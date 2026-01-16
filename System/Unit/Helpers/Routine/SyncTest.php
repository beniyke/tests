<?php

declare(strict_types=1);

use Helpers\Routine\Sync;

describe('Sync', function () {
    test('executes tasks in sequence', function () {
        $results = [];

        Sync::new()
            ->task(function () use (&$results) {
                $results[] = 'task1';

                return 'result1';
            })
            ->task(function () use (&$results) {
                $results[] = 'task2';

                return 'result2';
            })
            ->execute();

        expect($results)->toBe(['task1', 'task2']);
    });

    test('returns task results', function () {
        $results = Sync::new()
            ->task(fn () => 'a')
            ->task(fn () => 'b')
            ->task(fn () => 'c')
            ->execute();

        expect($results)->toBe(['a', 'b', 'c']);
    });

    test('runs before callback for each task', function () {
        $beforeCalls = 0;

        Sync::new()
            ->before(function () use (&$beforeCalls) {
                $beforeCalls++;

                return 'input';
            })
            ->task(fn ($input) => $input)
            ->task(fn ($input) => $input)
            ->execute();

        expect($beforeCalls)->toBe(2);
    });

    test('runs after callback for each task', function () {
        $results = Sync::new()
            ->task(fn () => 5)
            ->task(fn () => 10)
            ->after(fn ($output) => $output * 2)
            ->execute();

        expect($results)->toBe([10, 20]);
    });

    test('chains before and after callbacks', function () {
        $results = Sync::new()
            ->before(fn () => 1)
            ->task(fn ($x) => $x + 1)
            ->task(fn ($x) => $x + 2)
            ->after(fn ($x) => $x * 10)
            ->execute();

        expect($results)->toBe([20, 30]); // (1+1)*10, (1+2)*10
    });
});
