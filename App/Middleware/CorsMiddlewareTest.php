<?php

declare(strict_types=1);

use App\Middleware\Api\CorsMiddleware;
use Core\Services\ConfigServiceInterface;
use Helpers\Http\Request;
use Helpers\Http\Response;

describe('CorsMiddleware', function () {

    test('skips CORS when disabled', function () {
        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('cors.enabled', false)->andReturn(false);

        $middleware = new CorsMiddleware($config);
        $request = Mockery::mock(Request::class);
        $response = Mockery::mock(Response::class);
        $response->shouldNotReceive('header');

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });

    test('allows all origins with wildcard', function () {
        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('cors.enabled', false)->andReturn(true);
        $config->shouldReceive('get')->with('cors.allowed_origins', ['*'])->andReturn(['*']);
        $config->shouldReceive('get')->with('cors.allow_credentials', false)->andReturn(false);
        $config->shouldReceive('get')->with('cors.exposed_headers', [])->andReturn([]);

        $middleware = new CorsMiddleware($config);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('header')->with('Origin')->andReturn('https://example.com');
        $request->shouldReceive('isOptions')->andReturn(false);

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('header')->with(['Access-Control-Allow-Origin' => '*'])->once()->andReturnSelf();

        $next = function ($req, $res) {
            return $res;
        };

        $middleware->handle($request, $response, $next);
    });

    test('handles preflight OPTIONS request', function () {
        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('cors.enabled', false)->andReturn(true);
        $config->shouldReceive('get')->with('cors.allowed_origins', ['*'])->andReturn(['https://example.com']);
        $config->shouldReceive('get')->with('cors.allowed_methods', Mockery::any())->andReturn(['GET', 'POST', 'PUT', 'DELETE']);
        $config->shouldReceive('get')->with('cors.allowed_headers', Mockery::any())->andReturn(['Content-Type', 'Authorization']);
        $config->shouldReceive('get')->with('cors.max_age', 86400)->andReturn(3600);
        $config->shouldReceive('get')->with('cors.allow_credentials', false)->andReturn(true);

        $middleware = new CorsMiddleware($config);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('header')->with('Origin')->andReturn('https://example.com');
        $request->shouldReceive('isOptions')->andReturn(true);

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('header')->with([
            'Access-Control-Allow-Origin' => 'https://example.com',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
            'Access-Control-Max-Age' => '3600',
            'Access-Control-Allow-Credentials' => 'true',
        ])->once()->andReturnSelf();
        $response->shouldReceive('status')->with(204)->once()->andReturnSelf();

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });

    test('allows specific origin', function () {
        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('cors.enabled', false)->andReturn(true);
        $config->shouldReceive('get')->with('cors.allowed_origins', ['*'])->andReturn(['https://example.com', 'https://app.example.com']);
        $config->shouldReceive('get')->with('cors.allow_credentials', false)->andReturn(false);
        $config->shouldReceive('get')->with('cors.exposed_headers', [])->andReturn([]);

        $middleware = new CorsMiddleware($config);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('header')->with('Origin')->andReturn('https://app.example.com');
        $request->shouldReceive('isOptions')->andReturn(false);

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('header')->with(['Access-Control-Allow-Origin' => 'https://app.example.com'])->once()->andReturnSelf();

        $next = function ($req, $res) {
            return $res;
        };

        $middleware->handle($request, $response, $next);
    });

    test('allows wildcard subdomain', function () {
        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('cors.enabled', false)->andReturn(true);
        $config->shouldReceive('get')->with('cors.allowed_origins', ['*'])->andReturn(['*.example.com']);
        $config->shouldReceive('get')->with('cors.allow_credentials', false)->andReturn(false);
        $config->shouldReceive('get')->with('cors.exposed_headers', [])->andReturn([]);

        $middleware = new CorsMiddleware($config);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('header')->with('Origin')->andReturn('https://api.example.com');
        $request->shouldReceive('isOptions')->andReturn(false);

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('header')->with(['Access-Control-Allow-Origin' => 'https://api.example.com'])->once()->andReturnSelf();

        $next = function ($req, $res) {
            return $res;
        };

        $middleware->handle($request, $response, $next);
    });

    test('rejects disallowed origin', function () {
        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('cors.enabled', false)->andReturn(true);
        $config->shouldReceive('get')->with('cors.allowed_origins', ['*'])->andReturn(['https://example.com']);

        $middleware = new CorsMiddleware($config);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('header')->with('Origin')->andReturn('https://malicious.com');

        $response = Mockery::mock(Response::class);
        $response->shouldNotReceive('header');

        $next = function ($req, $res) {
            return $res;
        };

        $middleware->handle($request, $response, $next);
    });
});
