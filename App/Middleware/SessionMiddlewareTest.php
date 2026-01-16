<?php

declare(strict_types=1);

use App\Middleware\Web\SessionMiddleware;
use Core\Services\ConfigServiceInterface;
use Helpers\Http\Request;
use Helpers\Http\Response;
use Helpers\Http\Session;

describe('SessionMiddleware', function () {

    test('regenerates session when configured', function () {
        $session = Mockery::mock(Session::class);
        $session->shouldReceive('periodicRegenerate')->once();

        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('session.regenerate')->andReturn(true);

        $middleware = new SessionMiddleware($session, $config);
        $request = Mockery::mock(Request::class);
        $response = Mockery::mock(Response::class);

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });

    test('skips regeneration when disabled', function () {
        $session = Mockery::mock(Session::class);
        $session->shouldNotReceive('periodicRegenerate');

        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('session.regenerate')->andReturn(false);

        $middleware = new SessionMiddleware($session, $config);
        $request = Mockery::mock(Request::class);
        $response = Mockery::mock(Response::class);

        $next = function ($req, $res) {
            return $res;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });
});
