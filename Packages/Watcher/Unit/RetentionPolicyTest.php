<?php

declare(strict_types=1);

namespace Tests\Packages\Watcher\Unit;

use Mockery;
use Watcher\Config\WatcherConfig;
use Watcher\Retention\RetentionPolicy;
use Watcher\Storage\WatcherRepository;

beforeEach(function () {
    $this->config = new WatcherConfig([
        'retention' => [
            'request' => 7,
            'query' => 7,
            'exception' => 30,
        ],
    ]);
    $this->repository = Mockery::mock(WatcherRepository::class);
    $this->policy = new RetentionPolicy($this->config, $this->repository);
});

afterEach(function () {
    Mockery::close();
});

describe('RetentionPolicy', function () {
    test('cleanupType deletes old entries', function () {
        $this->repository->shouldReceive('deleteOlderThan')->with('request', 7)->andReturn(50);

        $deleted = $this->policy->cleanupType('request');

        expect($deleted)->toBe(50);
    });

    test('cleanup processes all types', function () {
        $this->repository->shouldReceive('deleteOlderThan')->with('request', 7)->andReturn(10);
        $this->repository->shouldReceive('deleteOlderThan')->with('query', 7)->andReturn(20);
        $this->repository->shouldReceive('deleteOlderThan')->with('exception', 30)->andReturn(5);
        $this->repository->shouldReceive('deleteOlderThan')->andReturn(0);

        $results = $this->policy->cleanup();

        expect($results)->toHaveKey('request');
        expect($results)->toHaveKey('query');
        expect($results)->toHaveKey('exception');
        expect($results['request'])->toBe(10);
    });

    test('getStats returns retention information', function () {
        $this->repository->shouldReceive('countByType')->andReturn(100);

        $stats = $this->policy->getStats();

        expect($stats)->toHaveKey('request');
        expect($stats['request'])->toHaveKey('retention_days');
        expect($stats['request'])->toHaveKey('total_entries');
    });
});
