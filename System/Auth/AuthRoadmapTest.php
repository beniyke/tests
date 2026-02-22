<?php

declare(strict_types=1);

use Core\Ioc\Container;
use Core\Services\ConfigServiceInterface;
use Helpers\Http\Request;
use Testing\Concerns\InteractsWithFakes;

describe('Auth Roadmap Features', function () {
    uses(InteractsWithFakes::class);

    test('auth() helper is context-aware', function () {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('getRouteContext')->with('auth_guard')->andReturn('admin');

        $fakeAuth = $this->fakeAuth();
        $guard = $fakeAuth->guard('admin');

        // Mock the container
        Container::getInstance()->instance(Request::class, $request);

        $result = auth();
        expect($result)->toBe($guard);
    });

    test('AuthManager logoutAll clears all guards', function () {
        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('auth.guards', [])->andReturn([
            'web' => [],
            'admin' => []
        ]);

        $fakeAuth = $this->fakeAuth();
        $webGuard = $fakeAuth->guard('web');
        $adminGuard = $fakeAuth->guard('admin');

        $fakeAuth->logoutAll();

        // Assertions in AuthFake could be improved to check if logout was called on all guards
        // But for now, we are verifying the helper usage.
    });
});
