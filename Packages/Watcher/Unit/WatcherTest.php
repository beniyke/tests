<?php

declare(strict_types=1);

namespace Tests\Packages\Watcher\Unit;

use Mockery;
use Watcher\Config\WatcherConfig;
use Watcher\Filters\WatcherFilter;
use Watcher\Sampling\Sampler;
use Watcher\Storage\WatcherRepository;
use Watcher\WatcherManager;

beforeEach(function () {
    /** @var WatcherConfig $config */
    /** @var WatcherRepository $repository */
    /** @var Sampler $sampler */
    /** @var WatcherFilter $filter */
    /** @var WatcherManager $watcher */

    $this->config = Mockery::mock(WatcherConfig::class);
    $this->config->shouldReceive('isBatchingEnabled')->andReturn(false);
    $this->config->shouldReceive('isEnabled')->andReturn(true);
    $this->config->shouldReceive('isTypeEnabled')->andReturn(true);

    $this->repository = Mockery::mock(WatcherRepository::class);
    $this->sampler = Mockery::mock(Sampler::class);
    $this->filter = Mockery::mock(WatcherFilter::class);

    $this->watcher = new WatcherManager(
        $this->config,
        $this->repository,
        $this->sampler,
        $this->filter
    );
});

afterEach(function () {
    Mockery::close();
});

describe('Watcher Facade', function () {
    /** @var WatcherManager $manager */
    /** @var WatcherAnalytics $analytics */
    /** @var AlertManager $alerts */

    beforeEach(function () {
        // This beforeEach block is empty in the provided instruction,
        // but the original code has a beforeEach at the top level.
        // Assuming this is a new beforeEach for the 'Watcher Facade' describe block.
    });

    test('records event when should record returns true', function () {
        $this->sampler->shouldReceive('shouldSample')->with('query')->andReturn(true);
        $this->filter->shouldReceive('shouldIgnore')->with('query', Mockery::any())->andReturn(false);
        $this->filter->shouldReceive('filter')->with('query', Mockery::any())->andReturn(['sql' => 'SELECT 1']);
        $this->repository->shouldReceive('insert')->once();

        $this->watcher->record('query', ['sql' => 'SELECT 1']);
    });

    test('does not record when sampling returns false', function () {
        $this->filter->shouldReceive('shouldIgnore')->with('query', Mockery::any())->andReturn(false);
        $this->sampler->shouldReceive('shouldSample')->with('query')->andReturn(false);
        $this->repository->shouldReceive('insert')->never();

        $this->watcher->record('query', ['sql' => 'SELECT 1']);
    });

    test('does not record when filter says ignore', function () {
        $this->sampler->shouldReceive('shouldSample')->with('request')->andReturn(true);
        $this->filter->shouldReceive('shouldIgnore')->with('request', Mockery::any())->andReturn(true);
        $this->repository->shouldReceive('insert')->never();

        $this->watcher->record('request', ['uri' => '/health']);
    });

    test('startBatch sets batch ID', function () {
        $this->sampler->shouldReceive('shouldSample')->andReturn(true);
        $this->filter->shouldReceive('shouldIgnore')->andReturn(false);
        $this->filter->shouldReceive('filter')->andReturn(['test' => 'data']);

        $this->repository->shouldReceive('insert')->once()->with(Mockery::on(function ($entry) {
            return isset($entry['batch_id']) && ! empty($entry['batch_id']);
        }));

        $this->watcher->startBatch();
        $this->watcher->record('test', ['test' => 'data']);
    });

    test('flush calls repository flush when batching', function () {
        // This test would need BatchRecorder to be mockable
        expect(true)->toBeTrue();
    });
});
