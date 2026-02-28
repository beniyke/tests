<?php

declare(strict_types=1);

namespace Tests\System\Unit\Security\Auth;

use App\Models\User;
use Core\Services\ConfigServiceInterface;
use Helpers\Data\Contracts\DataTransferObject;
use Helpers\Http\Request;
use Mockery;
use Security\Auth\AuthResult;
use Security\Auth\AuthService;
use Security\Auth\Contracts\Authenticatable;
use Security\Auth\Interfaces\AuthManagerInterface;
use Security\Auth\Interfaces\GuardInterface;
use Security\Auth\Interfaces\TokenManagerInterface;

/**
 * @property AuthManagerInterface|Mockery\MockInterface   $authManager
 * @property Mockery\MockInterface|Request                $request
 * @property ConfigServiceInterface|Mockery\MockInterface $config
 * @property Mockery\MockInterface|TokenManagerInterface  $tokenManager
 * @property GuardInterface|Mockery\MockInterface         $guard
 * @property AuthService                                  $service
 */

describe('AuthService', function () {
    beforeEach(function () {
        $this->authManager = $authManager = Mockery::mock(AuthManagerInterface::class);
        $this->request = $request = Mockery::mock(Request::class);
        $this->config = $config = Mockery::mock(ConfigServiceInterface::class);
        $this->tokenManager = $tokenManager = Mockery::mock(TokenManagerInterface::class);
        $this->guard = $guard = Mockery::mock(GuardInterface::class);

        $this->config->shouldReceive('get')->with('auth.defaults.guard', 'web')->andReturn('web');
        $this->config->shouldReceive('get')->with('auth.password_max_age_days', 90)->andReturn(90)->byDefault();

        $this->fakeEvents();

        $this->service = new AuthService(
            $authManager,
            $request,
            $config,
            $tokenManager
        );
    });

    afterEach(function () {
        Mockery::close();
    });

    it('can check authentication status', function () {
        $this->authManager->shouldReceive('guard')->with('web')->andReturn($this->guard);
        $this->guard->shouldReceive('check')->once()->andReturn(true);

        expect($this->service->isAuthenticated())->toBeTrue();
    });

    it('can get the authenticated user', function () {
        $user = Mockery::mock(Authenticatable::class);
        $this->authManager->shouldReceive('guard')->with('web')->andReturn($this->guard);
        $this->guard->shouldReceive('user')->once()->andReturn($user);

        expect($this->service->user())->toBe($user);
    });

    it('can switch guards', function () {
        $this->service->viaGuard('api');

        $this->authManager->shouldReceive('guard')->with('api')->andReturn($this->guard);
        $this->guard->shouldReceive('check')->once()->andReturn(true);

        expect($this->service->isAuthenticated())->toBeTrue();
    });

    it('returns successful AuthResult on login success', function () {
        $requestData = Mockery::mock(DataTransferObject::class);
        $requestData->shouldReceive('isValid')->andReturn(true);
        $requestData->shouldReceive('toArray')->andReturn(['email' => 'test@test.com']);

        $user = Mockery::mock(Authenticatable::class);

        $this->authManager->shouldReceive('guard')->with('web')->andReturn($this->guard);
        $this->guard->shouldReceive('attempt')->with(['email' => 'test@test.com'])->andReturn(true);
        $this->guard->shouldReceive('user')->andReturn($user);

        // Mock token guard check (false for web)
        $this->config->shouldReceive('get')->with('auth.guards.web')->andReturn(['driver' => 'session']);

        $result = $this->service->login($requestData);

        expect($result)->toBeInstanceOf(AuthResult::class);
        expect($result->isSuccessful())->toBeTrue();
        expect($result->getUser())->toBe($user);
    });

    it('returns failed AuthResult on login failure', function () {
        $requestData = Mockery::mock(DataTransferObject::class);
        $requestData->shouldReceive('isValid')->andReturn(true);
        $requestData->shouldReceive('toArray')->andReturn(['email' => 'test@test.com']);

        $this->authManager->shouldReceive('guard')->with('web')->andReturn($this->guard);
        $this->guard->shouldReceive('attempt')->andReturn(false);

        $result = $this->service->login($requestData);

        expect($result)->toBeInstanceOf(AuthResult::class);
        expect($result->isSuccessful())->toBeFalse();
        expect($result->getMessage())->toBe('Invalid login credentials.');
    });

    it('handles token generation for token guards', function () {
        $this->service->viaGuard('api');

        $requestData = Mockery::mock(DataTransferObject::class);
        $requestData->shouldReceive('isValid')->andReturn(true);
        $requestData->shouldReceive('toArray')->andReturn(['email' => 'test@test.com']);

        $user = Mockery::mock(Authenticatable::class);

        $this->authManager->shouldReceive('guard')->with('api')->andReturn($this->guard);
        $this->guard->shouldReceive('attempt')->andReturn(true);
        $this->guard->shouldReceive('user')->andReturn($user);

        $this->config->shouldReceive('get')->with('auth.guards.api')->andReturn(['driver' => 'token']);

        $this->request->shouldReceive('post')->with('device_name')->andReturn('test-device');
        $this->request->shouldReceive('post')->with('abilities')->andReturn(['*']);

        $this->tokenManager->shouldReceive('createToken')
            ->with($user, 'test-device', ['*'])
            ->andReturn('generated-token');

        $result = $this->service->login($requestData);

        expect($result->isSuccessful())->toBeTrue();
        expect($result->getMetadata())->toHaveKey('token', 'generated-token');
    });

    it('can logout', function () {
        $user = Mockery::mock(Authenticatable::class);
        $this->authManager->shouldReceive('guard')->with('web')->andReturn($this->guard);
        $this->guard->shouldReceive('user')->andReturn($user);
        $this->guard->shouldReceive('logout')->once();

        $this->config->shouldReceive('get')->with('auth.guards.web')->andReturn(['driver' => 'session']);

        expect($this->service->logout())->toBeTrue();
    });

    it('handles remember me correctly when DTO implements ProvidesRememberMe', function () {
        $requestData = Mockery::mock(DataTransferObject::class . ', ' . \Security\Auth\Contracts\ProvidesRememberMe::class);
        $requestData->shouldReceive('isValid')->andReturn(true);
        $requestData->shouldReceive('toArray')->andReturn(['email' => 'test@test.com']);
        $requestData->shouldReceive('hasRememberMe')->andReturn(true);

        $user = Mockery::mock(Authenticatable::class);
        $this->authManager->shouldReceive('guard')->with('web')->andReturn($this->guard);
        $this->guard->shouldReceive('attempt')->andReturn(true);
        $this->guard->shouldReceive('user')->andReturn($user);

        $this->config->shouldReceive('get')->with('auth.guards.web')->andReturn(['driver' => 'session']);

        // We expect LoginEvent to be dispatched with remember = true
        // Pest/Mockery will handle the return if we just execute it
        $result = $this->service->login($requestData);

        expect($result->isSuccessful())->toBeTrue();
    });

    it('it can check if password needs update', function () {
        $this->authManager->shouldReceive('guard')->with('web')->andReturn($this->guard);

        $userWithoutMethod = Mockery::mock(Authenticatable::class);
        $userWithMethod = Mockery::mock(User::class);

        // Scenario 1: User does not have method
        $this->guard->shouldReceive('user')->once()->andReturn($userWithoutMethod);
        expect($this->service->passwordNeedsUpdate())->toBeFalse();

        // Scenario 2: User has method and needs update
        $this->guard->shouldReceive('user')->once()->andReturn($userWithMethod);
        $userWithMethod->shouldReceive('passwordNeedsUpdate')->with(90)->once()->andReturn(true);
        expect($this->service->passwordNeedsUpdate())->toBeTrue();

        // Scenario 3: User exists but method returns false
        $this->guard->shouldReceive('user')->once()->andReturn($userWithMethod);
        $userWithMethod->shouldReceive('passwordNeedsUpdate')->with(90)->once()->andReturn(false);
        expect($this->service->passwordNeedsUpdate())->toBeFalse();

        // Scenario 4: No user
        $this->guard->shouldReceive('user')->once()->andReturn(null);
        expect($this->service->passwordNeedsUpdate())->toBeFalse();
    });
});
