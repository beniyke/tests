<?php

declare(strict_types=1);

use App\Models\User;
use App\Requests\LoginRequest;
use App\Services\Auth\ApiAuthService;
use App\Services\UserService;
use Bridge\ApiAuth\Contracts\ApiTokenValidatorServiceInterface;
use Bridge\TokenManager;
use Helpers\Data;
use Helpers\Http\Request;
use Helpers\Http\UserAgent;
use Security\Firewall\Drivers\AuthFirewall;

beforeEach(function () {
    $this->tokenValidator = $this->createMock(ApiTokenValidatorServiceInterface::class);
    $this->userService = $this->createMock(UserService::class);
    $this->tokenManager = $this->createMock(TokenManager::class);
    $this->firewall = $this->createMock(AuthFirewall::class);
    $this->agent = $this->createMock(UserAgent::class);
    $this->request = $this->createMock(Request::class);

    // Mock firewall chainable methods
    $this->firewall->method('fail')->willReturn($this->firewall);
    $this->firewall->method('clear')->willReturn($this->firewall);
    $this->firewall->method('capture');

    $this->service = new ApiAuthService(
        $this->tokenValidator,
        $this->userService,
        $this->tokenManager,
        $this->firewall,
        $this->agent,
        $this->request
    );
});

test('login success', function () {
    $loginRequest = $this->createMock(LoginRequest::class);
    $loginRequest->method('isValid')->willReturn(true);
    $loginRequest->method('getData')->willReturn(Data::make(['email' => 'test@example.com', 'password' => 'password']));
    $this->request->expects($this->any())
        ->method('post')
        ->willReturnCallback(fn ($key) => match ($key) {
            'device_name' => 'Test Device',
            default => null
        });

    $user = $this->createMock(User::class);
    $user->id = 1;
    $user->name = 'Test User';
    $user->email = 'test@example.com';
    $user->method('only')->willReturn(['id' => 1, 'name' => 'Test User', 'email' => 'test@example.com']);

    $this->userService->expects($this->once())
        ->method('confirmUser')
        ->willReturn($user);

    $this->tokenManager->expects($this->once())
        ->method('createToken')
        ->with($user, 'Test Device', ['*'])
        ->willReturn('1|token-secret');

    $result = $this->service->login($loginRequest);

    expect($result)->toBeTrue();
    expect($this->service->getGeneratedToken())->toBe('1|token-secret');
});

test('login failure invalid request', function () {
    $loginRequest = $this->createMock(LoginRequest::class);
    $loginRequest->method('isValid')->willReturn(false);

    $this->firewall->expects($this->once())->method('fail');

    $result = $this->service->login($loginRequest);

    expect($result)->toBeFalse();
    expect($this->service->getGeneratedToken())->toBeNull();
});

test('login failure invalid credentials', function () {
    $loginRequest = $this->createMock(LoginRequest::class);
    $loginRequest->method('isValid')->willReturn(true);
    $loginRequest->method('getData')->willReturn(Data::make(['email' => 'test@example.com', 'password' => 'wrong']));

    $this->userService->expects($this->once())
        ->method('confirmUser')
        ->willReturn(null);

    $this->firewall->expects($this->once())->method('fail');

    $result = $this->service->login($loginRequest);

    expect($result)->toBeFalse();
});

test('logout success', function () {
    $this->request->expects($this->any())
        ->method('getBearerToken')
        ->willReturn('1|token-secret');

    $this->tokenManager->expects($this->once())
        ->method('revokeToken')
        ->with(1)
        ->willReturn(true);

    $result = $this->service->logout();

    expect($result)->toBeTrue();
});

test('logout no token', function () {
    $this->request->expects($this->any())
        ->method('getBearerToken')
        ->willReturn(null);

    $this->tokenManager->expects($this->never())->method('revokeToken');

    $result = $this->service->logout();

    expect($result)->toBeTrue();
});

test('login sets user state', function () {
    $loginRequest = $this->createMock(LoginRequest::class);
    $loginRequest->method('isValid')->willReturn(true);
    $loginRequest->method('getData')->willReturn(Data::make(['email' => 'test@example.com']));

    $this->request->expects($this->any())
        ->method('post')
        ->willReturnCallback(fn ($key) => match ($key) {
            'device_name' => 'Test Device',
            default => null
        });

    $user = $this->createMock(User::class);
    $user->id = 1;
    $user->method('only')->willReturn(['id' => 1, 'name' => 'Test', 'email' => 'test@example.com']);

    $this->userService->method('confirmUser')->willReturn($user);
    $this->tokenManager->method('createToken')->willReturn('1|token');

    expect($this->service->isAuthenticated())->toBeFalse();

    $result = $this->service->login($loginRequest);

    expect($result)->toBeTrue();
    expect($this->service->isAuthenticated())->toBeTrue();
    expect($this->service->user())->toBe($user);
});

