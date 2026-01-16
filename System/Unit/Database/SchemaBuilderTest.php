<?php

declare(strict_types=1);

use Database\Connection;
use Database\DB;
use Database\Schema\Schema;
use Database\Schema\SchemaBuilder;

beforeEach(function () {
    // Create SQLite in-memory connection for testing
    $this->connection = Connection::configure('sqlite::memory:')
        ->name('test_schema')
        ->connect();

    // Set connection for Schema facade
    Schema::setConnection($this->connection);
    DB::setDefaultConnection($this->connection);

    // Clear global callbacks to prevent interference from other tests
    Connection::$queryCallbacks = [];
});

afterEach(function () {
    // Clean up any created tables
    $tables = $this->connection->getTables();
    foreach ($tables as $table) {
        try {
            Schema::drop($table);
        } catch (Exception $e) {
            // Ignore cleanup errors
        }
    }
});

describe('Schema - Table Creation', function () {
    test('creates table with basic structure', function () {
        Schema::create('user', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        expect($this->connection->tableExists('user'))->toBeTrue();
    });

    test('creates table with custom primary key', function () {
        Schema::create('post', function ($table) {
            $table->string('slug')->unique();
            $table->string('title');
            $table->primary('slug');
        });

        expect($this->connection->tableExists('post'))->toBeTrue();
    });

    test('creates table with engine option', function () {
        // Engine is MySQL-specific, but we test the method exists
        $builder = new SchemaBuilder($this->connection, 'test_table', 'create');

        expect(method_exists($builder, 'engine'))->toBeTrue();
    });

    test('creates table with charset and collation', function () {
        $builder = new SchemaBuilder($this->connection, 'test_table', 'create');

        expect(method_exists($builder, 'charset'))->toBeTrue();
        expect(method_exists($builder, 'collation'))->toBeTrue();
    });

    test('creates table with comment', function () {
        $builder = new SchemaBuilder($this->connection, 'test_table', 'create');

        expect(method_exists($builder, 'comment'))->toBeTrue();
    });
});

describe('Schema - Column Types', function () {
    test('creates string column', function () {
        Schema::create('user', function ($table) {
            $table->id();
            $table->string('name', 100);
        });

        expect($this->connection->columnExists('user', 'name'))->toBeTrue();
    });

    test('creates text columns', function () {
        Schema::create('post', function ($table) {
            $table->id();
            $table->tinyText('excerpt');
            $table->text('content');
            $table->mediumText('body');
            $table->longText('description');
        });

        expect($this->connection->columnExists('post', 'excerpt'))->toBeTrue();
        expect($this->connection->columnExists('post', 'content'))->toBeTrue();
    });

    test('creates integer columns', function () {
        Schema::create('stat', function ($table) {
            $table->id();
            $table->tinyInteger('tiny_num');
            $table->smallInteger('small_num');
            $table->mediumInteger('medium_num');
            $table->integer('int_num');
            $table->bigInteger('big_num');
        });

        expect($this->connection->columnExists('stat', 'tiny_num'))->toBeTrue();
        expect($this->connection->columnExists('stat', 'int_num'))->toBeTrue();
    });

    test('creates decimal and float columns', function () {
        Schema::create('product', function ($table) {
            $table->id();
            $table->decimal('price', 10, 2);
            $table->float('weight', 8, 2);
            $table->double('distance', 15, 8);
        });

        expect($this->connection->columnExists('product', 'price'))->toBeTrue();
        expect($this->connection->columnExists('product', 'weight'))->toBeTrue();
    });

    test('creates date and time columns', function () {
        Schema::create('event', function ($table) {
            $table->id();
            $table->date('event_date');
            $table->time('event_time');
            $table->dateTime('event_datetime');
            $table->timestamp('event_timestamp');
            $table->year('event_year');
        });

        expect($this->connection->columnExists('event', 'event_date'))->toBeTrue();
        expect($this->connection->columnExists('event', 'event_time'))->toBeTrue();
    });

    test('creates boolean column', function () {
        Schema::create('setting', function ($table) {
            $table->id();
            $table->boolean('is_active');
        });

        expect($this->connection->columnExists('setting', 'is_active'))->toBeTrue();
    });

    test('creates json column', function () {
        Schema::create('data', function ($table) {
            $table->id();
            $table->json('metadata');
        });

        expect($this->connection->columnExists('data', 'metadata'))->toBeTrue();
    });

    test('creates enum column', function () {
        Schema::create('user', function ($table) {
            $table->id();
            $table->enum('status', ['active', 'inactive', 'pending']);
        });

        expect($this->connection->columnExists('user', 'status'))->toBeTrue();
    });
});

describe('Schema - Column Modifiers', function () {
    test('makes column nullable', function () {
        Schema::create('user', function ($table) {
            $table->id();
            $table->string('name')->nullable();
        });

        expect($this->connection->columnExists('user', 'name'))->toBeTrue();
    });

    test('sets default value', function () {
        Schema::create('user', function ($table) {
            $table->id();
            $table->string('status')->default('active');
            $table->integer('count')->default(0);
        });

        expect($this->connection->columnExists('user', 'status'))->toBeTrue();
    });

    test('makes column unsigned', function () {
        Schema::create('stat', function ($table) {
            $table->id();
            $table->integer('views')->unsigned();
        });

        expect($this->connection->columnExists('stat', 'views'))->toBeTrue();
    });

    test('sets auto increment', function () {
        Schema::create('user', function ($table) {
            $table->bigInteger('id')->unsigned()->autoIncrement()->primary('id');
            $table->string('name');
        });

        expect($this->connection->columnExists('user', 'id'))->toBeTrue();
    });

    test('adds column comment', function () {
        Schema::create('user', function ($table) {
            $table->id();
            $table->string('name')->columnComment('User full name');
        });

        expect($this->connection->columnExists('user', 'name'))->toBeTrue();
    });
});

describe('Schema - Timestamps and Soft Deletes', function () {
    test('adds timestamps', function () {
        Schema::create('user', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        expect($this->connection->columnExists('user', 'created_at'))->toBeTrue();
        expect($this->connection->columnExists('user', 'updated_at'))->toBeTrue();
    });

    test('adds datetime timestamps', function () {
        Schema::create('post', function ($table) {
            $table->id();
            $table->string('title');
            $table->dateTimestamps();
        });

        expect($this->connection->columnExists('post', 'created_at'))->toBeTrue();
        expect($this->connection->columnExists('post', 'updated_at'))->toBeTrue();
    });

    test('adds soft deletes', function () {
        Schema::create('user', function ($table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
        });

        expect($this->connection->columnExists('user', 'deleted_at'))->toBeTrue();
    });

    test('adds soft deletes with timezone', function () {
        Schema::create('post', function ($table) {
            $table->id();
            $table->string('title');
            $table->softDeletesTz();
        });

        expect($this->connection->columnExists('post', 'deleted_at'))->toBeTrue();
    });
});

describe('Schema - Indexes', function () {
    test('creates index on column', function () {
        Schema::create('user', function ($table) {
            $table->id();
            $table->string('email')->index();
        });

        expect($this->connection->tableExists('user'))->toBeTrue();
    });

    test('creates unique index', function () {
        Schema::create('user', function ($table) {
            $table->id();
            $table->string('email')->unique();
        });

        expect($this->connection->tableExists('user'))->toBeTrue();
    });

    test('creates composite index', function () {
        Schema::create('user', function ($table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->index(['first_name', 'last_name'], 'name_index');
        });

        expect($this->connection->tableExists('user'))->toBeTrue();
    });

    test('creates fulltext index', function () {
        Schema::create('post', function ($table) {
            $table->id();
            $table->text('content');
            $table->fullText('content', 'content_fulltext');
        });

        expect($this->connection->tableExists('post'))->toBeTrue();
    });
});

describe('Schema - Foreign Keys', function () {
    test('creates foreign key constraint', function () {
        Schema::create('user', function ($table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('post', function ($table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->string('title');
            $table->foreign('user_id')->references('id')->on('user')->onDelete('CASCADE');
        });

        expect($this->connection->tableExists('post'))->toBeTrue();
    });

    test('creates foreign key with on update', function () {
        Schema::create('category', function ($table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('product', function ($table) {
            $table->id();
            $table->bigInteger('category_id')->unsigned();
            $table->string('name');
            $table->foreign('category_id')
                ->references('id')
                ->on('category')
                ->onDelete('SET NULL')
                ->onUpdate('CASCADE');
        });

        expect($this->connection->tableExists('product'))->toBeTrue();
    });
});

describe('Schema - Table Alterations', function () {
    test('adds column to existing table', function () {
        Schema::create('user', function ($table) {
            $table->id();
            $table->string('name');
        });

        Schema::table('user', function ($table) {
            $table->string('email')->nullable();
        });

        expect($this->connection->columnExists('user', 'email'))->toBeTrue();
    });

    test('drops column from table', function () {
        skipOnSqlite('SQLite does not support DROP COLUMN via ALTER TABLE in all versions/configurations');
        Schema::create('user', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
        });

        Schema::table('user', function ($table) {
            $table->dropColumn('email');
        });

        expect($this->connection->columnExists('user', 'email'))->toBeFalse();
    });

    test('drops multiple columns', function () {
        skipOnSqlite('SQLite does not support DROP COLUMN');
        Schema::create('user', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
        });

        Schema::table('user', function ($table) {
            $table->dropColumn(['email', 'phone']);
        });

        expect($this->connection->columnExists('user', 'email'))->toBeFalse();
        expect($this->connection->columnExists('user', 'phone'))->toBeFalse();
    });

    test('renames column', function () {
        skipOnSqlite('SQLite does not support RENAME COLUMN in all versions');
        Schema::create('user', function ($table) {
            $table->id();
            $table->string('name');
        });

        Schema::table('user', function ($table) {
            $table->renameColumn('name', 'full_name', 'VARCHAR(255)');
        });

        expect($this->connection->columnExists('user', 'full_name'))->toBeTrue();
    });

    test('modifies column', function () {
        skipOnSqlite('SQLite does not support MODIFY COLUMN');
        Schema::create('user', function ($table) {
            $table->id();
            $table->string('name', 50);
        });

        Schema::table('user', function ($table) {
            $table->string('name', 100)->change();
        });

        expect($this->connection->columnExists('user', 'name'))->toBeTrue();
    });
});

describe('Schema - Index Operations', function () {
    test('drops index', function () {
        skipOnSqlite('SQLite DROP INDEX behavior varies');
        Schema::create('user', function ($table) {
            $table->id();
            $table->string('email');
            $table->index('email', 'email_index');
        });

        Schema::table('user', function ($table) {
            $table->dropIndex('email_index');
        });

        expect($this->connection->tableExists('user'))->toBeTrue();
    });

    test('drops unique constraint', function () {
        skipOnSqlite('SQLite does not support DROP CONSTRAINT');
        Schema::create('user', function ($table) {
            $table->id();
            $table->string('email');
            $table->unique('email', 'email_unique');
        });

        Schema::table('user', function ($table) {
            $table->dropUnique('email_unique');
        });

        expect($this->connection->tableExists('user'))->toBeTrue();
    });

    test('drops foreign key', function () {
        skipOnSqlite('SQLite does not support DROP FOREIGN KEY');
        Schema::create('user', function ($table) {
            $table->id();
        });

        Schema::create('post', function ($table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('user')->onDelete('CASCADE');
        });

        Schema::table('post', function ($table) {
            $table->dropForeign('user_id');
        });

        expect($this->connection->tableExists('post'))->toBeTrue();
    });
});

describe('Schema - Table Operations', function () {
    test('drops table', function () {
        Schema::create('temp_table', function ($table) {
            $table->id();
        });

        expect($this->connection->tableExists('temp_table'))->toBeTrue();

        Schema::drop('temp_table');

        expect($this->connection->tableExists('temp_table'))->toBeFalse();
    });

    test('drops table if exists', function () {
        Schema::dropIfExists('nonexistent_table');

        // Should not throw exception
        expect(true)->toBeTrue();
    });

    test('checks if table exists', function () {
        Schema::create('user', function ($table) {
            $table->id();
        });

        expect(Schema::hasTable('user'))->toBeTrue();
        expect(Schema::hasTable('nonexistent'))->toBeFalse();
    });
});

describe('Schema - Raw SQL', function () {
    test('executes raw SQL in schema', function () {
        Schema::create('user', function ($table) {
            $table->id();
            $table->string('name');
            $table->raw('CHECK (LENGTH(name) > 0)');
        });

        expect($this->connection->tableExists('user'))->toBeTrue();
    });
});
describe('Schema - Environment Aware', function () {
    test('whenEnvironment executes callback when environment matches', function () {
        putenv('APP_ENV=prod');
        $executed = false;

        $builder = new SchemaBuilder($this->connection, 'test_table', 'create');
        $builder->whenEnvironment('prod', function ($builder) use (&$executed) {
            $executed = true;
            expect($builder)->toBeInstanceOf(SchemaBuilder::class);
        });

        expect($executed)->toBeTrue();
        putenv('APP_ENV'); // Clear
    });

    test('whenEnvironment ignores callback when environment mismatch', function () {
        putenv('APP_ENV=dev');
        $executed = false;

        $builder = new SchemaBuilder($this->connection, 'test_table', 'create');
        $builder->whenEnvironment('prod', function () use (&$executed) {
            $executed = true;
        });

        expect($executed)->toBeFalse();
        putenv('APP_ENV');
    });

    test('whenNotEnvironment executes callback when environment mismatch', function () {
        putenv('APP_ENV=dev');
        $executed = false;

        $builder = new SchemaBuilder($this->connection, 'test_table', 'create');
        $builder->whenNotEnvironment('prod', function () use (&$executed) {
            $executed = true;
        });

        expect($executed)->toBeTrue();
        putenv('APP_ENV');
    });

    test('whenNotEnvironment ignores callback when environment matches', function () {
        putenv('APP_ENV=prod');
        $executed = false;

        $builder = new SchemaBuilder($this->connection, 'test_table', 'create');
        $builder->whenNotEnvironment('prod', function () use (&$executed) {
            $executed = true;
        });

        expect($executed)->toBeFalse();
        putenv('APP_ENV');
    });

    test('supports array of environments', function () {
        putenv('APP_ENV=staging');
        $executed = false;

        $builder = new SchemaBuilder($this->connection, 'test_table', 'create');
        $builder->whenEnvironment(['prod', 'staging'], function () use (&$executed) {
            $executed = true;
        });

        expect($executed)->toBeTrue();
        putenv('APP_ENV');
    });
});
