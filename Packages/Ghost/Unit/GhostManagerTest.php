<?php

declare(strict_types=1);

namespace Tests\Packages\Ghost\Unit;

use App\Models\Session as SessionModel;
use App\Models\User;
use App\Services\Auth\Interfaces\AuthServiceInterface;
use App\Services\SessionService;
use App\Services\UserService;
use Core\Services\ConfigServiceInterface;
use Ghost\Services\GhostManagerService;
use Helpers\Http\Session;
use Mockery;

beforeEach(function () {
    $this->auth = Mockery::mock(AuthServiceInterface::class);
    $this->sessionService = Mockery::mock(SessionService::class);
    $this->config = Mockery::mock(ConfigServiceInterface::class);
    $this->session = Mockery::mock(Session::class);
    $this->userService = Mockery::mock(UserService::class);

    // Default config mock
    $this->config->shouldReceive('get')->with('ghost.ttl', 3600)->andReturn(3600);
    $this->config->shouldReceive('get')->with('ghost.ttl')->andReturn(3600);
    $this->config->shouldReceive('get')->with('ghost.session_key')->andReturn('anchor_ghost_impersonation');
    $this->config->shouldReceive('get')->with('session.name')->andReturn('anchor_session');
    $this->config->shouldReceive('get')->with('encryption_key', 'ghost-secret-key-fallback')->andReturn('ghost-secret-key-fallback');
    $this->config->shouldReceive('get')->with('ghost.protected_roles', ['super-admin'])->andReturn(['super-admin']);
    $this->config->shouldReceive('get')->with('ghost.allowed_roles', ['admin', 'super-admin'])->andReturn(['admin', 'super-admin']);

    $this->ghostManager = new GhostManagerService(
        $this->auth,
        $this->sessionService,
        $this->config,
        $this->session,
        $this->userService
    );
});

afterEach(function () {
    Mockery::close();
});

test('impersonate starts successfully', function () {
    $impersonator = Mockery::mock(User::class)->makePartial();
    $impersonator->id = 1;
    $impersonator->shouldReceive('hasRole')->with('admin')->andReturnTrue();
    $impersonator->shouldReceive('hasRole')->with('super-admin')->andReturnFalse();

    $targetUser = Mockery::mock(User::class)->makePartial();
    $targetUser->id = 2;
    $targetUser->shouldReceive('hasRole')->with('super-admin')->andReturnFalse();

    $targetSession = Mockery::mock(SessionModel::class)->makePartial();
    $targetSession->token = 'target-token';

    $this->auth->shouldReceive('user')->andReturn($impersonator);
    // isGhosting check
    $this->session->shouldReceive('get')->with('anchor_ghost_impersonation')->andReturnNull();
    $this->session->shouldReceive('get')->with('anchor_session')->andReturn('original-token');
    $this->sessionService->shouldReceive('createNewSession')->with($targetUser, 3600)->andReturn($targetSession);

    $this->session->shouldReceive('set')->with('anchor_ghost_impersonation', Mockery::on(function ($data) {
        return $data['impersonator_id'] === 1
            && $data['impersonated_id'] === 2
            && $data['original_token'] === 'original-token'
            && isset($data['signature']);
    }))->once();

    $this->session->shouldReceive('set')->with('anchor_session', 'target-token')->once();

    $result = $this->ghostManager->impersonate($targetUser);

    expect($result)->toBeTrue();
});

test('impersonate fails if not logged in', function () {
    $targetUser = Mockery::mock(User::class)->makePartial();
    // isGhosting check
    $this->session->shouldReceive('get')->with('anchor_ghost_impersonation')->andReturnNull();
    $this->auth->shouldReceive('user')->andReturn(null);

    $result = $this->ghostManager->impersonate($targetUser);

    expect($result)->toBeFalse();
});

test('impersonate fails if impersonating self', function () {
    $user = Mockery::mock(User::class)->makePartial();
    $user->id = 1;

    $user = Mockery::mock(User::class)->makePartial();
    $user->id = 1;

    // isGhosting check
    $this->session->shouldReceive('get')->with('anchor_ghost_impersonation')->andReturnNull();
    $this->auth->shouldReceive('user')->andReturn($user);

    $result = $this->ghostManager->impersonate($user);

    expect($result)->toBeFalse();
});

