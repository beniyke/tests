<?php

declare(strict_types=1);

use Helpers\Number\NumberCollection;

describe('NumberCollection', function () {
    test('chains number operations', function () {
        $result = NumberCollection::make(5)
            ->to_integer()
            ->get();

        expect($result)->toBe(500);
    });

    test('format method works via delegation', function () {
        $result = NumberCollection::make(1000)
            ->format(0)
            ->get();

        expect($result)->toBe('1,000');
    });

    test('ordinal method works via delegation', function () {
        $result = NumberCollection::make(1)
            ->ordinal()
            ->get();

        expect($result)->toBe('1st');
    });

    test('throws exception for non-existent method', function () {
        expect(fn () => NumberCollection::make(5)->nonExistent())
            ->toThrow(BadMethodCallException::class);
    });
});
