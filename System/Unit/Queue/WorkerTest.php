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

        $this->cache->shouldReceive('has')->with('worker_status_default')->andReturn(false);
        $this->cache->shouldReceive('write')->with('worker_status_default', Mockery::any());
        $this->cache->shouldReceive('read')->with('worker_status_default')->andReturn(null);

        $worker->start();

        expect($worker->terminated)->toBeTrue();
    });

    test('Worker recognizes empty string as stop signal', function () {
        $worker = new TestableWorker($this->cache, $this->osChecker, 'default');

        $this->cache->shouldReceive('has')->with('worker_status_default')->andReturn(true);
        $this->cache->shouldReceive('write')->with('worker_status_default', Mockery::any());
        $this->cache->shouldReceive('read')->with('worker_status_default')->andReturn('');

        $worker->start();

        expect($worker->terminated)->toBeTrue();
    });

    test('Worker stays running when status is active', function () {
        $worker = new TestableWorker($this->cache, $this->osChecker, 'default');

        $this->cache->shouldReceive('has')->with('worker_status_default')->andReturn(true);
        $this->cache->shouldReceive('write')->with('worker_status_default', Mockery::any());

        // Return 'started' once, then return null to stop the loop
        $this->cache->shouldReceive('read')->with('worker_status_default')->andReturn('started', null);

        $worker->start();

        expect($worker->terminated)->toBeTrue();
    });
});
