<?php

declare(strict_types=1);

use Core\Ioc\Container;
use Core\Services\ConfigServiceInterface;
use Helpers\Http\Request;
use Security\Firewall\Drivers\MaintenanceFirewall;
use Testing\Fakes\RequestFake;

beforeEach(function () {
    $this->config = Mockery::mock(ConfigServiceInterface::class);
    $this->config->shouldReceive('get')->andReturn(null)->byDefault();
    $this->config->shouldReceive('get')->with('debug', Mockery::any())->andReturn(true)->byDefault();
    $this->config->shouldReceive('get')->with('debug')->andReturn(false)->byDefault();
    $this->config->shouldReceive('get')->with('timezone')->andReturn('UTC')->byDefault();
    $this->config->shouldReceive('get')->with('timezone', Mockery::any())->andReturn('UTC')->byDefault();

    // Baseline firewall config
    $this->config->shouldReceive('get')->with('firewall')->andReturn([
        'api-request' => [],
        'auth' => [],
        'maintenance' => [
            'enable' => false,
            'locked_resources' => [],
            'allow' => [
                'routes' => [],
                'ips' => ['ignore' => true, 'list' => []],
                'browsers' => ['ignore' => true, 'list' => []],
                'platforms' => ['ignore' => true, 'list' => []],
                'devices' => ['ignore' => true, 'list' => []],
            ]
        ]
    ])->byDefault();

    // API routes config
    $this->config->shouldReceive('get')->with('route.api')->andReturn([
        'any-route',
        'users',
        'dashboard',
        'auth/login'
    ])->byDefault();

    // Cache configs
    $this->config->shouldReceive('get')->with('cache.path')->andReturn('cache')->byDefault();
    $this->config->shouldReceive('get')->with('cache.path', Mockery::any())->andReturn('cache')->byDefault();
    $this->config->shouldReceive('get')->with('cache.prefix')->andReturn('')->byDefault();
    $this->config->shouldReceive('get')->with('cache.prefix', Mockery::any())->andReturn('')->byDefault();
    $this->config->shouldReceive('get')->with('cache.extension')->andReturn('cache')->byDefault();
    $this->config->shouldReceive('get')->with('cache.extension', Mockery::any())->andReturn('cache')->byDefault();

    Container::getInstance()->instance(ConfigServiceInterface::class, $this->config);
});



test('it blocks all requests when global maintenance is enabled', function () {
    $this->config->shouldReceive('get')->with('firewall')->andReturn([
        'api-request' => [],
        'auth' => [],
        'maintenance' => [
            'enable' => true,
            'allow' => [
                'routes' => [],
                'ips' => ['ignore' => true, 'list' => []],
                'browsers' => ['ignore' => true, 'list' => []],
                'platforms' => ['ignore' => true, 'list' => []],
                'devices' => ['ignore' => true, 'list' => []],
            ],
            'locked_resources' => []
        ]
    ]);

    $request = RequestFake::create('/any-route', 'GET', [], ['HTTP_ACCEPT' => 'application/json']);
    Container::getInstance()->instance(Request::class, $request);

    // Refresh firewall with new request
    $firewall = resolve(MaintenanceFirewall::class);
    $firewall->handle();

    expect($firewall->isBlocked())->toBeTrue();

    $response = $firewall->getResponse();
    expect($response['code'])->toBe(503);
    // When isApi is true, we expect JSON content with the message
    expect($response['content'])->toContain('Under maintenance');
});

test('it blocks only specific resources when locked_resources is set', function () {
    $this->config->shouldReceive('get')->with('firewall')->andReturn([
        'api-request' => [],
        'auth' => [],
        'maintenance' => [
            'enable' => false,
            'allow' => [
                'routes' => [],
                'ips' => ['ignore' => true, 'list' => []],
                'browsers' => ['ignore' => true, 'list' => []],
                'platforms' => ['ignore' => true, 'list' => []],
                'devices' => ['ignore' => true, 'list' => []],
            ],
            'locked_resources' => ['users']
        ]
    ]);

    // 1. Test locked resource (as API to get string message)
    $request = RequestFake::create('/users', 'GET', [], ['HTTP_ACCEPT' => 'application/json']);
    $request->setRouteContext('resource', 'users');
    Container::getInstance()->instance(Request::class, $request);
    $firewall = resolve(MaintenanceFirewall::class);
    $firewall->handle();
    expect($firewall->isBlocked())->toBeTrue();
    expect($firewall->getResponse()['content'])->toContain('The users module is temporarily under maintenance');

    // 2. Test unlocked resource
    $request = RequestFake::create('/dashboard', 'GET', [], ['HTTP_ACCEPT' => 'application/json']);
    $request->setRouteContext('resource', 'dashboard');
    Container::getInstance()->instance(Request::class, $request);
    $firewall = resolve(MaintenanceFirewall::class);
    $firewall->handle();
    expect($firewall->isBlocked())->toBeFalse();
});

test('it allows explicitly allowed routes during maintenance', function () {
    $this->config->shouldReceive('get')->with('firewall')->andReturn([
        'api-request' => [],
        'auth' => [],
        'maintenance' => [
            'enable' => true,
            'allow' => [
                'routes' => ['auth/login'],
                'ips' => ['ignore' => true, 'list' => []],
                'browsers' => ['ignore' => true, 'list' => []],
                'platforms' => ['ignore' => true, 'list' => []],
                'devices' => ['ignore' => true, 'list' => []],
            ],
            'locked_resources' => []
        ]
    ]);

    $request = RequestFake::create('auth/login', 'GET', [], ['HTTP_ACCEPT' => 'application/json']);
    Container::getInstance()->instance(Request::class, $request);
    $firewall = resolve(MaintenanceFirewall::class);
    $firewall->handle();

    expect($firewall->isBlocked())->toBeFalse();
});

test('it allows bypassed IPs during maintenance', function () {
    $this->config->shouldReceive('get')->with('firewall')->andReturn([
        'api-request' => [],
        'auth' => [],
        'maintenance' => [
            'enable' => true,
            'allow' => [
                'routes' => ['any-route'],
                'ips' => ['ignore' => false, 'list' => ['127.0.0.1']],
                'browsers' => ['ignore' => true, 'list' => []],
                'platforms' => ['ignore' => true, 'list' => []],
                'devices' => ['ignore' => true, 'list' => []],
            ],
            'locked_resources' => []
        ]
    ]);

    $request = RequestFake::create('/any-route', 'GET', [], ['HTTP_ACCEPT' => 'application/json']);
    Container::getInstance()->instance(Request::class, $request);
    $firewall = resolve(MaintenanceFirewall::class);
    $firewall->handle();

    expect($firewall->isBlocked())->toBeFalse();
});
