<?php

declare(strict_types=1);

use Helpers\Benchmark;

beforeEach(function () {
    Benchmark::reset();
});

describe('Benchmark Helper', function () {
    test('start and stop calculate duration', function () {
        Benchmark::start('test_timer');
        usleep(100000); // Sleep for 100ms
        $duration = Benchmark::stop('test_timer');

        expect($duration)->toBeGreaterThan(20); // Allow some variance
        expect($duration)->toBeLessThan(500);
    });

    test('has checks if timer exists', function () {
        expect(Benchmark::has('test_timer'))->toBeFalse();
        Benchmark::start('test_timer');
        expect(Benchmark::has('test_timer'))->toBeTrue();
        Benchmark::stop('test_timer');
        expect(Benchmark::has('test_timer'))->toBeFalse();
    });

    test('get returns stored duration', function () {
        Benchmark::start('stored_timer');
        usleep(50000);
        Benchmark::stop('stored_timer');

        $duration = Benchmark::get('stored_timer');
        expect($duration)->not->toBeNull();
        expect($duration)->toBeGreaterThan(40);
    });

    test('memory returns stored memory usage', function () {
        Benchmark::start('memory_timer');
        $array = range(1, 10000); // Allocate some memory
        Benchmark::stop('memory_timer');

        $memory = Benchmark::memory('memory_timer');
        expect($memory)->not->toBeNull();
        expect($memory)->toBeGreaterThan(0);
    });

    test('getAll returns all timers with details', function () {
        Benchmark::start('timer_1');
        Benchmark::stop('timer_1');

        Benchmark::start('timer_2');
        Benchmark::stop('timer_2');

        $all = Benchmark::getAll();

        expect($all)->toHaveCount(2);
        expect($all['timer_1'])->toHaveKeys(['time', 'memory']);
        expect($all['timer_2'])->toHaveKeys(['time', 'memory']);
    });

    test('measure executes callback and records time', function () {
        $result = Benchmark::measure('measure_timer', function () {
            usleep(50000);

            return 'success';
        });

        expect($result)->toBe('success');

        $duration = Benchmark::get('measure_timer');
        expect($duration)->toBeGreaterThan(40);
    });

    test('stop throws exception for non-existent timer', function () {
        Benchmark::stop('non_existent');
    })->throws(RuntimeException::class);

    test('global benchmark function works', function () {
        $result = benchmark('global_timer', function () {
            return 'global_success';
        });

        expect($result)->toBe('global_success');
        expect(Benchmark::get('global_timer'))->not->toBeNull();
    });

    test('global benchmark function returns class name when no callback', function () {
        expect(benchmark('any_key'))->toBe(Benchmark::class);
    });
});
