<?php

declare(strict_types=1);

use App\Middleware\Web\PasswordUpdateMiddleware;
use App\Services\Auth\Interfaces\AuthServiceInterface;
use Core\Services\ConfigServiceInterface;
use Helpers\Http\Request;
use Helpers\Http\Response;

describe('PasswordUpdateMiddleware', function () {

    test('allows unauthenticated users', function () {
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldReceive('isAuthenticated')->andReturn(false);

        $config = Mockery::mock(ConfigServiceInterface::class);

        $middleware = new PasswordUpdateMiddleware($auth, $config);
        $request = Mockery::mock(Request::class);
        $response = Mockery::mock(Response::class);

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });

    test('allows users with fresh passwords', function () {
        $user = Mockery::mock();
        $user->shouldReceive('passwordNeedsUpdate')->with(90)->andReturn(false);

        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldReceive('isAuthenticated')->andReturn(true);
        $auth->shouldReceive('user')->andReturn($user);

        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('auth.password_max_age_days')->andReturn(90);

        $middleware = new PasswordUpdateMiddleware($auth, $config);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->andReturn('dashboard');
        $request->shouldReceive('routeName')->with('change-password')->andReturn('change-password');
        $request->shouldReceive('isLogoutRoute')->andReturn(false);

        $response = Mockery::mock(Response::class);

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });

    test('redirects users with expired passwords', function () {
        $user = Mockery::mock();
        $user->shouldReceive('passwordNeedsUpdate')->with(90)->andReturn(true);

        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldReceive('isAuthenticated')->andReturn(true);
        $auth->shouldReceive('user')->andReturn($user);

        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('auth.password_max_age_days')->andReturn(90);

        $middleware = new PasswordUpdateMiddleware($auth, $config);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->andReturn('dashboard');
        $request->shouldReceive('routeName')->with('change-password')->andReturn('change-password');
        $request->shouldReceive('isLogoutRoute')->andReturn(false);
        $request->shouldReceive('fullRouteByName')->with('change-password')->andReturn('/change-password');

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('redirect')->with('/change-password')->once()->andReturnSelf();

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });

    test('allows access to password update route', function () {
        $user = Mockery::mock();
        $user->shouldReceive('passwordNeedsUpdate')->with(90)->andReturn(true);

        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldReceive('isAuthenticated')->andReturn(true);
        $auth->shouldReceive('user')->andReturn($user);

        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('auth.password_max_age_days')->andReturn(90);

        $middleware = new PasswordUpdateMiddleware($auth, $config);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->andReturn('change-password');
        $request->shouldReceive('routeName')->with('change-password')->andReturn('change-password');
        $request->shouldReceive('isLogoutRoute')->andReturn(false);

        $response = Mockery::mock(Response::class);

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });

    test('allows access to logout route', function () {
        $user = Mockery::mock();
        $user->shouldReceive('passwordNeedsUpdate')->with(90)->andReturn(true);

        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldReceive('isAuthenticated')->andReturn(true);
        $auth->shouldReceive('user')->andReturn($user);

        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('auth.password_max_age_days')->andReturn(90);

        $middleware = new PasswordUpdateMiddleware($auth, $config);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')->andReturn('logout');
        $request->shouldReceive('routeName')->with('change-password')->andReturn('change-password');
        $request->shouldReceive('isLogoutRoute')->andReturn(true);

        $response = Mockery::mock(Response::class);

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });
});
