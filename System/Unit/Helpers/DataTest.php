<?php

declare(strict_types=1);

use Helpers\Data;

describe('Data - Creation and Initialization', function () {
    test('creates data from array', function () {
        $data = Data::make(['name' => 'John', 'age' => 30]);

        expect($data->get('name'))->toBe('John');
        expect($data->get('age'))->toBe(30);
    });

    test('creates data with only specified keys', function () {
        $data = Data::make(['name' => 'Jane', 'age' => 25, 'city' => 'NYC'], ['name', 'city']);

        expect($data->has('name'))->toBeTrue();
        expect($data->has('city'))->toBeTrue();
        expect($data->has('age'))->toBeFalse();
    });
});

describe('Data - Property Access', function () {
    test('gets value by key', function () {
        $data = Data::make(['username' => 'alice']);

        expect($data->get('username'))->toBe('alice');
    });

    test('gets value with default', function () {
        $data = Data::make(['name' => 'Bob']);

        expect($data->get('missing', 'default'))->toBe('default');
    });

    test('gets value via magic getter', function () {
        $data = Data::make(['email' => 'test@example.com']);

        expect($data->email)->toBe('test@example.com');
    });

    test('checks if key exists', function () {
        $data = Data::make(['key' => 'value']);

        expect($data->has('key'))->toBeTrue();
        expect($data->has('missing'))->toBeFalse();
    });

    test('checks if keys are filled', function () {
        $data = Data::make(['name' => 'John', 'email' => '', 'age' => 30]);

        expect($data->filled(['name', 'age']))->toBeTrue();
        expect($data->filled(['name', 'email']))->toBeFalse();
    });
});

describe('Data - Data Manipulation', function () {
    test('adds new items', function () {
        $data = Data::make(['a' => 1]);
        $updated = $data->add(['b' => 2, 'c' => 3]);

        expect($updated->has('a'))->toBeTrue();
        expect($updated->has('b'))->toBeTrue();
        expect($updated->has('c'))->toBeTrue();
    });

    test('removes items', function () {
        $data = Data::make(['a' => 1, 'b' => 2, 'c' => 3]);
        $updated = $data->remove(['b']);

        expect($updated->has('a'))->toBeTrue();
        expect($updated->has('b'))->toBeFalse();
        expect($updated->has('c'))->toBeTrue();
    });

    test('updates existing items', function () {
        $data = Data::make(['name' => 'John', 'age' => 30]);
        $updated = $data->update(['age' => 31]);

        expect($updated->get('name'))->toBe('John');
        expect($updated->get('age'))->toBe(31);
    });

    test('selects specific items', function () {
        $data = Data::make(['a' => 1, 'b' => 2, 'c' => 3]);
        $selected = $data->select(['a', 'c']);

        expect($selected->has('a'))->toBeTrue();
        expect($selected->has('b'))->toBeFalse();
        expect($selected->has('c'))->toBeTrue();
    });

    test('gets only specified keys as array', function () {
        $data = Data::make(['a' => 1, 'b' => 2, 'c' => 3]);
        $only = $data->only(['a', 'c']);

        expect($only)->toBeArray();
        expect($only)->toHaveKey('a');
        expect($only)->toHaveKey('c');
        expect($only)->not->toHaveKey('b');
    });
});

describe('Data - Array Access Interface', function () {
    test('checks offset exists', function () {
        $data = Data::make(['key' => 'value']);

        expect(isset($data['key']))->toBeTrue();
        expect(isset($data['missing']))->toBeFalse();
    });

    test('gets value via array access', function () {
        $data = Data::make(['name' => 'Alice']);

        expect($data['name'])->toBe('Alice');
    });

    test('throws when setting via array access', function () {
        $data = Data::make(['a' => 1]);

        $data['b'] = 2;
    })->throws(BadMethodCallException::class, 'Cannot set value directly');

    test('throws when unsetting via array access', function () {
        $data = Data::make(['a' => 1]);

        unset($data['a']);
    })->throws(BadMethodCallException::class, 'Cannot unset value directly');
});

describe('Data - Data Conversion', function () {
    test('converts to array', function () {
        $data = Data::make(['name' => 'John', 'age' => 30]);
        $array = $data->data();

        expect($array)->toBe(['name' => 'John', 'age' => 30]);
    });

    test('converts empty strings to null in data', function () {
        $data = Data::make(['name' => 'John', 'email' => '']);
        $array = $data->data();

        expect($array['name'])->toBe('John');
        expect($array['email'])->toBeNull();
    });
});
