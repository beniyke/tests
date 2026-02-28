<?php

declare(strict_types=1);

use Core\Middleware\SecurityMiddleware;
use Helpers\Http\Flash;
use Helpers\Http\Request;
use Helpers\Http\Response;

describe('SecurityMiddleware', function () {
    test('passes GET requests without check', function () {
        $flash = Mockery::mock(Flash::class);
        $middleware = new SecurityMiddleware($flash);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('getRouteContext')->with('is_exclusive')->andReturn(false);
        $request->shouldReceive('isStateChanging')->once()->andReturn(false);

        $response = Mockery::mock(Response::class);

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });

    test('redirects on security failure for state-changing requests', function () {
        $flash = Mockery::mock(Flash::class);
        $flash->shouldReceive('error')->once()->with('Security check failed. Please try again.');

        $middleware = new SecurityMiddleware($flash);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('getRouteContext')->with('is_exclusive')->andReturn(false);
        $request->shouldReceive('isStateChanging')->once()->andReturn(true);
        $request->shouldReceive('isSecurityValid')->once()->andReturn(false);
        $request->shouldReceive('refererRoute')->once()->andReturn('/login');
        $request->shouldReceive('baseUrl')->once()->with('/login')->andReturn('http://localhost/login');

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('redirect')->once()->with('http://localhost/login')->andReturn($response);

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });

    test('passes on security success for state-changing requests', function () {
        $flash = Mockery::mock(Flash::class);
        $middleware = new SecurityMiddleware($flash);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('getRouteContext')->with('is_exclusive')->andReturn(false);
        $request->shouldReceive('isStateChanging')->once()->andReturn(true);
        $request->shouldReceive('isSecurityValid')->once()->andReturn(true);

        $response = Mockery::mock(Response::class);

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });

    test('skips security checks if route is exclusive', function () {
        $flash = Mockery::mock(Flash::class);
        $middleware = new SecurityMiddleware($flash);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('getRouteContext')->with('is_exclusive')->once()->andReturn(true);

        $response = Mockery::mock(Response::class);

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });
});
