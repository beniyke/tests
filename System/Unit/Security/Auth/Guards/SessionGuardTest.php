<?php

declare(strict_types=1);

namespace Tests\System\Unit\Security\Auth\Guards;

use Core\Services\ConfigServiceInterface;
use Helpers\Http\Session;
use Mockery;
use Security\Auth\Contracts\Authenticatable;
use Security\Auth\Guards\SessionGuard;
use Security\Auth\Interfaces\SessionManagerInterface;
use Security\Auth\Interfaces\UserSourceInterface;

describe('SessionGuard Hardening', function () {
    beforeEach(function () {
        $this->source = Mockery::mock(UserSourceInterface::class);
        $this->session = Mockery::mock(Session::class);
        $this->config = Mockery::mock(ConfigServiceInterface::class);
        $this->sessionManager = Mockery::mock(SessionManagerInterface::class);
        $this->guard = new SessionGuard('web', $this->source, $this->session, $this->config, $this->sessionManager);
    });

    afterEach(function () {
        Mockery::close();
    });

    test('setUser regenerates session ID to prevent fixation', function () {
        $user = Mockery::mock(Authenticatable::class);
        $user->shouldReceive('getAuthId')->andReturn(123);

        $this->session->shouldReceive('regenerateId')->once();
        $this->sessionManager->shouldReceive('create')->with($user)->andReturn('token-123');
        $this->session->shouldReceive('set')->once()->with('auth_session_token_web', 'token-123');

        $this->guard->setUser($user);
    });

    test('user() retrieves user through session manager', function () {
        $this->session->shouldReceive('get')->with('auth_session_token_web')->andReturn('token-123');
        $user = Mockery::mock(Authenticatable::class);

        $this->sessionManager->shouldReceive('validate')->with('token-123')->andReturn($user);

        expect($this->guard->user())->toBe($user);
    });

    test('validate() uses source to check credentials', function () {
        $credentials = ['email' => 'test@example.com', 'password' => 'secret'];
        $user = Mockery::mock(Authenticatable::class);

        $this->source->shouldReceive('retrieveByCredentials')->with($credentials)->andReturn($user);
        $this->source->shouldReceive('validateCredentials')->with($user, $credentials)->andReturn(true);
        $user->shouldReceive('canAuthenticate')->andReturn(true);

        expect($this->guard->validate($credentials))->toBeTrue();
    });

    test('logout() clears session and revokes token', function () {
        $this->session->shouldReceive('get')->with('auth_session_token_web')->once()->andReturn('token-123');
        $this->sessionManager->shouldReceive('revoke')->with('token-123')->once();
        $this->session->shouldReceive('delete')->with('auth_session_token_web')->once();
        $this->session->shouldReceive('regenerateId')->once();
        $this->session->shouldReceive('get')->with('auth_session_token_web')->andReturn(null);

        $this->guard->logout();
        expect($this->guard->user())->toBeNull();
    });
});
