<?php

declare(strict_types=1);

use App\Middleware\Web\WebAuthMiddleware;
use App\Services\Auth\Interfaces\AuthServiceInterface;
use Helpers\Http\Request;
use Helpers\Http\Response;

describe('WebAuthMiddleware', function () {

    test('bypasses auth for exempted routes', function () {
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldNotReceive('isAuthenticated');

        $middleware = new WebAuthMiddleware($auth);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('routeShouldBypassAuth')->andReturn(true);

        $response = Mockery::mock(Response::class);

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });

    test('allows authenticated and authorized users', function () {
        $user = (object)['id' => 'user-123'];

        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldReceive('isAuthenticated')->andReturn(true);
        $auth->shouldReceive('isAuthorized')->with('home')->andReturn(true);
        $auth->shouldReceive('user')->andReturn($user);

        $middleware = new WebAuthMiddleware($auth);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('routeShouldBypassAuth')->andReturn(false);
        $request->shouldReceive('route')->andReturn('home');
        $request->shouldReceive('setHeader')->with('X-Account-ID', 'user-123')->once();

        $response = Mockery::mock(Response::class);

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });

    test('redirects unauthenticated users to login', function () {
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldReceive('isAuthenticated')->andReturn(false);
        $auth->shouldReceive('logout')->once();

        $middleware = new WebAuthMiddleware($auth);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('routeShouldBypassAuth')->andReturn(false);
        $request->shouldReceive('route')->andReturn('dashboard');
        $request->shouldReceive('fullRouteByName')->with('login')->andReturn('/login');

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('redirect')->with('/login')->once()->andReturnSelf();

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });

    test('redirects unauthorized users to login', function () {
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldReceive('isAuthenticated')->andReturn(true);
        $auth->shouldReceive('isAuthorized')->with('admin')->andReturn(false);
        $auth->shouldReceive('logout')->once();

        $middleware = new WebAuthMiddleware($auth);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('routeShouldBypassAuth')->andReturn(false);
        $request->shouldReceive('route')->andReturn('admin');
        $request->shouldReceive('fullRouteByName')->with('login')->andReturn('/login');

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('redirect')->with('/login')->once()->andReturnSelf();

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });
});
