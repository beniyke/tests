<?php

declare(strict_types=1);

namespace Tests\System\Unit\Helpers;

use App\Models\User;
use Helpers\Validation\Validator;
use Testing\Support\DatabaseTestHelper;

describe('Robust Unique Validation', function () {
    beforeEach(function () {
        DatabaseTestHelper::setupTestEnvironment([], true);
    });

    afterEach(function () {
        DatabaseTestHelper::dropAllTables();
    });

    test('validates unique field (collision exists)', function () {
        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'gender' => 'male',
            'refid' => 'REF123',
        ]);

        $validator = new Validator();
        $validator->rules(['email' => ['unique' => 'user.email']])
            ->parameters(['email' => 'Email'])
            ->validate(['email' => 'john@example.com']);

        expect($validator->has_error())->toBeTrue();
        expect(implode(' ', $validator->errors()['email']))->toContain('already exist');
    });

    test('validates unique field (no collision)', function () {
        $validator = new Validator();
        $validator->rules(['email' => ['unique' => 'user.email']])
            ->parameters(['email' => 'Email'])
            ->validate(['email' => 'new@example.com']);

        expect($validator->has_error())->toBeFalse();
    });

    test('validates unique field ignoring ID (collision excluded)', function () {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'gender' => 'male',
            'refid' => 'REF123',
        ]);

        $validator = new Validator();
        // Syntax: table.column:ignoreValue (defaults to ID column)
        $validator->rules(['email' => ['unique' => 'user.email:' . $user->id]])
            ->parameters(['email' => 'Email'])
            ->validate(['email' => 'john@example.com']);

        expect($validator->has_error())->toBeFalse();
    });

    test('validates unique field ignoring custom column (collision excluded)', function () {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'gender' => 'male',
            'refid' => 'REF123',
        ]);

        $validator = new Validator();
        // Syntax: table.column:ignoreValue,ignoreColumn
        $validator->rules(['email' => ['unique' => "user.email:{$user->refid},refid"]])
            ->parameters(['email' => 'Email'])
            ->validate(['email' => 'john@example.com']);

        expect($validator->has_error())->toBeFalse();
    });

    test('validates unique field ignoring self via email column', function () {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'gender' => 'male',
            'refid' => 'REF123',
        ]);

        $validator = new Validator();
        // Syntax: table.column:ignoreValue,ignoreColumn (same column for both)
        $validator->rules(['email' => ['unique' => "user.email:{$user->email},email"]])
            ->parameters(['email' => 'Email'])
            ->validate(['email' => 'john@example.com']);

        expect($validator->has_error())->toBeFalse();
    });

    test('fails unique validation when another record has the value', function () {
        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'gender' => 'male',
            'refid' => 'REF123',
        ]);

        $user2 = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'gender' => 'female',
            'refid' => 'REF456',
        ]);

        $validator = new Validator();
        // Try to change Jane's email to John's email, while ignoring Jane's ID
        $validator->rules(['email' => ['unique' => 'user.email:' . $user2->id]])
            ->parameters(['email' => 'Email'])
            ->validate(['email' => 'john@example.com']);

        expect($validator->has_error())->toBeTrue();
    });
});
