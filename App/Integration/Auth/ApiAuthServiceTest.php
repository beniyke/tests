<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Auth\ApiAuthService;
use Helpers\Http\Request;
use Security\Auth\Interfaces\AuthManagerInterface;
use Security\Auth\Interfaces\GuardInterface;
use Security\Auth\Interfaces\TokenManagerInterface;

describe('ApiAuthService Integration', function () {
    beforeEach(function () {
        $this->tokenManager = Mockery::mock(TokenManagerInterface::class);
        $this->request = Mockery::mock(Request::class);
        $this->auth = Mockery::mock(AuthManagerInterface::class);
        $this->guard = Mockery::mock(GuardInterface::class);

        $this->auth->shouldReceive('guard')->with('api')->andReturn($this->guard);
    });

    afterEach(function () {
        Mockery::close();
    });

    test('isAuthenticated returns true when user is authenticated', function () {
        $this->guard->shouldReceive('check')->once()->andReturn(true);

        $authService = new ApiAuthService(
            $this->tokenManager,
            $this->request,
            $this->auth
        );

        expect($authService->isAuthenticated())->toBeTrue();
    });

    test('isAuthenticated returns false when user is not authenticated', function () {
        $this->guard->shouldReceive('check')->once()->andReturn(false);

        $authService = new ApiAuthService(
            $this->tokenManager,
            $this->request,
            $this->auth
        );

        expect($authService->isAuthenticated())->toBeFalse();
    });

    test('user returns the authenticated user', function () {
        $user = Mockery::mock(User::class);
        $this->guard->shouldReceive('user')->once()->andReturn($user);

        $authService = new ApiAuthService(
            $this->tokenManager,
            $this->request,
            $this->auth
        );

        expect($authService->user())->toBe($user);
    });

    test('user returns null when not authenticated', function () {
        $this->guard->shouldReceive('user')->once()->andReturn(null);

        $authService = new ApiAuthService(
            $this->tokenManager,
            $this->request,
            $this->auth
        );

        expect($authService->user())->toBeNull();
    });
});
