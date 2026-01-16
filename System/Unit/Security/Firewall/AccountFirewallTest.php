<?php

declare(strict_types=1);

use Core\Ioc\Container;
use Core\Ioc\ContainerInterface;
use Core\Services\ConfigServiceInterface;
use Defer\DeferrerInterface;
use Helpers\File\Contracts\CacheInterface;
use Helpers\Http\Flash;
use Helpers\Http\Request;
use Helpers\Http\UserAgent;
use Notify\Notifier;
use Security\Firewall\Drivers\AccountFirewall;
use Security\Firewall\Throttling\Throttler;

beforeEach(function () {
    $this->config = Mockery::mock(ConfigServiceInterface::class);
    $this->cache = Mockery::mock(CacheInterface::class);
    $this->agent = Mockery::mock(UserAgent::class);
    $this->notifier = Mockery::mock(Notifier::class);
    $this->request = Mockery::mock(Request::class);
    $this->flash = Mockery::mock(Flash::class);
    $this->throttler = Mockery::mock(Throttler::class);
    $this->deferrer = Mockery::mock(DeferrerInterface::class);
    $this->container = Mockery::mock(ContainerInterface::class);

    // Setup Container for global helpers
    $containerInstance = Container::getInstance();
    $containerInstance->instance(ContainerInterface::class, $this->container);

    // Bind DeferrerInterface for defer() helper
    $this->container->shouldReceive('get')->with(DeferrerInterface::class)->andReturn($this->deferrer);

    // Setup Deferrer mock
    $this->deferrer->shouldReceive('name')->andReturn($this->deferrer);
    $this->deferrer->shouldReceive('push');

    $this->firewall = new AccountFirewall(
        $this->config,
        $this->cache,
        $this->agent,
        $this->notifier,
        $this->request,
        $this->flash,
        $this->throttler
    );
});

describe('AccountFirewall', function () {
    test('callback registers a callback', function () {
        $callback = function () {};
        $this->firewall->callback($callback);

        // Access protected property via reflection to verify
        $reflection = new ReflectionClass($this->firewall);
        $property = $reflection->getProperty('callback');
        $property->setAccessible(true);

        expect($property->getValue($this->firewall))->toBe($callback);
    });

    test('user sets the user array', function () {
        $user = ['id' => 1, 'name' => 'John'];
        $this->firewall->user($user);

        $reflection = new ReflectionClass($this->firewall);
        $property = $reflection->getProperty('user');
        $property->setAccessible(true);

        expect($property->getValue($this->firewall))->toBe($user);
    });

    test('user throws exception if id missing', function () {
        $this->firewall->user(['name' => 'John']);
    })->throws(InvalidArgumentException::class, "User array must contain an 'id' key");

    test('handle skips if account firewall disabled', function () {
        $this->config->shouldReceive('get')->with('firewall')->andReturn(['account' => ['enable' => false]]);

        $this->firewall->handle();

        expect($this->firewall->isBlocked())->toBeFalse();
    });

    test('handle throws exception if user not set', function () {
        $this->config->shouldReceive('get')->with('firewall')->andReturn(['account' => ['enable' => true]]);

        $this->firewall->handle();
    })->throws(RuntimeException::class, 'AccountFirewall requires calling user() before handle()');

    test('handle blocks user when throttled', function () {
        $user = ['id' => 123];
        $this->firewall->user($user);

        $this->config->shouldReceive('get')->with('firewall')->andReturn([
            'account' => [
                'enable' => true,
                'response' => 'Blocked for {duration}',
            ],
            'notification' => ['mail' => ['status' => false]],
        ]);

        $this->agent->shouldReceive('ip')->andReturn('127.0.0.1');
        $this->agent->shouldReceive('device')->andReturn('Desktop');
        $this->agent->shouldReceive('platform')->andReturn('Windows');
        $this->agent->shouldReceive('browser')->andReturn('Chrome');
        $this->agent->shouldReceive('version')->andReturn('90');

        $this->throttler->shouldReceive('attempt')->andReturn([
            'is_blocked' => true,
            'time_remaining' => 3665, // 1 hour, 1 minute, 5 seconds
        ]);

        $this->flash->shouldReceive('error')->with('Blocked for 1 hour, 1 minute and 5 seconds');

        // Audit trail mocks
        $this->cache->shouldReceive('withPath')->andReturn($this->cache);
        $this->cache->shouldReceive('has')->andReturn(false);
        $this->cache->shouldReceive('write');

        $this->request->shouldReceive('baseUrl')->with('login')->andReturn('http://localhost/login');

        $this->firewall->handle();

        expect($this->firewall->isBlocked())->toBeTrue();
        expect($this->firewall->getResponse()['code'])->toBe(307);
    });

    test('handle allows user when not throttled', function () {
        $user = ['id' => 123];
        $this->firewall->user($user);

        $this->config->shouldReceive('get')->with('firewall')->andReturn(['account' => ['enable' => true]]);

        $this->agent->shouldReceive('ip')->andReturn('127.0.0.1');
        $this->agent->shouldReceive('device')->andReturn('Desktop');
        $this->agent->shouldReceive('platform')->andReturn('Windows');
        $this->agent->shouldReceive('browser')->andReturn('Chrome');
        $this->agent->shouldReceive('version')->andReturn('90');

        $this->throttler->shouldReceive('attempt')->andReturn([
            'is_blocked' => false,
        ]);

        $this->firewall->handle();

        expect($this->firewall->isBlocked())->toBeFalse();
    });
});
