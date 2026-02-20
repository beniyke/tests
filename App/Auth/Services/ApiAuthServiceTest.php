<?php

declare(strict_types=1);

use App\Models\User;
use App\Requests\LoginRequest;
use App\Services\Auth\ApiAuthService;
use Helpers\Http\Request;
use Security\Auth\Interfaces\AuthManagerInterface;
use Security\Auth\Interfaces\GuardInterface;
use Security\Auth\Interfaces\TokenManagerInterface;

beforeEach(function () {
    $this->tokenManager = $this->createMock(TokenManagerInterface::class);
    // Use getMockBuilder for Request to avoid conflict with 'method' method
    $this->request = $this->getMockBuilder(Request::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['post', 'getBearerToken'])
        ->getMock();
    $this->auth = $this->createMock(AuthManagerInterface::class);
    $this->guard = $this->createMock(GuardInterface::class);

    $this->auth->method('guard')->with('api')->willReturn($this->guard);

    $this->service = new ApiAuthService(
        $this->tokenManager,
        $this->request,
        $this->auth
    );
});

test('login success', function () {
    $loginRequest = $this->createMock(LoginRequest::class);
    $loginRequest->method('isValid')->willReturn(true);
    $loginRequest->method('toArray')->willReturn(['email' => 'test@example.com', 'password' => 'password']);

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

    $this->guard->expects($this->once())
        ->method('attempt')
        ->willReturn(true);

    $this->guard->expects($this->any())
        ->method('user')
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
    $loginRequest->method('toArray')->willReturn([]);

    $result = $this->service->login($loginRequest);

    expect($result)->toBeFalse();
    expect($this->service->getGeneratedToken())->toBeNull();
});

test('login failure invalid credentials', function () {
    $loginRequest = $this->createMock(LoginRequest::class);
    $loginRequest->method('isValid')->willReturn(true);
    $loginRequest->method('toArray')->willReturn(['email' => 'test@example.com', 'password' => 'wrong']);

    $this->guard->expects($this->once())
        ->method('attempt')
        ->willReturn(false);

    $result = $this->service->login($loginRequest);

    expect($result)->toBeFalse();
});

test('logout success', function () {
    $user = $this->createMock(User::class);
    $user->id = 1;

    $this->guard->expects($this->any())
        ->method('user')
        ->willReturn($user);

    $this->request->expects($this->any())
        ->method('getBearerToken')
        ->willReturn('1|token-secret');

    $this->tokenManager->expects($this->once())
        ->method('revokeToken')
        ->with(1)
        ->willReturn(true);

    $this->guard->expects($this->once())
        ->method('logout');

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
    $loginRequest->method('toArray')->willReturn(['email' => 'test@example.com']);

    $user = $this->createMock(User::class);
    $user->id = 1;

    $this->guard->method('attempt')->willReturn(true);
    $this->guard->method('user')->willReturn($user);
    $this->guard->method('check')->willReturn(true);

    expect($this->service->isAuthenticated())->toBeTrue(); // Initial mock state logic?
    // Wait, ApiAuthService::login calls guard('api')->setUser($user);

    $result = $this->service->login($loginRequest);

    expect($result)->toBeTrue();
    expect($this->service->isAuthenticated())->toBeTrue();
    expect($this->service->user())->toBe($user);
});

test('logout clears user state', function () {
    $user = $this->createMock(User::class);
    $user->id = 1;

    $this->guard->method('user')->willReturnOnConsecutiveCalls($user, null);
    $this->guard->method('check')->willReturnOnConsecutiveCalls(true, false);

    expect($this->service->isAuthenticated())->toBeTrue();

    $this->service->logout();

    expect($this->service->isAuthenticated())->toBeFalse();
    expect($this->service->user())->toBeNull();
});

test('can() returns true when user has ability', function () {
    $user = $this->createMock(User::class);
    $this->guard->method('check')->willReturn(true);

    $this->request->expects($this->any())->method('getBearerToken')->willReturn('1|token');

    $this->tokenManager->expects($this->once())
        ->method('checkAbility')
        ->with('1|token', 'read')
        ->willReturn(true);

    expect($this->service->can('read'))->toBeTrue();
});

test('can() returns false when user lacks ability', function () {
    $this->guard->method('check')->willReturn(true);
    $this->request->expects($this->any())->method('getBearerToken')->willReturn('1|token');

    $this->tokenManager->expects($this->once())
        ->method('checkAbility')
        ->with('1|token', 'write')
        ->willReturn(false);

    expect($this->service->can('write'))->toBeFalse();
});

test('can() returns false when not authenticated', function () {
    $this->guard->method('check')->willReturn(false);

    expect($this->service->can('read'))->toBeFalse();
});

test('can() checks multiple abilities', function () {
    $this->guard->method('check')->willReturn(true);
    $this->request->expects($this->any())->method('getBearerToken')->willReturn('1|token');

    $this->tokenManager->expects($this->any())
        ->method('checkAbility')
        ->willReturnCallback(fn ($token, $ability) => match ($ability) {
            'read', 'write' => true,
            default => false
        });

    expect($this->service->can(['read', 'write']))->toBeTrue();
    expect($this->service->can(['read', 'delete']))->toBeFalse();
});

test('login with custom abilities', function () {
    $loginRequest = $this->createMock(LoginRequest::class);
    $loginRequest->method('isValid')->willReturn(true);
    $loginRequest->method('toArray')->willReturn(['email' => 'test@example.com']);

    $this->request->expects($this->any())
        ->method('post')
        ->willReturnCallback(fn ($key) => match ($key) {
            'device_name' => 'Mobile App',
            'abilities' => ['read', 'write'],
            default => null
        });

    $user = $this->createMock(User::class);
    $this->guard->method('attempt')->willReturn(true);
    $this->guard->method('user')->willReturn($user);

    $this->tokenManager->expects($this->once())
        ->method('createToken')
        ->with($user, 'Mobile App', ['read', 'write'])
        ->willReturn('1|token-secret');

    $this->service->login($loginRequest);
});

test('logout with non-personal-access token returns true', function () {
    $user = $this->createMock(User::class);
    $this->guard->method('user')->willReturn($user);

    $this->request->expects($this->any())
        ->method('getBearerToken')
        ->willReturn('some-static-token');

    $this->tokenManager->expects($this->never())
        ->method('revokeToken');

    $result = $this->service->logout();

    expect($result)->toBeTrue();
});
