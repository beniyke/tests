<?php

declare(strict_types=1);

namespace Tests\Packages\Watcher\Unit;

use Mockery;
use Watcher\Analytics\WatcherAnalytics;
use Watcher\Storage\WatcherRepository;

beforeEach(function () {
    $this->repository = Mockery::mock(WatcherRepository::class);
    $this->analytics = new WatcherAnalytics($this->repository);
});

afterEach(function () {
    Mockery::close();
});

describe('WatcherAnalytics', function () {
    test('getRequestStats returns correct metrics', function () {
        $this->repository->shouldReceive('getStats')->with('request', Mockery::any())->andReturn([
            'count' => 2, // Changed from 100 to match actual entries count
            'entries' => [
                ['content' => json_encode(['duration_ms' => 100, 'status' => 200])],
                ['content' => json_encode(['duration_ms' => 200, 'status' => 500])],
            ],
        ]);

        $stats = $this->analytics->getRequestStats('24h');

        expect($stats['total_requests'])->toBe(2);
        expect($stats['avg_response_time_ms'])->toBe(150.0);
        expect($stats['status_codes'])->toHaveKey(200);
        expect($stats['status_codes'])->toHaveKey(500);
    });

    test('getQueryStats calculates slow queries', function () {
        $this->repository->shouldReceive('getStats')->with('query', Mockery::any())->andReturn([
            'count' => 50,
            'entries' => [
                ['content' => json_encode(['time_ms' => 50, 'sql' => 'SELECT 1']), 'created_at' => '2025-12-01 10:00:00'],
                ['content' => json_encode(['time_ms' => 150, 'sql' => 'SELECT *']), 'created_at' => '2025-12-01 10:00:01'],
            ],
        ]);

        $stats = $this->analytics->getQueryStats('24h');

        expect($stats['total_queries'])->toBe(50);
        expect($stats['slow_queries_count'])->toBe(1);
    });

    test('getExceptionStats groups by class', function () {
        $this->repository->shouldReceive('getStats')->with('exception', Mockery::any())->andReturn([
            'count' => 10,
            'entries' => [
                ['content' => json_encode(['class' => 'RuntimeException'])],
                ['content' => json_encode(['class' => 'RuntimeException'])],
                ['content' => json_encode(['class' => 'PDOException'])],
            ],
        ]);

        $stats = $this->analytics->getExceptionStats('24h');

        expect($stats['total_exceptions'])->toBe(10);
        expect($stats['by_class']['RuntimeException'])->toBe(2);
        expect($stats['by_class']['PDOException'])->toBe(1);
    });

    test('getJobStats calculates success rate', function () {
        $this->repository->shouldReceive('getStats')->with('job', Mockery::any())->andReturn([
            'count' => 100,
            'entries' => [
                ['content' => json_encode(['status' => 'completed', 'duration_ms' => 100])],
                ['content' => json_encode(['status' => 'completed', 'duration_ms' => 200])],
                ['content' => json_encode(['status' => 'failed', 'duration_ms' => 50])],
            ],
        ]);

        $stats = $this->analytics->getJobStats('24h');

        expect($stats['total_jobs'])->toBe(100);
        expect($stats['completed'])->toBe(2);
        expect($stats['failed'])->toBe(1);
        expect($stats['success_rate'])->toBeGreaterThan(0);
    });
});
