<?php

declare(strict_types=1);

use App\Middleware\Web\SecurityHeadersMiddleware;
use Core\Services\ConfigServiceInterface;
use Helpers\Http\Request;
use Helpers\Http\Response;

describe('SecurityHeadersMiddleware', function () {

    test('adds security headers when enabled', function () {
        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('security_headers.enabled', true)->andReturn(true);
        $config->shouldReceive('get')->with('security_headers.x_frame_options', 'SAMEORIGIN')->andReturn('DENY');
        $config->shouldReceive('get')->with('security_headers.x_content_type_options', 'nosniff')->andReturn('nosniff');
        $config->shouldReceive('get')->with('security_headers.x_xss_protection', '1; mode=block')->andReturn('1; mode=block');
        $config->shouldReceive('get')->with('security_headers.referrer_policy', 'strict-origin-when-cross-origin')->andReturn('no-referrer');
        $config->shouldReceive('get')->with('security_headers.permissions_policy', Mockery::any())->andReturn([]);
        $config->shouldReceive('get')->with('security_headers.hsts_enabled', true)->andReturn(false);
        $config->shouldReceive('get')->with('security_headers.fetch_metadata.enabled', false)->andReturn(false);
        $config->shouldReceive('get')->with('security_headers.content_security_policy')->andReturn(null);

        $middleware = new SecurityHeadersMiddleware($config);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('isSecure')->andReturn(false);

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('header')->with([
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'no-referrer',
        ])->once()->andReturnSelf();

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });

    test('skips headers when disabled', function () {
        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('security_headers.fetch_metadata.enabled', false)->andReturn(false);
        $config->shouldReceive('get')->with('security_headers.enabled', true)->andReturn(false);

        $middleware = new SecurityHeadersMiddleware($config);
        $request = Mockery::mock(Request::class);
        $response = Mockery::mock(Response::class);
        $response->shouldNotReceive('header');

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });

    test('adds HSTS header on secure connections', function () {
        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('security_headers.enabled', true)->andReturn(true);
        $config->shouldReceive('get')->with('security_headers.x_frame_options', 'SAMEORIGIN')->andReturn('SAMEORIGIN');
        $config->shouldReceive('get')->with('security_headers.x_content_type_options', 'nosniff')->andReturn('nosniff');
        $config->shouldReceive('get')->with('security_headers.x_xss_protection', '1; mode=block')->andReturn('1; mode=block');
        $config->shouldReceive('get')->with('security_headers.referrer_policy', 'strict-origin-when-cross-origin')->andReturn('strict-origin-when-cross-origin');
        $config->shouldReceive('get')->with('security_headers.permissions_policy', Mockery::any())->andReturn([]);
        $config->shouldReceive('get')->with('security_headers.hsts_enabled', true)->andReturn(true);
        $config->shouldReceive('get')->with('security_headers.hsts_max_age', 31536000)->andReturn(31536000);
        $config->shouldReceive('get')->with('security_headers.hsts_include_subdomains', true)->andReturn(true);
        $config->shouldReceive('get')->with('security_headers.hsts_preload', false)->andReturn(true);
        $config->shouldReceive('get')->with('security_headers.fetch_metadata.enabled', false)->andReturn(false);
        $config->shouldReceive('get')->with('security_headers.content_security_policy')->andReturn(null);

        $middleware = new SecurityHeadersMiddleware($config);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('isSecure')->andReturn(true);

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('header')->andReturnSelf();

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });

    test('adds CSP header when configured', function () {
        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('security_headers.enabled', true)->andReturn(true);
        $config->shouldReceive('get')->with('security_headers.x_frame_options', 'SAMEORIGIN')->andReturn('SAMEORIGIN');
        $config->shouldReceive('get')->with('security_headers.x_content_type_options', 'nosniff')->andReturn('nosniff');
        $config->shouldReceive('get')->with('security_headers.x_xss_protection', '1; mode=block')->andReturn('1; mode=block');
        $config->shouldReceive('get')->with('security_headers.referrer_policy', 'strict-origin-when-cross-origin')->andReturn('strict-origin-when-cross-origin');
        $config->shouldReceive('get')->with('security_headers.permissions_policy', Mockery::any())->andReturn([]);
        $config->shouldReceive('get')->with('security_headers.hsts_enabled', true)->andReturn(false);
        $config->shouldReceive('get')->with('security_headers.fetch_metadata.enabled', false)->andReturn(false);
        $config->shouldReceive('get')->with('security_headers.content_security_policy')->andReturn("default-src 'self'");

        $middleware = new SecurityHeadersMiddleware($config);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('isSecure')->andReturn(false);

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('header')->andReturnSelf();

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });
});
