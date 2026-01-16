<?php

declare(strict_types=1);

use Helpers\Capsule;

describe('Capsule - Basic Operations', function () {
    test('creates empty capsule', function () {
        $capsule = Capsule::empty();

        expect($capsule->isEmpty())->toBeTrue();
        expect($capsule->count())->toBe(0);
    });

    test('creates capsule from array', function () {
        $capsule = Capsule::make(['name' => 'John', 'age' => 30]);

        expect($capsule->get('name'))->toBe('John');
        expect($capsule->get('age'))->toBe(30);
    });

    test('sets and gets values', function () {
        $capsule = Capsule::empty();
        $capsule->set('key', 'value');

        expect($capsule->get('key'))->toBe('value');
        expect($capsule->has('key'))->toBeTrue();
    });

    test('forgets keys', function () {
        $capsule = Capsule::make(['a' => 1, 'b' => 2]);
        $capsule->forget('a');

        expect($capsule->has('a'))->toBeFalse();
        expect($capsule->has('b'))->toBeTrue();
    });
});

describe('Capsule - Nested Keys', function () {
    test('sets nested values with dot notation', function () {
        $capsule = Capsule::empty();
        $capsule->set('user.name', 'Alice');

        expect($capsule->get('user.name'))->toBe('Alice');
    });

    test('gets nested values with dot notation', function () {
        $capsule = Capsule::make([
            'user' => ['name' => 'Bob', 'settings' => ['theme' => 'dark']],
        ]);

        expect($capsule->get('user.name'))->toBe('Bob');
        expect($capsule->get('user.settings.theme'))->toBe('dark');
    });
});

describe('Capsule - Schema Validation', function () {
    test('enforces schema types', function () {
        $capsule = Capsule::empty();
        $capsule->schema(['age' => 'int', 'name' => 'string']);

        $capsule->set('age', '25');
        $capsule->set('name', 123);

        expect($capsule->get('age'))->toBe(25);
        expect($capsule->get('name'))->toBe('123');
    });

    test('throws on invalid schema key', function () {
        $capsule = Capsule::empty();
        $capsule->schema(['allowed' => 'string']);

        $capsule->set('not_allowed', 'value');
    })->throws(InvalidArgumentException::class, 'Schema');
});

describe('Capsule - Immutability', function () {
    test('immutable capsule prevents modifications', function () {
        $capsule = Capsule::make(['a' => 1])->immutable();

        $capsule->set('b', 2);
    })->throws(RuntimeException::class, 'Sealed');

    test('frozen capsule prevents new keys', function () {
        $capsule = Capsule::make(['a' => 1])->freeze();

        $capsule->set('b', 2);
    })->throws(RuntimeException::class, 'Frozen');
});

describe('Capsule - Data Manipulation', function () {
    test('selects only specified keys', function () {
        $capsule = Capsule::make(['a' => 1, 'b' => 2, 'c' => 3]);
        $filtered = $capsule->only(['a', 'c']);

        expect($filtered->has('a'))->toBeTrue();
        expect($filtered->has('b'))->toBeFalse();
        expect($filtered->has('c'))->toBeTrue();
    });

    test('with creates new capsule with additional data', function () {
        $capsule = Capsule::make(['a' => 1]);
        $new = $capsule->with(['b' => 2]);

        expect($capsule->has('b'))->toBeFalse();
        expect($new->has('b'))->toBeTrue();
    });
});

describe('Capsule - Utility Methods', function () {
    test('counts elements', function () {
        $capsule = Capsule::make(['a' => 1, 'b' => 2, 'c' => 3]);

        expect($capsule->count())->toBe(3);
    });

    test('checks if empty', function () {
        expect(Capsule::empty()->isEmpty())->toBeTrue();
        expect(Capsule::make(['a' => 1])->isEmpty())->toBeFalse();
    });

    test('sums values', function () {
        $capsule = Capsule::make(['a' => 1, 'b' => 2, 'c' => 3]);

        expect($capsule->sum())->toBe(6);
    });

    test('converts to array', function () {
        $data = ['a' => 1, 'b' => 2];
        $capsule = Capsule::make($data);

        expect($capsule->toArray())->toBe($data);
    });

    test('converts to JSON', function () {
        $capsule = Capsule::make(['name' => 'Test', 'value' => 123]);
        $json = $capsule->toJson();

        expect($json)->toBeString();
        expect(json_decode($json, true))->toBe(['name' => 'Test', 'value' => 123]);
    });
});
