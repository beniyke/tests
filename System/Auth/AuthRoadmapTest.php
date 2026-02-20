<?php

declare(strict_types=1);

use Core\Ioc\Container;
use Core\Services\ConfigServiceInterface;
use Helpers\Http\Request;
use Security\Auth\Interfaces\AuthManagerInterface;
use Security\Auth\Interfaces\GuardInterface;

describe('Auth Roadmap Features', function () {

    test('auth() helper is context-aware', function () {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('getRouteContext')->with('auth_guard')->andReturn('admin');

        $guard = Mockery::mock(GuardInterface::class);
        $authManager = Mockery::mock(AuthManagerInterface::class);
        $authManager->shouldReceive('guard')->with('admin')->andReturn($guard);

        // Mock the container
        Container::getInstance()->instance(Request::class, $request);
        Container::getInstance()->instance(AuthManagerInterface::class, $authManager);

        $result = auth();
        expect($result)->toBe($guard);
    });

    test('AuthManager logoutAll clears all guards', function () {
        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('auth.guards', [])->andReturn([
            'web' => [],
            'admin' => []
        ]);

        $webGuard = Mockery::mock(GuardInterface::class);
        $webGuard->shouldReceive('logout')->once();

        $adminGuard = Mockery::mock(GuardInterface::class);
        $adminGuard->shouldReceive('logout')->once();

        $authManager = new Security\Auth\AuthManager($config);

        // We need to override the resolve or guard method to return our mocks
        // Since AuthManager::guard() uses internally $this->guards, we can reflect into it or mock the resolve.
        // For simplicity in this test, we'll mock the guard() method if possible,
        // but AuthManager is what we are testing.

        $authManagerMock = Mockery::mock(Security\Auth\AuthManager::class, [$config])->makePartial();
        $authManagerMock->shouldReceive('guard')->with('web')->andReturn($webGuard);
        $authManagerMock->shouldReceive('guard')->with('admin')->andReturn($adminGuard);

        $authManagerMock->logoutAll();
    });
});
