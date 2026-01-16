<?php

declare(strict_types=1);

namespace Tests\Packages\Watcher\Unit;

use Mockery;
use Watcher\Alerts\AlertManager;
use Watcher\Analytics\WatcherAnalytics;
use Watcher\Watcher;
use Watcher\WatcherManager;

describe('Watcher Facade', function () {
    /** @var WatcherManager $manager */
    /** @var WatcherAnalytics $analytics */
    /** @var AlertManager $alerts */

    beforeEach(function () {
        $this->manager = Mockery::mock(WatcherManager::class);
        $this->analytics = Mockery::mock(WatcherAnalytics::class);
        $this->alerts = Mockery::mock(AlertManager::class);

        container()->instance(WatcherManager::class, $this->manager);
        container()->instance(WatcherAnalytics::class, $this->analytics);
        container()->instance(AlertManager::class, $this->alerts);
    });

    test('record() delegates to WatcherManager', function () {
        $this->manager->shouldReceive('record')
            ->with('request', ['foo' => 'bar'])
            ->once();

        Watcher::record('request', ['foo' => 'bar']);
    });

    test('analytics() returns analytics instance', function () {
        expect(Watcher::analytics())->toBe($this->analytics);
    });

    test('alerts() returns alerts instance', function () {
        expect(Watcher::alerts())->toBe($this->alerts);
    });

    test('startBatch() delegates to WatcherManager', function () {
        $this->manager->shouldReceive('startBatch')
            ->with('test-batch')
            ->once();

        Watcher::startBatch('test-batch');
    });

    test('stopBatch() delegates to WatcherManager', function () {
        $this->manager->shouldReceive('stopBatch')
            ->once();

        Watcher::stopBatch();
    });

    test('flush() delegates to WatcherManager', function () {
        $this->manager->shouldReceive('flush')
            ->once();

        Watcher::flush();
    });
});