test('stop restores original session', function () {
    $ghostData = [
        'impersonator_id' => 1,
        'impersonated_id' => 2,
        'original_token' => 'original-token',
        'expires_at' => time() + 3600,
    ];
    // Generate valid signature
    $payload = [
        'expires_at' => $ghostData['expires_at'],
        'impersonated_id' => 2,
        'impersonator_id' => 1,
        'original_token' => 'original-token',
    ];
    ksort($payload);
    $ghostData['signature'] = hash_hmac('sha256', json_encode($payload), 'ghost-secret-key-fallback');

    $this->session->shouldReceive('get')->with('anchor_ghost_impersonation')->andReturn($ghostData);
    $this->session->shouldReceive('set')->with('anchor_session', 'original-token')->once();
    $this->session->shouldReceive('delete')->with('anchor_ghost_impersonation')->once();

    $impersonator = Mockery::mock(User::class)->makePartial();
    $impersonated = Mockery::mock(User::class)->makePartial();
    $this->userService->shouldReceive('findById')->with(1)->andReturn($impersonator);
    $this->userService->shouldReceive('findById')->with(2)->andReturn($impersonated);

    $result = $this->ghostManager->stop();

    expect($result)->toBeTrue();
});

test('is ghosting verifies signature', function () {
    $ghostData = [
        'impersonator_id' => 1,
        'impersonated_id' => 2,
        'original_token' => 'original-token',
        'expires_at' => time() + 3600,
        'signature' => 'invalid-signature'
    ];

    $this->session->shouldReceive('get')->with('anchor_ghost_impersonation')->andReturn($ghostData);

    expect($this->ghostManager->isGhosting())->toBeFalse();
});

test('is expired works', function () {
    $ghostData = [
        'impersonator_id' => 1,
        'impersonated_id' => 2,
        'original_token' => 'original-token',
        'expires_at' => time() - 1, // Expired
    ];
    // Generate valid signature for expired data
    $payload = [
        'expires_at' => $ghostData['expires_at'],
        'impersonated_id' => 2,
        'impersonator_id' => 1,
        'original_token' => 'original-token',
    ];
    ksort($payload);
    $ghostData['signature'] = hash_hmac('sha256', json_encode($payload), 'ghost-secret-key-fallback');

    $this->session->shouldReceive('get')->with('anchor_ghost_impersonation')->andReturn($ghostData);

    expect($this->ghostManager->isExpired())->toBeTrue();
});

test('get impersonator returns user', function () {
    $ghostData = [
        'impersonator_id' => 1,
        'impersonated_id' => 2,
        'original_token' => 'original-token',
        'expires_at' => time() + 3600,
    ];
    $payload = ['expires_at' => $ghostData['expires_at'], 'impersonated_id' => 2, 'impersonator_id' => 1, 'original_token' => 'original-token'];
    ksort($payload);
    $ghostData['signature'] = hash_hmac('sha256', json_encode($payload), 'ghost-secret-key-fallback');

    $user = Mockery::mock(User::class)->makePartial();
    $this->session->shouldReceive('get')->with('anchor_ghost_impersonation')->andReturn($ghostData);
    $this->userService->shouldReceive('findById')->with(1)->andReturn($user);

    $result = $this->ghostManager->getImpersonator();

    expect($result)->toBe($user);
});

test('get impersonated returns user', function () {
    $ghostData = [
        'impersonator_id' => 1,
        'impersonated_id' => 2,
        'original_token' => 'original-token',
        'expires_at' => time() + 3600,
    ];
    $payload = ['expires_at' => $ghostData['expires_at'], 'impersonated_id' => 2, 'impersonator_id' => 1, 'original_token' => 'original-token'];
    ksort($payload);
    $ghostData['signature'] = hash_hmac('sha256', json_encode($payload), 'ghost-secret-key-fallback');

    $user = Mockery::mock(User::class)->makePartial();
    $this->session->shouldReceive('get')->with('anchor_ghost_impersonation')->andReturn($ghostData);
    $this->userService->shouldReceive('findById')->with(2)->andReturn($user);

    $result = $this->ghostManager->getImpersonated();

    expect($result)->toBe($user);
});
