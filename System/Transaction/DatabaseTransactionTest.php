<?php

declare(strict_types=1);

use Database\DB;
use Helpers\File\Paths;
use Testing\Support\DatabaseTestHelper;
use Tests\System\Fixtures\Models\TestUser;

beforeEach(function () {
    // Manually setup schema for these tests since we don't use RefreshDatabase
    DatabaseTestHelper::runMigrationsFrom(Paths::basePath('System/Testing/Fixtures/Migrations'));

    // Clean up table
    DB::table('test_rel_feature_users')->truncate();
});

describe('Feature - Database Transactions', function () {
    test('commits transaction on success', function () {
        DB::transaction(function () {
            TestUser::create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

            TestUser::create([
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
            ]);
        });

        $count = TestUser::count();
        expect($count)->toBe(2);
    });

    test('rolls back transaction on error', function () {
        try {
            DB::transaction(function () {
                TestUser::create([
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ]);

                throw new Exception('Rollback test');
            });
        } catch (Exception $e) {
            // Expected exception
        }

        $count = TestUser::count();
        expect($count)->toBe(0);
    });

    test('executes after commit callbacks', function () {
        $callbackExecuted = false;

        DB::transaction(function ($conn) use (&$callbackExecuted) {
            $conn->afterCommit(function () use (&$callbackExecuted) {
                $callbackExecuted = true;
            });

            TestUser::create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);
        });

        expect($callbackExecuted)->toBeTrue();
    });
});
