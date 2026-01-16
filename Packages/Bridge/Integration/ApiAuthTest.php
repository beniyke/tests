<?php

declare(strict_types=1);

namespace Tests\Packages\Bridge\Integration;

use Bridge\ApiAuth\ApiTokenValidatorService;
use Bridge\ApiAuth\Validators\AuthTokenValidator;
use Bridge\Contracts\TokenableInterface;
use Bridge\Models\ApiKey;
use Core\Ioc\Container;
use Core\Services\ConfigServiceInterface;
use Helpers\Http\Request;
use Mockery;

describe('API Authentication System', function () {

    beforeEach(function () {
        $this->bootPackage('Bridge', runMigrations: true);

        $this->container = Container::getInstance();
        $this->config = Mockery::mock(ConfigServiceInterface::class);
        $this->request = Mockery::mock(Request::class);
    });

    afterEach(function () {
        Mockery::close();
    });

    test('static token validation', function () {
        $service = new ApiTokenValidatorService($this->request, $this->config, $this->container);

        $this->request->shouldReceive('route')->andReturn('api/static');
        $this->request->shouldReceive('getAuthToken')->andReturn('secret-token');

        $this->config->shouldReceive('get')->with('api.api/static')->andReturn([
            'type' => 'static',
            'token' => 'secret-token',
        ]);

        $user = $service->getAuthenticatedUser();

        expect($user)->not->toBeNull();
        expect($user->token)->toBe('secret-token');
        expect($user->type)->toBe('static');
    });

    test('dynamic token validation', function () {
        $rawKey = 'dynamic-key-' . time();
        $hashedKey = hash('sha256', $rawKey);

        ApiKey::create([
            'name' => 'Test Key',
            'key' => $hashedKey,
        ]);

        $service = new ApiTokenValidatorService($this->request, $this->config, $this->container);

        $this->request->shouldReceive('route')->andReturn('api/dynamic');
        $this->request->shouldReceive('getAuthToken')->andReturn($rawKey);

        $this->config->shouldReceive('get')->with('api.api/dynamic')->andReturn([
            'type' => 'dynamic',
        ]);

        $user = $service->getAuthenticatedUser();

        expect($user)->not->toBeNull();
        expect($user->name)->toBe('Test Key');

        $key = ApiKey::query()->where('key', '=', $hashedKey)->first();
        expect($key->last_used_at)->not->toBeNull();
    });

    test('auth token validation (bridge)', function () {
        $authValidator = Mockery::mock(AuthTokenValidator::class);
        $this->container->instance(AuthTokenValidator::class, $authValidator);

        $service = new ApiTokenValidatorService($this->request, $this->config, $this->container);

        $this->request->shouldReceive('route')->andReturn('api/auth');
        $this->request->shouldReceive('getAuthToken')->andReturn('auth-token');

        $this->config->shouldReceive('get')->with('api.api/auth')->andReturn([
            'type' => 'auth',
            'abilities' => ['*'],
        ]);

        $mockUser = Mockery::mock(TokenableInterface::class);
        $mockUser->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $mockUser->shouldReceive('getAttribute')->with('name')->andReturn('User');

        $authValidator->shouldReceive('validate')
            ->with('auth-token', ['*'])
            ->andReturn($mockUser);

        $user = $service->getAuthenticatedUser();

        expect($user)->toBe($mockUser);
    });

    test('invalid token returns null', function () {
        $service = new ApiTokenValidatorService($this->request, $this->config, $this->container);

        $this->request->shouldReceive('route')->andReturn('api/static');
        $this->request->shouldReceive('getAuthToken')->andReturn('wrong-token');

        $this->config->shouldReceive('get')->with('api.api/static')->andReturn([
            'type' => 'static',
            'token' => 'secret-token',
        ]);

        $user = $service->getAuthenticatedUser();

        expect($user)->toBeNull();
    });
});
