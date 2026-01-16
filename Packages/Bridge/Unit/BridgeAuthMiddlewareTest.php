<?php

declare(strict_types=1);

use Bridge\Middleware\BridgeAuthMiddleware;
use Bridge\TokenManager;
use Helpers\Http\Request;
use Helpers\Http\Response;

describe('BridgeAuthMiddleware', function () {
    beforeEach(function () {
        $this->tokenManager = Mockery::mock(TokenManager::class);
        $this->middleware = new BridgeAuthMiddleware($this->tokenManager);
        $this->request = Mockery::mock(Request::class);
        $this->response = Mockery::mock(Response::class);
        $this->next = fn ($req, $res) => 'next_called';
    });

    afterEach(function () {
        Mockery::close();
    });

    test('returns 401 if no token provided', function () {
        $this->request->shouldReceive('getBearerToken')->andReturn(null);

        $this->response->shouldReceive('json')->with(['error' => 'Unauthenticated'])->andReturnSelf();
        $this->response->shouldReceive('status')->with(401)->andReturn($this->response);

        $result = $this->middleware->handle($this->request, $this->response, $this->next);

        expect($result)->toBe($this->response);
    });

    test('returns 401 if token is invalid', function () {
        $token = 'invalid-token';
        $this->request->shouldReceive('getBearerToken')->andReturn($token);

        $this->tokenManager->shouldReceive('authenticate')
            ->with($token, Mockery::any())
            ->andReturn(null);

        $this->response->shouldReceive('json')->with(['error' => 'Invalid or expired token'])->andReturnSelf();
        $this->response->shouldReceive('status')->with(401)->andReturn($this->response);

        $result = $this->middleware->handle($this->request, $this->response, $this->next);

        expect($result)->toBe($this->response);
    });

    test('authenticates user and sets request attributes', function () {
        $token = 'valid-token';
        $user = Mockery::mock(Bridge\Contracts\TokenableInterface::class);
        $user->id = 1;

        $this->request->shouldReceive('getBearerToken')->andReturn($token);

        $this->tokenManager->shouldReceive('authenticate')
            ->with($token, Mockery::any())
            ->andReturn($user);

        // These are the methods we just added to Request
        $this->request->shouldReceive('setAuthenticatedUser')->with($user)->once();
        $this->request->shouldReceive('setAuthToken')->with($token)->once();

        $result = $this->middleware->handle($this->request, $this->response, $this->next);

        expect($result)->toBe('next_called');
    });
});
