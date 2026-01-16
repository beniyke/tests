<?php

declare(strict_types=1);

/**
 * Integration tests for multi-component workflows
 * Tests interactions between multiple framework systems
 */

use Database\Connection;
use Database\DB;
use Database\Schema\Schema;

beforeEach(function () {
    // Set up in-memory database
    $this->connection = Connection::configure('sqlite::memory:')
        ->name('test_workflows')
        ->connect();

    DB::setDefaultConnection($this->connection);
    Schema::setConnection($this->connection);
});

afterEach(function () {
    // Clean up
    if (isset($this->connection)) {
        $this->connection = null;
    }
});

describe('Multi-Component Workflow - Database Operations', function () {
    test('database insert and query work together', function () {
        Schema::create('workflow_test', function ($table) {
            $table->id();
            $table->string('name');
        });

        DB::table('workflow_test')->insert(['name' => 'Test User']);
        $record = DB::table('workflow_test')->first();

        expect($record)->not->toBeNull();
        expect($record->name)->toBe('Test User');

        Schema::dropIfExists('workflow_test');
    });

    test('database transactions maintain integrity', function () {
        Schema::create('transaction_test', function ($table) {
            $table->id();
            $table->string('value');
        });

        DB::beginTransaction();
        DB::table('transaction_test')->insert(['value' => 'test']);
        DB::commit();

        $count = DB::table('transaction_test')->count();
        expect($count)->toBe(1);

        Schema::dropIfExists('transaction_test');
    });
});

describe('Multi-Component Workflow - Data Validation', function () {
    test('email validation works correctly', function () {
        $validEmail = 'test@example.com';
        $invalidEmail = 'not-an-email';

        expect(filter_var($validEmail, FILTER_VALIDATE_EMAIL))->toBe($validEmail);
        expect(filter_var($invalidEmail, FILTER_VALIDATE_EMAIL))->toBeFalse();
    });

    test('password hashing and verification work', function () {
        $password = 'secret123';
        $hash = password_hash($password, PASSWORD_DEFAULT);

        expect(password_verify($password, $hash))->toBeTrue();
        expect(password_verify('wrong', $hash))->toBeFalse();
    });
});

describe('Multi-Component Workflow - Complete User Registration', function () {
    test('user registration workflow from validation to database', function () {
        Schema::create('users_workflow', function ($table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        // Simulate registration data
        $email = 'newuser@example.com';
        $password = 'secret123';

        // Validate email
        $isValidEmail = filter_var($email, FILTER_VALIDATE_EMAIL);
        expect($isValidEmail)->toBe($email);

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Save to database
        $result = DB::table('users_workflow')->insert([
            'email' => $email,
            'password' => $hashedPassword,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        expect($result)->toBeTrue();

        $user = DB::table('users_workflow')->where('email', $email)->first();
        expect($user)->not->toBeNull();
        expect($user->email)->toBe($email);

        Schema::dropIfExists('users_workflow');
    });
});

describe('Multi-Component Workflow - Data Processing', function () {
    test('complete data processing workflow', function () {
        Schema::create('data_workflow', function ($table) {
            $table->id();
            $table->string('status');
            $table->integer('value');
        });

        // Insert test data
        for ($i = 1; $i <= 5; $i++) {
            DB::table('data_workflow')->insert([
                'status' => $i % 2 === 0 ? 'active' : 'inactive',
                'value' => $i * 10,
            ]);
        }

        // Process data - get active records
        $activeRecords = DB::table('data_workflow')
            ->where('status', 'active')
            ->get();

        expect($activeRecords)->toBeArray();
        expect(count($activeRecords))->toBe(2);

        // Calculate sum
        $sum = array_sum(array_column($activeRecords, 'value'));
        expect($sum)->toBe(60); // 20 + 40

        Schema::dropIfExists('data_workflow');
    });
});

describe('Multi-Component Workflow - Error Handling', function () {
    test('database errors are caught gracefully', function () {
        try {
            DB::table('non_existent_table')->get();
            $errorCaught = false;
        } catch (Exception $e) {
            $errorCaught = true;
        }

        expect($errorCaught)->toBeTrue();
    });

    test('invalid data is rejected before database', function () {
        $invalidEmail = 'not-an-email';
        $isValid = filter_var($invalidEmail, FILTER_VALIDATE_EMAIL);

        expect($isValid)->toBeFalse();
    });
});
