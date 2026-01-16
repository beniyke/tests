<?php

declare(strict_types=1);

use App\Middleware\Web\RedirectIfAuthenticatedMiddleware;
use App\Services\Auth\Interfaces\AuthServiceInterface;
use Helpers\Http\Request;
use Helpers\Http\Response;

describe('RedirectIfAuthenticatedMiddleware', function () {

    test('redirects authenticated users on login route', function () {
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldReceive('isAuthenticated')->andReturn(true);

        $middleware = new RedirectIfAuthenticatedMiddleware($auth);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('isLoginRoute')->andReturn(true);
        $request->shouldReceive('fullRouteByName')->with('home')->andReturn('/home');

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
        $auth->shouldReceive('isAuthenticated')->andReturn(false);

        $middleware = new RedirectIfAuthenticatedMiddleware($auth);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('isLoginRoute')->andReturn(true);

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

        $middleware = new RedirectIfAuthenticatedMiddleware($auth);
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
