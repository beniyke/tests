<?php

declare(strict_types=1);

use App\Auth\Requests\LoginRequest;
use App\Models\User;
use App\Services\Auth\WebAuthService;
use App\Services\MenuService;
use Helpers\Http\Flash;
use Security\Auth\Interfaces\AuthManagerInterface;
use Security\Auth\Interfaces\GuardInterface;

describe('WebAuthService Integration', function () {
    beforeEach(function () {
        $this->flash = Mockery::mock(Flash::class);
        $this->menuService = Mockery::mock(MenuService::class);
        $this->auth = Mockery::mock(AuthManagerInterface::class);
        $this->guard = Mockery::mock(GuardInterface::class);

        $this->auth->shouldReceive('guard')->with('web')->andReturn($this->guard);
    });

    afterEach(function () {
        Mockery::close();
    });

    test('isAuthenticated returns true when user is authenticated', function () {
        $this->guard->shouldReceive('check')->once()->andReturn(true);

        $authService = new WebAuthService(
            $this->flash,
            $this->menuService,
            $this->auth
        );

        expect($authService->isAuthenticated())->toBeTrue();
    });

    test('isAuthenticated returns false when user is not authenticated', function () {
        $this->guard->shouldReceive('check')->once()->andReturn(false);

        $authService = new WebAuthService(
            $this->flash,
            $this->menuService,
            $this->auth
        );

        expect($authService->isAuthenticated())->toBeFalse();
    });

    test('user returns the authenticated user', function () {
        $user = Mockery::mock(User::class);
        $this->guard->shouldReceive('user')->once()->andReturn($user);

        $authService = new WebAuthService(
            $this->flash,
            $this->menuService,
            $this->auth
        );

        expect($authService->user())->toBe($user);
    });

    test('user returns null when not authenticated', function () {
        $this->guard->shouldReceive('user')->once()->andReturn(null);

        $authService = new WebAuthService(
            $this->flash,
            $this->menuService,
            $this->auth
        );

        expect($authService->user())->toBeNull();
    });

    test('login success', function () {
        $loginRequest = Mockery::mock(LoginRequest::class);
        $loginRequest->shouldReceive('isValid')->once()->andReturn(true);
        $loginRequest->shouldReceive('toArray')->andReturn(['email' => 'test@example.com', 'password' => 'password']);
        $loginRequest->shouldReceive('hasRememberMe')->andReturn(false);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('name')->andReturn('Test User');
        $user->shouldReceive('canLogin')->andReturn(true);

        $this->guard->shouldReceive('attempt')->once()->andReturn(true);
        $this->guard->shouldReceive('user')->andReturn($user);

        $this->flash->shouldReceive('success')->once();

        $authService = new WebAuthService(
            $this->flash,
            $this->menuService,
            $this->auth
        );

        expect($authService->login($loginRequest))->toBeTrue();
    });

    test('login failure invalid credentials', function () {
        $loginRequest = Mockery::mock(LoginRequest::class);
        $loginRequest->shouldReceive('isValid')->once()->andReturn(true);
        $loginRequest->shouldReceive('toArray')->andReturn(['email' => 'test@example.com', 'password' => 'wrong']);

        $this->guard->shouldReceive('attempt')->once()->andReturn(false);
        $this->flash->shouldReceive('error')->once();

        $authService = new WebAuthService(
            $this->flash,
            $this->menuService,
            $this->auth
        );

        expect($authService->login($loginRequest))->toBeFalse();
    });

    test('login failure invalid request', function () {
        $loginRequest = Mockery::mock(LoginRequest::class);
        $loginRequest->shouldReceive('isValid')->once()->andReturn(false);
        $loginRequest->shouldReceive('toArray')->andReturn([]);
        $this->flash->shouldReceive('error')->once();

        $authService = new WebAuthService(
            $this->flash,
            $this->menuService,
            $this->auth
        );

        expect($authService->login($loginRequest))->toBeFalse();
    });

    test('logout terminates guard', function () {
        $user = Mockery::mock(User::class);
        $this->guard->shouldReceive('user')->andReturn($user);
        $this->guard->shouldReceive('logout')->once();

        $authService = new WebAuthService(
            $this->flash,
            $this->menuService,
            $this->auth
        );

        expect($authService->logout())->toBeTrue();
    });

    test('isAuthorized returns true when user can access route', function () {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('canLogin')->andReturn(true);
        $this->guard->shouldReceive('user')->andReturn($user);
        $this->menuService->shouldReceive('getAccessibleRoutes')->with($user)->andReturn(['dashboard', 'profile']);

        $authService = new WebAuthService(
            $this->flash,
            $this->menuService,
            $this->auth
        );

        expect($authService->isAuthorized('dashboard'))->toBeTrue();
    });

    test('isAuthorized returns false when user cannot access route', function () {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('canLogin')->andReturn(true);
        $this->guard->shouldReceive('user')->andReturn($user);
        $this->menuService->shouldReceive('getAccessibleRoutes')->with($user)->andReturn(['dashboard']);

        $authService = new WebAuthService(
            $this->flash,
            $this->menuService,
            $this->auth
        );

        expect($authService->isAuthorized('admin'))->toBeFalse();
    });
});
