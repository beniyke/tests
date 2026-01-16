<?php

declare(strict_types=1);

use Helpers\Array\Collections;

describe('Collections', function () {
    test('creates collection from array', function () {
        $collection = Collections::make([1, 2, 3]);
        expect($collection->get())->toBe([1, 2, 3]);
    });

    test('maps over collection', function () {
        $collection = Collections::make([1, 2, 3]);
        $result = $collection->map(fn ($item) => $item * 2);
        expect($result->get())->toBe([2, 4, 6]);
    });

    test('filters collection', function () {
        $collection = Collections::make([1, 2, 3, 4, 5]);
        // ArrayCollection::clean uses array_filter
        $result = $collection->clean(fn ($item) => $item > 2);
        // clean returns array, wrapped by Collections
        expect($result->get())->toBe([2 => 3, 3 => 4, 4 => 5]);
    });

    test('gets first item', function () {
        $collection = Collections::make([1, 2, 3]);
        expect($collection->first())->toBe(1);
    });

    test('gets last item', function () {
        $collection = Collections::make([1, 2, 3]);
        expect($collection->last())->toBe(3);
    });

    test('counts items', function () {
        $collection = Collections::make([1, 2, 3]);
        expect($collection->count())->toBe(3);
    });

    test('checks if empty', function () {
        $empty = Collections::make([]);
        $notEmpty = Collections::make([1]);

        expect($empty->isEmpty())->toBeTrue();
        expect($notEmpty->isEmpty())->toBeFalse();
    });

    test('plucks values', function () {
        $collection = Collections::make([
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25],
        ]);
        $result = $collection->pluck('name');
        expect($result->get())->toBe(['John', 'Jane']);
    });

    test('checks contains', function () {
        $collection = Collections::make([1, 2, 3]);
        expect($collection->contains(2))->toBeTrue();
        expect($collection->contains(5))->toBeFalse();
    });

    test('chunks collection', function () {
        $collection = Collections::make([1, 2, 3, 4, 5]);
        $result = $collection->chunk(2);
        expect($result->count())->toBe(3);
    });

    test('takes items', function () {
        $collection = Collections::make([1, 2, 3, 4, 5]);
        $result = $collection->take(3);
        expect($result->get())->toBe([1, 2, 3]);
    });

    test('reverses collection', function () {
        $collection = Collections::make([1, 2, 3]);
        $result = $collection->reverse();
        expect(array_values($result->get()))->toBe([3, 2, 1]);
    });

    test('merges collections', function () {
        $collection1 = Collections::make([1, 2]);
        $collection2 = Collections::make([3, 4]);
        // push uses array_merge
        $result = $collection1->push($collection2->get());
        expect($result->get())->toBe([1, 2, 3, 4]);
    });

    test('gets unique items', function () {
        $collection = Collections::make([1, 2, 2, 3, 3, 3]);
        $result = $collection->unique();
        expect(array_values($result->get()))->toBe([1, 2, 3]);
    });

    test('groups by key', function () {
        $collection = Collections::make([
            ['type' => 'fruit', 'name' => 'apple'],
            ['type' => 'fruit', 'name' => 'banana'],
            ['type' => 'vegetable', 'name' => 'carrot'],
        ]);
        $result = $collection->groupByKey('type');
        expect($result->has('fruit'))->toBeTrue();
        expect($result->has('vegetable'))->toBeTrue();
    });
});

describe('Collections Helper Static', function () {
    test('creates collection from array', function () {
        $collection = Collections::make([1, 2, 3]);
        expect($collection)->toBeInstanceOf(Collections::class);
    });

    test('wraps value in array', function () {
        $array = Collections::wrap(5);
        expect($array)->toBe([5]);
    });

    test('flattens nested arrays', function () {
        $result = Collections::flatten([[1, 2], [3, 4]]);
        expect($result)->toBe([1, 2, 3, 4]);
    });
});
