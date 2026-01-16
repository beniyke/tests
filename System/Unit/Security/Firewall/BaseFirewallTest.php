<?php

declare(strict_types=1);

use Core\Services\ConfigServiceInterface;
use Helpers\File\Contracts\CacheInterface;
use Helpers\Http\Flash;
use Helpers\Http\Request;
use Helpers\Http\UserAgent;
use Notify\Notifier;
use Security\Firewall\Throttling\Throttler;
use Tests\System\Support\Security\TestFirewall;

beforeEach(function () {
    $this->config = Mockery::mock(ConfigServiceInterface::class);
    $this->cache = Mockery::mock(CacheInterface::class);
    $this->agent = Mockery::mock(UserAgent::class);
    $this->notifier = Mockery::mock(Notifier::class);
    $this->request = Mockery::mock(Request::class);
    $this->flash = Mockery::mock(Flash::class);
    $this->throttler = Mockery::mock(Throttler::class);

    $this->firewall = new TestFirewall(
        $this->config,
        $this->cache,
        $this->agent,
        $this->notifier,
        $this->request,
        $this->flash,
        $this->throttler
    );
});

describe('BaseFirewall', function () {
    test('getConfig returns full config when no key provided', function () {
        $configData = ['key' => 'value'];
        $this->config->shouldReceive('get')->with('firewall')->andReturn($configData);

        expect($this->firewall->publicGetConfig())->toBe($configData);
    });

    test('getConfig returns specific value when key provided', function () {
        $configData = ['key' => ['sub' => 'value']];
        $this->config->shouldReceive('get')->with('firewall')->andReturn($configData);

        expect($this->firewall->publicGetConfig('key'))->toBe(['sub' => 'value']);
    });

    test('cache returns cache interface with firewall path', function () {
        $this->cache->shouldReceive('withPath')->with('firewall')->andReturn($this->cache);

        expect($this->firewall->publicCache())->toBe($this->cache);
    });

    test('auditTrail writes to cache if not exists', function () {
        $this->agent->shouldReceive('ip')->andReturn('127.0.0.1');
        $this->agent->shouldReceive('browser')->andReturn('Chrome');
        $this->agent->shouldReceive('version')->andReturn('90');
        $this->agent->shouldReceive('platform')->andReturn('Windows');
        $this->agent->shouldReceive('device')->andReturn('Desktop');

        $this->cache->shouldReceive('withPath')->andReturn($this->cache);
        $this->cache->shouldReceive('has')->andReturn(false);
        $this->cache->shouldReceive('write')->once();

        $this->config->shouldReceive('get')->with('firewall')->andReturn(['notification' => ['mail' => ['status' => false]]]);

        $this->firewall->publicAuditTrail('test message');
    });

    test('auditTrail skips writing if already cached', function () {
        $this->agent->shouldReceive('ip')->andReturn('127.0.0.1');
        $this->agent->shouldReceive('browser')->andReturn('Chrome');
        $this->agent->shouldReceive('version')->andReturn('90');
        $this->agent->shouldReceive('platform')->andReturn('Windows');
        $this->agent->shouldReceive('device')->andReturn('Desktop');

        $this->cache->shouldReceive('withPath')->andReturn($this->cache);
        $this->cache->shouldReceive('has')->andReturn(true);
        $this->cache->shouldReceive('write')->never();

        $this->firewall->publicAuditTrail('test message');
    });

    test('setResponse sets the response', function () {
        $response = ['content' => 'test', 'code' => 200, 'header' => []];
        $this->firewall->publicSetResponse($response);

        expect($this->firewall->getResponse())->toBe($response);
    });

    test('isBlocked returns initial false', function () {
        expect($this->firewall->isBlocked())->toBeFalse();
    });

    test('getJsonResponsePayload formats correctly', function () {
        $content = ['status' => 'ok'];
        $payload = $this->firewall->publicGetJsonResponsePayload($content, 200);

        expect($payload['content'])->toBe(json_encode($content));
        expect($payload['code'])->toBe(200);
        expect($payload['header'])->toHaveKey('Content-Type', 'application/json');
    });

    test('getRedirectResponsePayload formats correctly', function () {
        $this->request->shouldReceive('baseUrl')->with('login')->andReturn('http://localhost/login');

        $payload = $this->firewall->publicGetRedirectResponsePayload('login');

        expect($payload['content'])->toBe('http://localhost/login');
        expect($payload['code'])->toBe(307);
    });
});
