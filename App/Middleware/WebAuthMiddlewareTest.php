<?php

declare(strict_types=1);

use App\Middleware\Web\WebAuthMiddleware;
use App\Services\Auth\Interfaces\AuthServiceInterface;
use Core\Services\ConfigServiceInterface;
use Helpers\Http\Request;
use Helpers\Http\Response;

describe('WebAuthMiddleware', function () {

    test('bypasses auth for exempted routes', function () {
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldNotReceive('isAuthenticated');
        $config = Mockery::mock(ConfigServiceInterface::class);

        $middleware = new WebAuthMiddleware($auth, $config);
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
        $auth->shouldReceive('viaGuard')->with('web')->andReturnSelf();
        $auth->shouldReceive('isAuthenticated')->andReturn(true);
        $auth->shouldReceive('isAuthorized')->with('home')->andReturn(true);
        $auth->shouldReceive('user')->andReturn($user);
        $config = Mockery::mock(ConfigServiceInterface::class);

        $middleware = new WebAuthMiddleware($auth, $config);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('routeShouldBypassAuth')->andReturn(false);
        $request->shouldReceive('getRouteContext')->with('guards')->andReturn(['web']);
        $request->shouldReceive('getRouteContext')->with('login_route')->andReturn(null);
        $request->shouldReceive('route')->andReturn('home');
        $request->shouldReceive('setAuthenticatedUser')->with($user)->once();
        $request->shouldReceive('setRouteContext')->with('auth_guard', 'web')->once();
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
        $auth->shouldReceive('viaGuard')->with('web')->andReturnSelf();
        $auth->shouldReceive('isAuthenticated')->andReturn(false);
        $auth->shouldReceive('logout')->once();
        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('auth.guards.web.login_route')->andReturn(null);

        $middleware = new WebAuthMiddleware($auth, $config);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('routeShouldBypassAuth')->andReturn(false);
        $request->shouldReceive('getRouteContext')->with('guards')->andReturn(['web']);
        $request->shouldReceive('getRouteContext')->with('login_route')->andReturn(null);
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
        $auth->shouldReceive('viaGuard')->with('web')->andReturnSelf();
        $auth->shouldReceive('isAuthenticated')->andReturn(true);
        $auth->shouldReceive('isAuthorized')->with('admin')->andReturn(false);
        $auth->shouldReceive('logout')->once();
        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('auth.guards.web.login_route')->andReturn(null);

        $middleware = new WebAuthMiddleware($auth, $config);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('routeShouldBypassAuth')->andReturn(false);
        $request->shouldReceive('getRouteContext')->with('guards')->andReturn(['web']);
        $request->shouldReceive('getRouteContext')->with('login_route')->andReturn(null);
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

    test('redirects to custom login_route if defined', function () {
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldReceive('viaGuard')->with('admin')->andReturnSelf();
        $auth->shouldReceive('isAuthenticated')->andReturn(false);
        $auth->shouldReceive('logout')->once();
        $config = Mockery::mock(ConfigServiceInterface::class);

        $middleware = new WebAuthMiddleware($auth, $config);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('routeShouldBypassAuth')->andReturn(false);
        $request->shouldReceive('getRouteContext')->with('guards')->andReturn(['admin']);
        $request->shouldReceive('getRouteContext')->with('login_route')->andReturn('admin.login');
        $request->shouldReceive('route')->andReturn('admin.dashboard');
        $request->shouldReceive('fullRouteByName')->with('admin.login')->andReturn('/admin/login');

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('redirect')->with('/admin/login')->once()->andReturnSelf();

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });
});
