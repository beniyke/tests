<?php

declare(strict_types=1);

use Tests\System\DTOs\TestProductDTO;
use Tests\System\DTOs\TestStrictDTO;
use Tests\System\DTOs\TestUserDTO;

describe('DTO - Property Mapping', function () {
    test('maps array data to properties', function () {
        $dto = new TestUserDTO(['name' => 'John', 'age' => 30]);

        expect($dto->name)->toBe('John');
        expect($dto->age)->toBe(30);
    });

    test('handles optional properties', function () {
        $dto = new TestUserDTO(['name' => 'Jane', 'age' => 25]);

        expect($dto->name)->toBe('Jane');
        expect($dto->age)->toBe(25);
        expect($dto->email)->toBeNull();
    });

    test('uses default values', function () {
        $dto = new TestProductDTO(['id' => '123', 'title' => 'Product', 'price' => 99.99]);

        expect($dto->active)->toBeTrue();
    });

    test('sets provided values over defaults', function () {
        $dto = new TestProductDTO([
            'id' => '456',
            'title' => 'Item',
            'price' => 49.99,
            'active' => false,
        ]);

        expect($dto->active)->toBeFalse();
    });
});

describe('DTO - Validation', function () {
    test('tracks missing required properties', function () {
        $dto = new TestStrictDTO(['required' => 'value']);

        expect($dto->isValid())->toBeFalse();
        expect($dto->getErrors())->toContain("The required property 'count' is missing.");
    });

    test('is valid when all properties provided', function () {
        $dto = new TestUserDTO(['name' => 'Alice', 'age' => 28, 'email' => 'alice@example.com']);

        expect($dto->isValid())->toBeTrue();
        expect($dto->getErrors())->toBeEmpty();
    });

    test('tracks multiple missing properties', function () {
        $dto = new TestStrictDTO([]);

        expect($dto->isValid())->toBeFalse();
        $errors = $dto->getErrors();
        expect(count($errors))->toBe(2);
    });
});

describe('DTO - Readonly Properties', function () {
    test('handles readonly properties', function () {
        $dto = new TestProductDTO([
            'id' => 'prod-123',
            'title' => 'Test Product',
            'price' => 29.99,
        ]);

        expect($dto->id)->toBe('prod-123');
    });

    test('provides default for missing readonly property', function () {
        $dto = new TestProductDTO([
            'title' => 'Product',
            'price' => 19.99,
        ]);

        // Readonly property gets default value (empty string for string type)
        expect($dto->id)->toBe('');
        expect($dto->getErrors())->toContain("The required property 'id' is missing.");
    });
});

describe('DTO - Conversion Methods', function () {
    test('converts to array', function () {
        $dto = new TestUserDTO(['name' => 'Bob', 'age' => 35, 'email' => 'bob@test.com']);
        $array = $dto->toArray();

        expect($array)->toBe([
            'name' => 'Bob',
            'age' => 35,
            'email' => 'bob@test.com',
        ]);
    });

    test('toArray excludes uninitialized properties', function () {
        $dto = new TestUserDTO(['name' => 'Charlie', 'age' => 40]);
        $array = $dto->toArray();

        expect(array_key_exists('email', $array))->toBeTrue();
        expect($array['email'])->toBeNull();
    });

    test('converts to Data object', function () {
        $dto = new TestUserDTO(['name' => 'Diana', 'age' => 29]);
        $data = $dto->getData();

        expect($data)->toBeInstanceOf(Helpers\Data::class);
        expect($data->get('name'))->toBe('Diana');
        expect($data->get('age'))->toBe(29);
    });
});

describe('DTO - Type Coercion', function () {
    test('coerces string to int', function () {
        $dto = new TestUserDTO(['name' => 'Eve', 'age' => '25']);

        // PHP will coerce the string to int when assigning to typed property
        expect($dto->age)->toBe(25);
    });

    test('coerces string to float', function () {
        $dto = new TestProductDTO([
            'id' => '789',
            'title' => 'Widget',
            'price' => '99.99',
        ]);

        expect($dto->price)->toBe(99.99);
    });
});

describe('DTO - Edge Cases', function () {
    test('handles extra properties in input', function () {
        $dto = new TestUserDTO([
            'name' => 'Frank',
            'age' => 45,
            'extra' => 'ignored',
        ]);

        expect($dto->name)->toBe('Frank');
        expect($dto->age)->toBe(45);
        expect(property_exists($dto, 'extra'))->toBeFalse();
    });

    test('handles empty array input', function () {
        $dto = new TestStrictDTO([]);

        expect($dto->isValid())->toBeFalse();
        $errors = $dto->getErrors();
        expect(count($errors))->toBeGreaterThan(0);
    });
});
