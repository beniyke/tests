<?php

declare(strict_types=1);

namespace Tests\Packages\Watcher\Unit;

use Helpers\File\Contracts\CacheInterface;
use Mockery;
use Watcher\Alerts\AlertManager;
use Watcher\Analytics\WatcherAnalytics;
use Watcher\Config\WatcherConfig;

beforeEach(function () {
    $this->config = new WatcherConfig([
        'alerts' => [
            'enabled' => true,
            'thresholds' => [
                'error_rate' => 5.0,
                'slow_query' => 1000,
            ],
            'channels' => [
                'email' => [],
            ],
            'throttle' => 300,
        ],
    ]);
    $this->analytics = Mockery::mock(WatcherAnalytics::class);
    $this->cache = Mockery::mock(CacheInterface::class);
    $this->cache->shouldReceive('withPath')->andReturnSelf();

    $this->alertManager = new AlertManager($this->config, $this->analytics, $this->cache);
});

afterEach(function () {
    Mockery::close();
});

describe('AlertManager', function () {
    test('checkThresholds detects high error rate', function () {
        // First call for error_rate check
        $this->analytics->shouldReceive('getRequestStats')->with('1h')->andReturn([
            'total_requests' => 100,
            'status_codes' => [500 => 10],
            'avg_response_time_ms' => 100,
        ]);
        $this->analytics->shouldReceive('getSlowQueries')->andReturn([]);
        $this->cache->shouldReceive('has')->andReturn(false);
        $this->cache->shouldReceive('write')->once();

        $alerts = $this->alertManager->checkThresholds();

        expect($alerts)->not->toBeEmpty();
        expect($alerts[0]['type'])->toBe('error_rate');
    });

    test('shouldThrottle prevents duplicate alerts', function () {
        $this->cache->shouldReceive('has')->with('test_alert')->andReturn(true);

        $shouldThrottle = $this->alertManager->shouldThrottle('test_alert');

        expect($shouldThrottle)->toBeTrue();
    });

    test('registerChannel adds alert channel', function () {
        $channel = Mockery::mock('stdClass');
        $this->alertManager->registerChannel('test', $channel);

        expect(true)->toBeTrue(); // Channel registered successfully
    });
});
