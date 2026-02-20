<?php

declare(strict_types=1);

namespace Tests\System\Feature;

use Core\Ioc\Container;
use Core\Services\ConfigServiceInterface;
use Core\Support\Adapters\Interfaces\SapiInterface;
use Helpers\Http\Request;
use Helpers\Http\Session;
use Helpers\Http\UserAgent;
use Mockery;
use Security\Firewall\Drivers\MaintenanceFirewall;

beforeEach(function () {
    $this->config = mock(ConfigServiceInterface::class);
    $this->config->shouldReceive('get')->andReturn(null)->byDefault();
    $this->config->shouldReceive('get')->with('debug', Mockery::any())->andReturn(false)->byDefault();
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

function createMaintenanceMockRequest(string $uri = '/', array $context = [], bool $isApi = false): Request
{
    $server = [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => $uri,
        'REMOTE_ADDR' => '127.0.0.1',
        'PHP_SELF' => '/index.php' . $uri,
        'SCRIPT_NAME' => '/index.php',
    ];

    if ($isApi) {
        $server['HTTP_ACCEPT'] = 'application/json';
    }

    $_SERVER = $server;

    $request = Request::createFromGlobals(
        resolve(ConfigServiceInterface::class),
        resolve(SapiInterface::class),
        resolve(Session::class),
        resolve(UserAgent::class)
    );

    foreach ($context as $key => $value) {
        $request->setRouteContext($key, $value);
    }

    return $request;
}

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

    $request = createMaintenanceMockRequest('/any-route', [], true);
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
    $request = createMaintenanceMockRequest('/users', ['resource' => 'users'], true);
    Container::getInstance()->instance(Request::class, $request);
    $firewall = resolve(MaintenanceFirewall::class);
    $firewall->handle();
    expect($firewall->isBlocked())->toBeTrue();
    expect($firewall->getResponse()['content'])->toContain('The users module is temporarily under maintenance');

    // 2. Test unlocked resource
    $request = createMaintenanceMockRequest('/dashboard', ['resource' => 'dashboard'], true);
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

    $request = createMaintenanceMockRequest('auth/login', [], true);
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

    $request = createMaintenanceMockRequest('/any-route', [], true);
    Container::getInstance()->instance(Request::class, $request);
    $firewall = resolve(MaintenanceFirewall::class);
    $firewall->handle();

    expect($firewall->isBlocked())->toBeFalse();
});
