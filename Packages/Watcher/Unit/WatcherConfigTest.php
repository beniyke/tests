<?php

declare(strict_types=1);

namespace Tests\Packages\Watcher\Unit;

use Watcher\Config\WatcherConfig;

describe('WatcherConfig', function () {
    test('merges defaults with provided config', function () {
        $config = new WatcherConfig([
            'enabled' => false,
            'types' => ['query' => false],
        ]);

        expect($config->isEnabled())->toBeFalse();
        expect($config->isTypeEnabled('query'))->toBeFalse();
        // Should still have defaults for other types
        expect($config->isTypeEnabled('request'))->toBeTrue();
    });

    test('retrieves sampling rates', function () {
        $config = new WatcherConfig([
            'sampling' => ['query' => 0.5],
        ]);

        expect($config->getSamplingRate('query'))->toBe(0.5);
        expect($config->getSamplingRate('request'))->toBe(1.0); // Default
    });

    test('retrieves batch settings', function () {
        $config = new WatcherConfig([
            'batch' => [
                'enabled' => true,
                'size' => 100,
                'flush_interval' => 10,
            ],
        ]);

        expect($config->isBatchingEnabled())->toBeTrue();
        expect($config->getBatchSize())->toBe(100);
        expect($config->getBatchFlushInterval())->toBe(10);
    });

    test('retrieves retention settings', function () {
        $config = new WatcherConfig([
            'retention' => ['query' => 14],
        ]);

        expect($config->getRetentionDays('query'))->toBe(14);
        expect($config->getRetentionDays('request'))->toBe(7); // Default
    });

    test('retrieves filter settings', function () {
        $config = new WatcherConfig([
            'filters' => [
                'ignore_paths' => ['/test'],
                'ignore_queries' => ['SELECT 1'],
                'redact_fields' => ['password'],
            ],
        ]);

        expect($config->getIgnoredPaths())->toContain('/test');
        expect($config->getIgnoredQueries())->toContain('SELECT 1');
        expect($config->getRedactFields())->toContain('password');
    });

    test('retrieves alert settings', function () {
        $config = new WatcherConfig([
            'alerts' => [
                'enabled' => true,
                'thresholds' => ['error_rate' => 10.0],
                'channels' => ['email' => []],
                'throttle' => 600,
            ],
        ]);

        expect($config->areAlertsEnabled())->toBeTrue();
        expect($config->getAlertThreshold('error_rate'))->toBe(10.0);
        expect($config->getAlertChannels())->toHaveKey('email');
        expect($config->getAlertThrottle())->toBe(600);
    });
});
