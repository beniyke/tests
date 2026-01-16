<?php

declare(strict_types=1);

use Database\DB;
use Database\Schema\Schema;
use Helpers\File\Paths;
use Testing\Support\DatabaseTestHelper;

beforeEach(function () {
    // Setup in-memory database
    DatabaseTestHelper::resetDefaultConnection();
    $this->connection = DatabaseTestHelper::setupInMemoryDatabase();
    DB::setDefaultConnection($this->connection);
    Schema::setConnection($this->connection);
});

afterEach(function () {
    if (isset($this->connection)) {
        $this->connection->clearStatementCache();
    }
    DatabaseTestHelper::resetDefaultConnection();
});

describe('Schema Index Checking', function () {
    beforeEach(function () {
        // Create test tables via migration
        DatabaseTestHelper::runMigrationsFrom(Paths::basePath('System/Testing/Fixtures/Migrations'));
    });

    test('hasIndex returns true for existing index', function () {
        expect(Schema::hasIndex('test_index_table', 'test_index_table_name_index'))->toBeTrue();
    });

    test('hasIndex returns false for non-existing index', function () {
        expect(Schema::hasIndex('test_index_table', 'non_existing_index'))->toBeFalse();
    });

    test('whenTableHasIndex executes callback when index exists', function () {
        $executed = false;

        Schema::whenTableHasIndex('test_index_table', 'test_index_table_name_index', function ($table) use (&$executed) {
            $executed = true;
            // Don't actually drop the index, just verify callback was called
        });

        expect($executed)->toBeTrue();
    });

    test('whenTableHasIndex skips callback when index does not exist', function () {
        $executed = false;

        Schema::whenTableHasIndex('test_index_table', 'non_existing_index', function ($table) use (&$executed) {
            $executed = true;
        });

        expect($executed)->toBeFalse();
    });

    test('whenTableDoesntHaveIndex executes callback when index does not exist', function () {
        $executed = false;

        Schema::whenTableDoesntHaveIndex('test_index_table', 'test_index_table_email_index', function ($table) use (&$executed) {
            $executed = true;
            $table->index('email', 'test_index_table_email_index');
        });

        expect($executed)->toBeTrue();
        expect(Schema::hasIndex('test_index_table', 'test_index_table_email_index'))->toBeTrue();
    });

    test('whenTableDoesntHaveIndex skips callback when index exists', function () {
        $executed = false;

        Schema::whenTableDoesntHaveIndex('test_index_table', 'test_index_table_name_index', function ($table) use (&$executed) {
            $executed = true;
        });

        expect($executed)->toBeFalse();
    });

    test('whenTableHasIndex can safely drop existing index', function () {
        Schema::whenTableHasIndex('test_index_table', 'test_index_table_name_index', function ($table) {
            $table->dropIndex('test_index_table_name_index');
        });

        expect(Schema::hasIndex('test_index_table', 'test_index_table_name_index'))->toBeFalse();
    });

    test('whenTableDoesntHaveIndex makes migrations idempotent', function () {
        // First run - should create index
        Schema::whenTableDoesntHaveIndex('test_index_table', 'test_index_table_email_index', function ($table) {
            $table->index('email', 'test_index_table_email_index');
        });

        expect(Schema::hasIndex('test_index_table', 'test_index_table_email_index'))->toBeTrue();

        // Second run - should skip (no error)
        Schema::whenTableDoesntHaveIndex('test_index_table', 'test_index_table_email_index', function ($table) {
            $table->index('email', 'test_index_table_email_index');
        });

        expect(Schema::hasIndex('test_index_table', 'test_index_table_email_index'))->toBeTrue();
    });
});
