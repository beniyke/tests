<?php

declare(strict_types=1);

use App\Models\User;
use Bridge\ApiAuth\ApiTokenValidatorService;
use Bridge\ApiAuth\Validators\DynamicTokenValidator;
use Bridge\ApiAuth\Validators\StaticTokenValidator;
use Core\Ioc\ContainerInterface;
use Core\Services\ConfigServiceInterface;
use Core\Support\Adapters\Interfaces\SapiInterface;
use Helpers\Http\Request;
use Helpers\Http\Session;
use Helpers\Http\UserAgent;

describe('ApiTokenValidatorService Integration', function () {
    afterEach(function () {
        Mockery::close();
    });

    test('getAuthenticatedUser returns null when config is missing', function () {
        $reqConfig = Mockery::mock(ConfigServiceInterface::class);
        $sapi = Mockery::mock(SapiInterface::class);
        $session = Mockery::mock(Session::class);
        $agent = Mockery::mock(UserAgent::class);

        $sapi->shouldReceive('isPhpServer')->andReturn(false);
        $sapi->shouldReceive('isCli')->andReturn(true);
        $agent->shouldReceive('ip')->andReturn('127.0.0.1');
        $agent->shouldReceive('agentString')->andReturn('TestAgent');

        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/api/v1/users',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'HTTP_HOST' => 'example.com',
            'HTTP_AUTHORIZATION' => 'some-token',
            'PHP_SELF' => '/index.php',
        ];

        $request = new Request(
            $server,
            [],
            [],
            [],
            [],
            $reqConfig,
            $sapi,
            $session,
            $agent
        );

        $reqConfig->shouldReceive('get')->with('route.default')->andReturn('Home');

        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with(Mockery::type('string'))->andReturn(null);

        $container = Mockery::mock(ContainerInterface::class);

        $service = new ApiTokenValidatorService(
            $request,
            $config,
            $container
        );

        expect($service->getAuthenticatedUser())->toBeNull();
    });

    test('getAuthenticatedUser returns null when token is missing', function () {
        $reqConfig = Mockery::mock(ConfigServiceInterface::class);
        $sapi = Mockery::mock(SapiInterface::class);
        $session = Mockery::mock(Session::class);
        $agent = Mockery::mock(UserAgent::class);

        $sapi->shouldReceive('isPhpServer')->andReturn(false);
        $sapi->shouldReceive('isCli')->andReturn(true);
        $agent->shouldReceive('ip')->andReturn('127.0.0.1');
        $agent->shouldReceive('agentString')->andReturn('TestAgent');

        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/api/v1/users',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'HTTP_HOST' => 'example.com',
            'PHP_SELF' => '/index.php',
        ];

        $request = new Request(
            $server,
            [],
            [],
            [],
            [],
            $reqConfig,
            $sapi,
            $session,
            $agent
        );

        $reqConfig->shouldReceive('get')->with('route.default')->andReturn('Home');

        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->andReturn(['type' => 'static']);

        $container = Mockery::mock(ContainerInterface::class);

        $service = new ApiTokenValidatorService(
            $request,
            $config,
            $container
        );

        expect($service->getAuthenticatedUser())->toBeNull();
    });

    test('getAuthenticatedUser validates static token', function () {
        $reqConfig = Mockery::mock(ConfigServiceInterface::class);
        $sapi = Mockery::mock(SapiInterface::class);
        $session = Mockery::mock(Session::class);
        $agent = Mockery::mock(UserAgent::class);

        $sapi->shouldReceive('isPhpServer')->andReturn(false);
        $sapi->shouldReceive('isCli')->andReturn(true);
        $agent->shouldReceive('ip')->andReturn('127.0.0.1');
        $agent->shouldReceive('agentString')->andReturn('TestAgent');

        $user = Mockery::mock(User::class);
        $validator = Mockery::mock(StaticTokenValidator::class);

        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/api/v1/users',
            'PHP_SELF' => '/index.php',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'HTTP_HOST' => 'example.com',
            'HTTP_AUTHORIZATION' => 'provided-token',
        ];

        $request = new Request(
            $server,
            [],
            [],
            [],
            [],
            $reqConfig,
            $sapi,
            $session,
            $agent
        );

        $reqConfig->shouldReceive('get')->with('route.default')->andReturn('Home');

        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with(Mockery::pattern('/^api\./'))->andReturn([
            'type' => 'static',
            'token' => 'secret-token',
        ]);

        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->with(StaticTokenValidator::class)->once()->andReturn($validator);
        $validator->shouldReceive('validate')->with('provided-token', 'secret-token')->once()->andReturn($user);

        $service = new ApiTokenValidatorService(
            $request,
            $config,
            $container
        );

        expect($service->getAuthenticatedUser())->toBe($user);
    });

    test('getAuthenticatedUser validates dynamic token', function () {
        $reqConfig = Mockery::mock(ConfigServiceInterface::class);
        $sapi = Mockery::mock(SapiInterface::class);
        $session = Mockery::mock(Session::class);
        $agent = Mockery::mock(UserAgent::class);

        $sapi->shouldReceive('isPhpServer')->andReturn(false);
        $sapi->shouldReceive('isCli')->andReturn(true);
        $agent->shouldReceive('ip')->andReturn('127.0.0.1');
        $agent->shouldReceive('agentString')->andReturn('TestAgent');

        $user = Mockery::mock(User::class);
        $validator = Mockery::mock(DynamicTokenValidator::class);
        $dynamicService = new stdClass();

        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/api/v1/users',
            'PHP_SELF' => '/index.php',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'HTTP_HOST' => 'example.com',
            'HTTP_AUTHORIZATION' => 'provided-token',
        ];

        $request = new Request(
            $server,
            [],
            [],
            [],
            [],
            $reqConfig,
            $sapi,
            $session,
            $agent
        );

        $reqConfig->shouldReceive('get')->with('route.default')->andReturn('Home');

        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with(Mockery::pattern('/^api\./'))->andReturn([
            'type' => 'dynamic',
            'service' => 'App\Services\SomeService',
        ]);

        $container = Mockery::mock(ContainerInterface::class);
        // $container->shouldReceive('get')->with('App\Services\SomeService')->once()->andReturn($dynamicService); // No longer needed
        $container->shouldReceive('get')->with(DynamicTokenValidator::class)->once()->andReturn($validator);
        $validator->shouldReceive('validate')->with('provided-token')->once()->andReturn($user);

        $service = new ApiTokenValidatorService(
            $request,
            $config,
            $container
        );

        expect($service->getAuthenticatedUser())->toBe($user);
    });

    test('getAuthenticatedUser returns null for unknown type', function () {
        $reqConfig = Mockery::mock(ConfigServiceInterface::class);
        $sapi = Mockery::mock(SapiInterface::class);
        $session = Mockery::mock(Session::class);
        $agent = Mockery::mock(UserAgent::class);

        $sapi->shouldReceive('isPhpServer')->andReturn(false);
        $sapi->shouldReceive('isCli')->andReturn(true);
        $agent->shouldReceive('ip')->andReturn('127.0.0.1');
        $agent->shouldReceive('agentString')->andReturn('TestAgent');

        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/api/v1/users',
            'PHP_SELF' => '/index.php',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'HTTP_HOST' => 'example.com',
            'HTTP_AUTHORIZATION' => 'provided-token',
            'PHP_SELF' => '/index.php',
        ];

        $request = new Request(
            $server,
            [],
            [],
            [],
            [],
            $reqConfig,
            $sapi,
            $session,
            $agent
        );

        $reqConfig->shouldReceive('get')->with('route.default')->andReturn('Home');

        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with(Mockery::pattern('/^api\./'))->andReturn([
            'type' => 'unknown',
        ]);

        $container = Mockery::mock(ContainerInterface::class);

        $service = new ApiTokenValidatorService(
            $request,
            $config,
            $container
        );

        expect($service->getAuthenticatedUser())->toBeNull();
    });
});
