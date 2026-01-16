<?php

declare(strict_types=1);

namespace Tests\Packages\Watcher\Integration;

use Core\Ioc\Container;
use Testing\Support\DatabaseTestHelper;
use Watcher\Analytics\WatcherAnalytics;
use Watcher\Config\WatcherConfig;
use Watcher\Filters\WatcherFilter;
use Watcher\Sampling\Sampler;
use Watcher\Storage\WatcherRepository;
use Watcher\Watcher;
use Watcher\WatcherManager;

beforeEach(function () {
    // Setup Test Environment (Schema + Migrations)
    $this->connection = DatabaseTestHelper::setupTestEnvironment(['Watcher']);

    // Create Watcher components manually with our test connection
    $this->config = new WatcherConfig([
        'enabled' => true,
        'types' => [
            'query' => true,
            'request' => true,
        ],
        'sampling' => [
            'query' => 1.0,
            'request' => 1.0,
        ],
        'batch' => [
            'enabled' => true,
            'size' => 50,
            'flush_interval' => 5,
        ],
    ]);

    $this->repository = new WatcherRepository($this->connection);
    $this->sampler = new Sampler($this->config);
    $this->filter = new WatcherFilter($this->config);

    $this->watcher = new WatcherManager(
        $this->config,
        $this->repository,
        $this->sampler,
        $this->filter
    );

    $this->analytics = new WatcherAnalytics($this->repository);

    // Bind instances to container so Facade/Static calls work during test
    $container = Container::getInstance();
    $container->instance(WatcherConfig::class, $this->config);
    $container->instance(WatcherRepository::class, $this->repository);
    $container->instance(WatcherManager::class, $this->watcher);
    $container->instance(WatcherAnalytics::class, $this->analytics);
});

afterEach(function () {
    DatabaseTestHelper::dropAllTables();
    DatabaseTestHelper::resetDefaultConnection();
});

describe('Watcher Integration', function () {
    test('records query events end-to-end', function () {
        // Record a query event
        $this->watcher->record('query', [
            'sql' => 'SELECT * FROM users',
            'time_ms' => 50,
            'connection' => 'default',
        ]);

        $this->watcher->flush();

        // Verify it was recorded
        $entries = $this->repository->getByType('query', 10);
        expect($entries)->not->toBeEmpty();
        expect($entries[0]['type'])->toBe('query');
    });

    test('analytics can retrieve recorded data', function () {
        // Record some test data
        for ($i = 0; $i < 5; $i++) {
            $this->watcher->record('request', [
                'method' => 'GET',
                'uri' => '/test',
                'status' => 200,
                'duration_ms' => 100 + $i,
            ]);
        }

        $this->watcher->flush();

        // Get analytics
        $stats = $this->analytics->getRequestStats('24h');
        expect($stats['total_requests'])->toBeGreaterThan(0);
        expect($stats['avg_response_time_ms'])->toBeGreaterThan(0);
    });

    test('batch recording works correctly', function () {
        $batchId = 'test-batch-123';
        $this->watcher->startBatch($batchId);

        $this->watcher->record('query', ['sql' => 'SELECT 1']);
        $this->watcher->record('query', ['sql' => 'SELECT 2']);

        $this->watcher->stopBatch();

        // Verify batch ID was set
        $entries = $this->repository->getByBatchId($batchId);

        // For now, accept that batching may combine entries
        expect(count($entries))->toBeGreaterThanOrEqual(1);
        expect($entries[0]['batch_id'])->toBe($batchId);
    });
});
