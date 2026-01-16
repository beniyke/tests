<?php

declare(strict_types=1);

use App\Middleware\Api\ApiAuthMiddleware;
use App\Services\Auth\Interfaces\AuthServiceInterface;
use Helpers\Http\Request;
use Helpers\Http\Response;

describe('ApiAuthMiddleware', function () {

    test('allows authenticated requests', function () {
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldReceive('isAuthenticated')->andReturn(true);

        $middleware = new ApiAuthMiddleware($auth);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('routeShouldBypassAuth')->andReturn(false);

        $response = Mockery::mock(Response::class);

        $next = function ($req) use ($response) {
            return $response;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });

    test('blocks unauthenticated requests', function () {
        $auth = Mockery::mock(AuthServiceInterface::class);
        $auth->shouldReceive('isAuthenticated')->andReturn(false);

        $middleware = new ApiAuthMiddleware($auth);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('routeShouldBypassAuth')->andReturn(false);

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('header')->with(['Content-Type' => 'application/json'])->once()->andReturnSelf();
        $response->shouldReceive('status')->with(401)->once()->andReturnSelf();
        $response->shouldReceive('body')->once()->andReturnSelf();

        $next = function ($req) use ($response) {
            return $response;
        };

        $result = $middleware->handle($request, $response, $next);
        expect($result)->toBe($response);
    });
});
