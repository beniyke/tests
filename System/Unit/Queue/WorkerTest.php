<?php

declare(strict_types=1);

namespace Tests\System\Unit\Queue;

use Core\Support\Adapters\Interfaces\OSCheckerInterface;
use Helpers\File\Contracts\CacheInterface;
use Mockery;
use Tests\System\Support\Queue\TestableWorker;

describe('Queue Worker', function () {
    beforeEach(function () {
        $this->cache = Mockery::mock(CacheInterface::class);
        $this->osChecker = Mockery::mock(OSCheckerInterface::class);

        $this->cache->shouldReceive('withPath')->andReturn($this->cache);
        $this->osChecker->shouldReceive('isWindows')->andReturn(true); // Default to Windows for simpler signal handling in tests
    });

    afterEach(function () {
        Mockery::close();
    });

    test('Worker recognizes missing cache key as stop signal', function () {
        $worker = new TestableWorker($this->cache, $this->osChecker, 'default');

        // hasStarted() check in start() and daemon()
        $this->cache->shouldReceive('has')->atLeast()->times(2)->with('worker_status_default')->andReturn(false, false, false);
        $this->cache->shouldReceive('write')->once()->with('worker_status_default', 'started');

        // First read in daemon loop loop
        $this->cache->shouldReceive('read')->once()->with('worker_status_default')->andReturn(null); // Missing key

        $worker->start();

        expect($worker->terminated)->toBeTrue();
    });

    test('Worker recognizes empty string as stop signal', function () {
        $worker = new TestableWorker($this->cache, $this->osChecker, 'default');

        $this->cache->shouldReceive('has')->atLeast()->times(2)->with('worker_status_default')->andReturn(false, false, true);
        $this->cache->shouldReceive('write')->once()->with('worker_status_default', 'started');

        $this->cache->shouldReceive('read')->once()->with('worker_status_default')->andReturn(''); // Explicit stop

        $worker->start();

        expect($worker->terminated)->toBeTrue();
    });

    test('Worker stays running when status is active', function () {
        $worker = new TestableWorker($this->cache, $this->osChecker, 'default');

        $this->cache->shouldReceive('has')->atLeast()->times(1)->with('worker_status_default')->andReturn(false);
        $this->cache->shouldReceive('write')->atLeast()->once()->with('worker_status_default', 'started');

        // Loop 1: started
        $this->cache->shouldReceive('read')->once()->with('worker_status_default')->andReturn('started');

        // Loop 2: null (stop)
        $this->cache->shouldReceive('read')->once()->with('worker_status_default')->andReturn(null);

        $worker->start();

        expect($worker->terminated)->toBeTrue();
    });
});
