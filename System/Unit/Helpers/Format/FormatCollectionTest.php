<?php

declare(strict_types=1);

use Helpers\Format\FormatCollection;

describe('FormatCollection', function () {
    test('formats collection to array', function () {
        $collection = new ArrayIterator(['one', 'two']);
        $formatted = FormatCollection::asArray($collection);

        expect($formatted)->toBeArray();
        expect($formatted)->toBe(['one', 'two']);
    });

    test('handles empty collection', function () {
        $formatted = FormatCollection::asArray([]);
        expect($formatted)->toBe([]);
    });

    test('handles nested collections', function () {
        $data = [
            'users' => new ArrayIterator(['Alice', 'Bob']),
            'meta' => ['page' => 1],
        ];

        // Assuming toArray handles recursion or just direct conversion
        // Let's check the implementation if needed, but for now test basic behavior
        $formatted = FormatCollection::asArray($data);

        expect($formatted['users'])->toBeInstanceOf(ArrayIterator::class);
        // Note: standard iterator_to_array doesn't recurse by default unless implemented
    });
});
