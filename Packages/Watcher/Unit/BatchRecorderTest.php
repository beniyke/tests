<?php

declare(strict_types=1);

namespace Tests\Packages\Watcher\Unit;

use Mockery;
use Watcher\Batching\BatchRecorder;
use Watcher\Config\WatcherConfig;
use Watcher\Storage\WatcherRepository;

beforeEach(function () {
    $this->config = Mockery::mock(WatcherConfig::class);
    $this->repository = Mockery::mock(WatcherRepository::class);

    $this->config->shouldReceive('getBatchSize')->andReturn(3);
    $this->config->shouldReceive('getBatchFlushInterval')->andReturn(60);

    $this->recorder = new BatchRecorder($this->repository, $this->config);
});

afterEach(function () {
    Mockery::close();
});

describe('BatchRecorder', function () {
    test('add accumulates entries', function () {
        // We need to access private property to verify, or use reflection
        // But simpler is to verify it doesn't flush until limit

        $this->repository->shouldReceive('insertBatch')->never();

        $this->recorder->add(['test' => 1]);
        $this->recorder->add(['test' => 2]);

        expect(true)->toBeTrue(); // No exception and no flush
    });

    test('flushes when batch size reached', function () {
        $this->repository->shouldReceive('insertBatch')->once()->with(Mockery::on(function ($batch) {
            return count($batch) === 3;
        }));

        $this->recorder->add(['test' => 1]);
        $this->recorder->add(['test' => 2]);
        $this->recorder->add(['test' => 3]);
    });

    test('flush sends data to repository', function () {
        $this->repository->shouldReceive('insertBatch')->once()->with(Mockery::on(function ($batch) {
            return count($batch) === 1 && $batch[0]['test'] === 1;
        }));

        $this->recorder->add(['test' => 1]);
        $this->recorder->flush();
    });

    test('destructor flushes remaining entries', function () {
        $this->repository->shouldReceive('insertBatch')->once();

        $recorder = new BatchRecorder($this->repository, $this->config);
        $recorder->add(['test' => 1]);

        // Unset to trigger destructor
        unset($recorder);
    });
});
