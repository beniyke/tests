<?php

declare(strict_types=1);

namespace Tests\Packages\Watcher\Unit;

use Mockery;
use Watcher\Config\WatcherConfig;
use Watcher\Sampling\Sampler;

beforeEach(function () {
    $this->config = Mockery::mock(WatcherConfig::class);
    $this->sampler = new Sampler($this->config);
});

afterEach(function () {
    Mockery::close();
});

describe('Sampler', function () {
    test('always samples when rate is 1.0', function () {
        $this->config->shouldReceive('getSamplingRate')->with('query')->andReturn(1.0);

        // Run multiple times to be sure
        for ($i = 0; $i < 100; $i++) {
            expect($this->sampler->shouldSample('query'))->toBeTrue();
        }
    });

    test('never samples when rate is 0.0', function () {
        $this->config->shouldReceive('getSamplingRate')->with('debug')->andReturn(0.0);

        for ($i = 0; $i < 100; $i++) {
            expect($this->sampler->shouldSample('debug'))->toBeFalse();
        }
    });

    test('samples approximately according to rate', function () {
        $this->config->shouldReceive('getSamplingRate')->with('request')->andReturn(0.5);

        $samples = 0;
        $total = 1000;

        for ($i = 0; $i < $total; $i++) {
            if ($this->sampler->shouldSample('request')) {
                $samples++;
            }
        }

        // Allow some variance, but should be roughly 50%
        expect($samples)->toBeGreaterThan(400);
        expect($samples)->toBeLessThan(600);
    });
});
