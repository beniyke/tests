<?php

declare(strict_types=1);

use Helpers\Routine\Pipe;

describe('Pipe', function () {
    test('executes stages in sequence', function () {
        $result = Pipe::start(5)
            ->through(fn ($x) => $x * 2)
            ->through(fn ($x) => $x + 3)
            ->run();

        expect($result)->toBe(13); // (5 * 2) + 3
    });

    test('handles initial value', function () {
        $result = Pipe::start('hello')
            ->through(fn ($x) => strtoupper($x))
            ->run();

        expect($result)->toBe('HELLO');
    });

    test('applies final callback', function () {
        $result = Pipe::start(10)
            ->through(fn ($x) => $x / 2)
            ->then(fn ($x) => "Result: $x")
            ->run();

        expect($result)->toBe('Result: 5');
    });

    test('works with no stages', function () {
        $result = Pipe::start(42)->run();
        expect($result)->toBe(42);
    });

    test('chains multiple transformations', function () {
        $result = Pipe::start([1, 2, 3])
            ->through(fn ($arr) => array_map(fn ($x) => $x * 2, $arr))
            ->through(fn ($arr) => array_sum($arr))
            ->run();

        expect($result)->toBe(12); // (1*2 + 2*2 + 3*2) = 12
    });
});
