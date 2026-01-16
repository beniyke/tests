<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Auth\ApiAuthService;
use App\Services\UserService;
use Bridge\ApiAuth\Contracts\ApiTokenValidatorServiceInterface;
use Bridge\TokenManager;
use Helpers\Http\Request;
use Helpers\Http\UserAgent;
use Security\Firewall\Drivers\AuthFirewall;

describe('ApiAuthService Integration', function () {
    beforeEach(function () {
        $this->tokenValidator = Mockery::mock(ApiTokenValidatorServiceInterface::class);
        $this->userService = Mockery::mock(UserService::class);
        $this->tokenManager = Mockery::mock(TokenManager::class);
        $this->firewall = Mockery::mock(AuthFirewall::class);
        $this->agent = Mockery::mock(UserAgent::class);
        $this->request = Mockery::mock(Request::class);
    });

    afterEach(function () {
        Mockery::close();
    });

    test('isAuthenticated returns true when user is authenticated', function () {
        $user = Mockery::mock(User::class);
        $this->tokenValidator->shouldReceive('getAuthenticatedUser')->once()->andReturn($user);

        $authService = new ApiAuthService(
            $this->tokenValidator,
            $this->userService,
            $this->tokenManager,
            $this->firewall,
            $this->agent,
            $this->request
        );

        expect($authService->isAuthenticated())->toBeTrue();
    });

    test('isAuthenticated returns false when user is not authenticated', function () {
        $this->tokenValidator->shouldReceive('getAuthenticatedUser')->once()->andReturn(null);

        $authService = new ApiAuthService(
            $this->tokenValidator,
            $this->userService,
            $this->tokenManager,
            $this->firewall,
            $this->agent,
            $this->request
        );

        expect($authService->isAuthenticated())->toBeFalse();
    });

    test('user returns the authenticated user', function () {
        $user = Mockery::mock(User::class);
        $this->tokenValidator->shouldReceive('getAuthenticatedUser')->once()->andReturn($user);

        $authService = new ApiAuthService(
            $this->tokenValidator,
            $this->userService,
            $this->tokenManager,
            $this->firewall,
            $this->agent,
            $this->request
        );

        expect($authService->user())->toBe($user);
    });

    test('user returns null when not authenticated', function () {
        $this->tokenValidator->shouldReceive('getAuthenticatedUser')->once()->andReturn(null);

        $authService = new ApiAuthService(
            $this->tokenValidator,
            $this->userService,
            $this->tokenManager,
            $this->firewall,
            $this->agent,
            $this->request
        );

        expect($authService->user())->toBeNull();
    });
});
