<?php

declare(strict_types=1);

use Core\Ioc\Container;
use Core\Services\ConfigServiceInterface;
use Helpers\Http\Request;
use Security\Auth\Contracts\Authenticatable;
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
        $fakeAuth->actingAs(Mockery::mock(Authenticatable::class), 'web');
        $fakeAuth->actingAs(Mockery::mock(Authenticatable::class), 'admin');

        $fakeAuth->logoutAll();

        $fakeAuth->assertGuest('web');
        $fakeAuth->assertGuest('admin');
    });
});
