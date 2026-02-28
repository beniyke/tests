<?php

declare(strict_types=1);

use App\Middleware\Web\RedirectIfAuthenticatedMiddleware;
use Core\Contracts\AuthServiceInterface;
use Core\Services\ConfigServiceInterface;
use Helpers\Http\Request;
use Helpers\Http\Response;

describe('RedirectIfAuthenticatedMiddleware', function () {

    test('redirects authenticated users on login route', function () {
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldReceive('viaGuard')->with('web')->andReturnSelf();
        $auth->shouldReceive('isAuthenticated')->andReturn(true);

        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('auth.guards.web.route.home')->andReturn('home');
        $middleware = new RedirectIfAuthenticatedMiddleware($auth, $config);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('isLoginRoute')->andReturn(true);
        $request->shouldReceive('getRouteContext')->with('guards')->andReturn(['web']);
        $request->shouldReceive('baseUrl')->with('home')->andReturn('/home');

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('redirect')->with('/home')->once()->andReturnSelf();

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });

    test('allows unauthenticated users on login route', function () {
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldReceive('viaGuard')->with('web')->andReturnSelf();
        $auth->shouldReceive('isAuthenticated')->andReturn(false);

        $config = Mockery::mock(ConfigServiceInterface::class);
        $middleware = new RedirectIfAuthenticatedMiddleware($auth, $config);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('isLoginRoute')->andReturn(true);
        $request->shouldReceive('getRouteContext')->with('guards')->andReturn(['web']);

        $response = Mockery::mock(Response::class);

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });

    test('allows authenticated users on non-login routes', function () {
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldReceive('isAuthenticated')->andReturn(true);

        $config = Mockery::mock(ConfigServiceInterface::class);
        $middleware = new RedirectIfAuthenticatedMiddleware($auth, $config);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('isLoginRoute')->andReturn(false);

        $response = Mockery::mock(Response::class);

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });
});
