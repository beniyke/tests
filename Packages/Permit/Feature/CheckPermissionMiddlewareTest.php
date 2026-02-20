<?php

declare(strict_types=1);

namespace Tests\Packages\Permit\Feature;

use App\Models\User;
use Core\Services\ConfigServiceInterface;
use Helpers\Http\Flash;
use Helpers\Http\Request;
use Helpers\Http\Response;
use Mockery;
use Permit\Middleware\CheckPermissionMiddleware;

describe('CheckPermissionMiddleware', function () {
    beforeEach(function () {
        $this->config = Mockery::mock(ConfigServiceInterface::class);
        $this->flash = Mockery::mock(Flash::class);
        $this->middleware = new CheckPermissionMiddleware($this->config, $this->flash);
        $this->request = Mockery::mock(Request::class);
        $this->response = Mockery::mock(Response::class);
        $this->user = Mockery::mock(User::class);
        $this->next = fn ($req, $res) => $res;

        $this->config->shouldReceive('get')
            ->with('permit.smart_middleware.enabled')
            ->andReturn(true)
            ->byDefault();

        $this->config->shouldReceive('get')
            ->with('permit.smart_middleware.action_map', [])
            ->andReturn([
                'create' => 'create',
                'store' => 'create',
                'destroy' => 'delete',
                'index' => 'manage',
            ])
            ->byDefault();
    });

    afterEach(function () {
        Mockery::close();
    });

    it('passes through if no user is authenticated', function () {
        $this->request->shouldReceive('user')->andReturn(null);

        $result = $this->middleware->handle($this->request, $this->response, $this->next);

        expect($result)->toBe($this->response);
    });

    it('passes through if smart middleware is disabled', function () {
        $this->request->shouldReceive('user')->andReturn($this->user);
        $this->config->shouldReceive('get')
            ->with('permit.smart_middleware.enabled')
            ->andReturn(false);

        $result = $this->middleware->handle($this->request, $this->response, $this->next);

        expect($result)->toBe($this->response);
    });

    it('allows access if user has permission', function () {
        $this->request->shouldReceive('user')->andReturn($this->user);
        $this->request->shouldReceive('segments')->andReturn(['account', 'user', 'create']);

        $this->user->shouldReceive('hasPermission')
            ->with('users.create')
            ->andReturn(true);

        $result = $this->middleware->handle($this->request, $this->response, $this->next);

        expect($result)->toBe($this->response);
    });

    it('denies access if user lacks permission (Web)', function () {
        $this->request->shouldReceive('user')->andReturn($this->user);
        $this->request->shouldReceive('segments')->andReturn(['account', 'role', 'destroy']);
        $this->request->shouldReceive('isAjax')->andReturn(false);
        $this->request->shouldReceive('wantsJson')->andReturn(false);
        $this->request->shouldReceive('routeIsApi')->andReturn(false);
        $this->request->shouldReceive('referer')->andReturn(null);
        $this->request->shouldReceive('fullRouteByName')->with('home')->andReturn('/home');

        $this->user->shouldReceive('hasPermission')
            ->with('roles.delete')
            ->andReturn(false);

        $this->flash->shouldReceive('error')
            ->once()
            ->with('Unauthorized access.');

        $this->response->shouldReceive('redirect')
            ->once()
            ->with('/home')
            ->andReturnSelf();

        $result = $this->middleware->handle($this->request, $this->response, $this->next);

        expect($result)->toBe($this->response);
    });

    it('denies access if user lacks permission (API)', function () {
        $this->request->shouldReceive('user')->andReturn($this->user);
        $this->request->shouldReceive('segments')->andReturn(['api', 'v1', 'users', 'index']);
        $this->request->shouldReceive('isAjax')->andReturn(false);
        $this->request->shouldReceive('wantsJson')->andReturn(false);
        $this->request->shouldReceive('routeIsApi')->andReturn(true);

        $this->user->shouldReceive('hasPermission')
            ->with('users.manage')
            ->andReturn(false);

        $this->response->shouldReceive('json')
            ->once()
            ->with([
                'error' => 'Unauthorized',
                'message' => 'You do not have the required permission.',
            ], 403)
            ->andReturnSelf();

        $result = $this->middleware->handle($this->request, $this->response, $this->next);

        expect($result)->toBe($this->response);
    });
});