test('logout clears user state', function () {
    $user = $this->createMock(User::class);
    $tokenValidator = $this->createMock(ApiTokenValidatorServiceInterface::class);
    $tokenValidator->method('getAuthenticatedUser')->willReturn($user);

    $request = $this->createMock(Request::class);
    $request->expects($this->any())->method('getBearerToken')->willReturn('1|token');

    $tokenManager = $this->createMock(TokenManager::class);
    $tokenManager->expects($this->once())->method('revokeToken')->willReturn(true);

    $service = new ApiAuthService(
        $tokenValidator,
        $this->userService,
        $tokenManager,
        $this->firewall,
        $this->agent,
        $request
    );

    expect($service->isAuthenticated())->toBeTrue();

    $service->logout();

    expect($service->isAuthenticated())->toBeFalse();
    expect($service->user())->toBeNull();
});

test('can() returns true when user has ability', function () {
    $user = $this->createMock(User::class);
    $tokenValidator = $this->createMock(ApiTokenValidatorServiceInterface::class);
    $tokenValidator->method('getAuthenticatedUser')->willReturn($user);

    $request = $this->createMock(Request::class);
    $request->expects($this->any())->method('getBearerToken')->willReturn('1|token');

    $tokenManager = $this->createMock(TokenManager::class);
    $tokenManager->expects($this->once())
        ->method('checkAbility')
        ->with('1|token', 'read')
        ->willReturn(true);

    $service = new ApiAuthService(
        $tokenValidator,
        $this->userService,
        $tokenManager,
        $this->firewall,
        $this->agent,
        $request
    );

    expect($service->can('read'))->toBeTrue();
});

test('can() returns false when user lacks ability', function () {
    $user = $this->createMock(User::class);
    $tokenValidator = $this->createMock(ApiTokenValidatorServiceInterface::class);
    $tokenValidator->method('getAuthenticatedUser')->willReturn($user);

    $request = $this->createMock(Request::class);
    $request->expects($this->any())->method('getBearerToken')->willReturn('1|token');

    $tokenManager = $this->createMock(TokenManager::class);
    $tokenManager->expects($this->once())
        ->method('checkAbility')
        ->with('1|token', 'write')
        ->willReturn(false);

    $service = new ApiAuthService(
        $tokenValidator,
        $this->userService,
        $tokenManager,
        $this->firewall,
        $this->agent,
        $request
    );

    expect($service->can('write'))->toBeFalse();
});

test('can() returns false when not authenticated', function () {
    $tokenValidator = $this->createMock(ApiTokenValidatorServiceInterface::class);
    $tokenValidator->method('getAuthenticatedUser')->willReturn(null);

    $service = new ApiAuthService(
        $tokenValidator,
        $this->userService,
        $this->tokenManager,
        $this->firewall,
        $this->agent,
        $this->request
    );

    expect($service->can('read'))->toBeFalse();
});

test('can() checks multiple abilities', function () {
    $user = $this->createMock(User::class);
    $tokenValidator = $this->createMock(ApiTokenValidatorServiceInterface::class);
    $tokenValidator->method('getAuthenticatedUser')->willReturn($user);

    $request = $this->createMock(Request::class);
    $request->expects($this->any())->method('getBearerToken')->willReturn('1|token');

    $tokenManager = $this->createMock(TokenManager::class);
    $tokenManager->expects($this->any())
        ->method('checkAbility')
        ->willReturnCallback(fn ($token, $ability) => in_array($ability, ['read', 'write']));

    $service = new ApiAuthService(
        $tokenValidator,
        $this->userService,
        $tokenManager,
        $this->firewall,
        $this->agent,
        $request
    );

    expect($service->can(['read', 'write']))->toBeTrue();
    expect($service->can(['read', 'delete']))->toBeFalse();
});

test('login with custom abilities', function () {
    $loginRequest = $this->createMock(LoginRequest::class);
    $loginRequest->method('isValid')->willReturn(true);
    $loginRequest->method('getData')->willReturn(Data::make(['email' => 'test@example.com']));

    $request = $this->createMock(Request::class);
    $request->expects($this->any())
        ->method('post')
        ->willReturnCallback(fn ($key) => match ($key) {
            'device_name' => 'Mobile App',
            'abilities' => ['read', 'write'],
            default => null
        });

    $user = $this->createMock(User::class);
    $user->method('only')->willReturn(['id' => 1]);

    $userService = $this->createMock(UserService::class);
    $userService->method('confirmUser')->willReturn($user);

    $tokenManager = $this->createMock(TokenManager::class);
    $tokenManager->expects($this->once())
        ->method('createToken')
        ->with($user, 'Mobile App', ['read', 'write'])
        ->willReturn('1|token');

    $service = new ApiAuthService(
        $this->tokenValidator,
        $userService,
        $tokenManager,
        $this->firewall,
        $this->agent,
        $request
    );

    $result = $service->login($loginRequest);

    expect($result)->toBeTrue();
});

test('logout with non-personal-access token returns true', function () {
    $request = $this->createMock(Request::class);
    $request->expects($this->any())->method('getBearerToken')->willReturn('some-static-or-dynamic-token');

    $tokenManager = $this->createMock(TokenManager::class);
    $tokenManager->expects($this->never())->method('revokeToken');

    $service = new ApiAuthService(
        $this->tokenValidator,
        $this->userService,
        $tokenManager,
        $this->firewall,
        $this->agent,
        $request
    );

    $result = $service->logout();

    expect($result)->toBeTrue();
});
