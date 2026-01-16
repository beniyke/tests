<?php

declare(strict_types=1);

use Database\Connection;
use Database\DB;
use Database\Migration\MigrationRepository;
use Database\Schema\Schema;

beforeEach(function () {
    // Create SQLite in-memory connection
    $this->connection = Connection::configure('sqlite::memory:')
        ->name('test_migrations')
        ->connect()
        ->initCommand('PRAGMA foreign_keys = ON');

    // Set as default connection
    DB::setConnection($this->connection);
    Schema::setConnection($this->connection);
});

afterEach(function () {
    // Clean up
    $tables = $this->connection->getTables();
    foreach ($tables as $table) {
        try {
            Schema::dropIfExists($table);
        } catch (Exception $e) {
            // Ignore
        }
    }
});

describe('Migration - Repository', function () {
    test('creates migration repository', function () {
        $repo = new MigrationRepository($this->connection);

        expect($repo)->toBeInstanceOf(MigrationRepository::class);
    });

    test('logs migration', function () {
        $repo = new MigrationRepository($this->connection);

        $repo->log('2024_01_01_000000_create_users_table', 1);

        $migrations = $this->connection->table('migrations')->get();
        expect($migrations)->toHaveCount(1);
        expect($migrations[0]['migration'])->toBe('2024_01_01_000000_create_users_table');
        expect($migrations[0]['batch'])->toBe(1);
    });

    test('gets ran migrations', function () {
        $repo = new MigrationRepository($this->connection);

        $repo->log('2024_01_01_000000_create_users_table', 1);
        $repo->log('2024_01_02_000000_create_posts_table', 1);

        $ran = $repo->getRan();

        expect($ran)->toBeArray();
        expect($ran)->toHaveCount(2);
        expect($ran)->toContain('2024_01_01_000000_create_users_table');
    });

    test('gets current batch number', function () {
        $repo = new MigrationRepository($this->connection);

        $repo->log('2024_01_01_000000_create_users_table', 1);
        $repo->log('2024_01_02_000000_create_posts_table', 2);

        $currentBatch = $repo->getCurrentBatchNumber();

        expect($currentBatch)->toBe(2);
    });

    test('gets last batch migrations', function () {
        $repo = new MigrationRepository($this->connection);

        $repo->log('2024_01_01_000000_create_users_table', 1);
        $repo->log('2024_01_02_000000_create_posts_table', 2);
        $repo->log('2024_01_03_000000_create_comments_table', 2);

        $migrations = $repo->getLastBatch();

        expect($migrations)->toBeArray();
        expect($migrations)->toHaveCount(2); // Last batch has 2 migrations
    });

    test('deletes migration', function () {
        $repo = new MigrationRepository($this->connection);

        $repo->log('2024_01_01_000000_create_users_table', 1);

        $repo->delete('2024_01_01_000000_create_users_table');

        $migrations = $this->connection->table('migrations')->get();
        expect($migrations)->toHaveCount(0);
    });

    test('gets next batch number', function () {
        $repo = new MigrationRepository($this->connection);

        $repo->log('2024_01_01_000000_create_users_table', 1);

        $nextBatch = $repo->getNextBatchNumber();

        expect($nextBatch)->toBe(2);
    });
});

describe('Migration - Schema Operations', function () {
    test('runs migration up', function () {
        // Create a simple migration
        Schema::create('test_users', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        expect($this->connection->tableExists('test_users'))->toBeTrue();
        expect($this->connection->columnExists('test_users', 'name'))->toBeTrue();
    });

    test('runs migration down', function () {
        // Create table
        Schema::create('test_users', function ($table) {
            $table->id();
            $table->string('name');
        });

        expect($this->connection->tableExists('test_users'))->toBeTrue();

        // Drop table (migration down)
        Schema::drop('test_users');

        expect($this->connection->tableExists('test_users'))->toBeFalse();
    });

    test('handles migration with foreign keys', function () {
        // Create parent table
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
        });

        // Create child table with foreign key
        Schema::create('posts', function ($table) {
            $table->id();
            $table->integer('user_id');
            $table->string('title');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');
        });

        expect($this->connection->tableExists('users'))->toBeTrue();
        expect($this->connection->tableExists('posts'))->toBeTrue();
    });
});

describe('Migration - Batch Tracking', function () {
    test('tracks migration batches', function () {
        $repo = new MigrationRepository($this->connection);

        // Batch 1
        $repo->log('2024_01_01_000000_create_users_table', 1);
        $repo->log('2024_01_02_000000_create_posts_table', 1);

        // Batch 2
        $repo->log('2024_01_03_000000_create_comments_table', 2);

        $batch1 = $this->connection->table('migrations')->where('batch', 1)->get();
        $batch2 = $this->connection->table('migrations')->where('batch', 2)->get();

        expect($batch1)->toHaveCount(2);
        expect($batch2)->toHaveCount(1);
    });

    test('rolls back last batch', function () {
        $repo = new MigrationRepository($this->connection);

        // Batch 1
        $repo->log('2024_01_01_000000_create_users_table', 1);

        // Batch 2
        $repo->log('2024_01_02_000000_create_posts_table', 2);
        $repo->log('2024_01_03_000000_create_comments_table', 2);

        // Get last batch
        $lastBatch = $repo->getLastBatch();

        // Delete last batch
        foreach ($lastBatch as $migration) {
            $repo->delete($migration['migration']);
        }

        $remaining = $this->connection->table('migrations')->get();
        expect($remaining)->toHaveCount(1);
        expect($remaining[0]['batch'])->toBe(1);
    });
});

describe('Migration - Error Handling', function () {
    test('handles duplicate table creation', function () {
        Schema::create('test_table', function ($table) {
            $table->id();
        });

        expect(function () {
            Schema::create('test_table', function ($table) {
                $table->id();
            });
        })->toThrow(Exception::class);
    });

    test('handles dropping non-existent table', function () {
        // dropIfExists should not throw
        Schema::dropIfExists('non_existent_table');

        expect(true)->toBeTrue();
    });

    test('handles invalid foreign key reference', function () {
        $conn = DB::connection();

        skipOnSqlite('SQLite deferred foreign key validation');
        expect(function () {
            Schema::create('posts', function ($table) {
                $table->id();
                $table->integer('user_id');
                // This will fail because 'users' table doesn't exist
                $table->foreign('user_id')->references('id')->on('users');
            });
        })->toThrow(Exception::class);
    });
});

describe('Migration - Complex Scenarios', function () {
    test('runs multiple migrations in order', function () {
        $repo = new MigrationRepository($this->connection);

        // Migration 1: Create users
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
        });
        $repo->log('2024_01_01_000000_create_users_table', 1);

        // Migration 2: Create posts with foreign key
        Schema::create('posts', function ($table) {
            $table->id();
            $table->integer('user_id');
            $table->string('title');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');
        });
        $repo->log('2024_01_02_000000_create_posts_table', 1);

        $migrations = $repo->getRan();
        expect($migrations)->toHaveCount(2);
    });

    test('handles migration rollback in reverse order', function () {
        $this->markTestSkipped('Skipped due to SQLite table locking when iterating and deleting from same table');
    });
});

describe('Migration - Migrator Methods', function () {
    test('getPendingMigrations returns only unrun migrations', function () {
        $tempDir = sys_get_temp_dir() . '/test_migrations_' . uniqid();
        mkdir($tempDir, 0755, true);

        // Create test migration files
        file_put_contents($tempDir . '/2024_01_01_000000_create_users_table.php', '<?php');
        file_put_contents($tempDir . '/2024_01_02_000000_create_posts_table.php', '<?php');
        file_put_contents($tempDir . '/2024_01_03_000000_create_comments_table.php', '<?php');

        $migrator = new Database\Migration\Migrator($this->connection, $tempDir);
        $repo = $migrator->getRepository();

        // Mark first two as run
        $repo->log('2024_01_01_000000_create_users_table', 1);
        $repo->log('2024_01_02_000000_create_posts_table', 1);

        $pending = $migrator->getPendingMigrations();

        expect($pending)->toBeArray();
        expect($pending)->toHaveCount(1);
        expect($pending)->toContain('2024_01_03_000000_create_comments_table');

        // Cleanup
        array_map('unlink', glob($tempDir . '/*.php'));
        rmdir($tempDir);
    });

    test('runFiles executes only specified migration files', function () {
        $tempDir = sys_get_temp_dir() . '/test_migrations_' . uniqid();
        mkdir($tempDir, 0755, true);

        // Create test migration files with actual migration classes
        $migration1 = <<<'PHP'
<?php
use Database\Migration\BaseMigration;
use Database\Schema\Schema;

class CreateUsersTableTest1 extends BaseMigration
{
    public function up(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
        });
    }

    public function down(): void
    {
        Schema::drop('users');
    }
}
PHP;

        $migration2 = <<<'PHP'
<?php
use Database\Migration\BaseMigration;
use Database\Schema\Schema;

class CreatePostsTableTest1 extends BaseMigration
{
    public function up(): void
    {
        Schema::create('posts', function ($table) {
            $table->id();
            $table->string('title');
        });
    }

    public function down(): void
    {
        Schema::drop('posts');
    }
}
PHP;

        file_put_contents($tempDir . '/2024_01_01_000000_create_users_table_test1.php', $migration1);
        file_put_contents($tempDir . '/2024_01_02_000000_create_posts_table_test1.php', $migration2);

        $migrator = new Database\Migration\Migrator($this->connection, $tempDir);

        // Run only the first migration
        $results = $migrator->runFiles(['2024_01_01_000000_create_users_table_test1']);

        expect($results)->toBeArray();
        expect($results)->toHaveCount(1);
        expect($results[0]['file'])->toBe('2024_01_01_000000_create_users_table_test1');
        expect($this->connection->tableExists('users'))->toBeTrue();
        expect($this->connection->tableExists('posts'))->toBeFalse();

        // Cleanup
        array_map('unlink', glob($tempDir . '/*.php'));
        rmdir($tempDir);
    });

    test('runFiles skips already run migrations', function () {
        $tempDir = sys_get_temp_dir() . '/test_migrations_' . uniqid();
        mkdir($tempDir, 0755, true);

        $migration = <<<'PHP'
<?php
use Database\Migration\BaseMigration;
use Database\Schema\Schema;

class CreateUsersTableTest2 extends BaseMigration
{
    public function up(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
        });
    }

    public function down(): void
    {
        Schema::drop('users');
    }
}
PHP;

        file_put_contents($tempDir . '/2024_01_01_000000_create_users_table_test2.php', $migration);

        $migrator = new Database\Migration\Migrator($this->connection, $tempDir);
        $repo = $migrator->getRepository();

        // Mark as already run
        $repo->log('2024_01_01_000000_create_users_table_test2', 1);

        // Try to run it again
        $results = $migrator->runFiles(['2024_01_01_000000_create_users_table_test2']);

        expect($results)->toBeArray();
        expect($results)->toHaveCount(0); // Should skip already run migration

        // Cleanup
        unlink($tempDir . '/2024_01_01_000000_create_users_table_test2.php');
        rmdir($tempDir);
    });

    test('runFiles handles multiple files', function () {
        $tempDir = sys_get_temp_dir() . '/test_migrations_' . uniqid();
        mkdir($tempDir, 0755, true);

        $migration1 = <<<'PHP'
<?php
use Database\Migration\BaseMigration;
use Database\Schema\Schema;

class CreateUsersTableTest3 extends BaseMigration
{
    public function up(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
        });
    }

    public function down(): void
    {
        Schema::drop('users');
    }
}
PHP;

        $migration2 = <<<'PHP'
<?php
use Database\Migration\BaseMigration;
use Database\Schema\Schema;

class CreatePostsTableTest3 extends BaseMigration
{
    public function up(): void
    {
        Schema::create('posts', function ($table) {
            $table->id();
        });
    }

    public function down(): void
    {
        Schema::drop('posts');
    }
}
PHP;

        file_put_contents($tempDir . '/2024_01_01_000000_create_users_table_test3.php', $migration1);
        file_put_contents($tempDir . '/2024_01_02_000000_create_posts_table_test3.php', $migration2);

        $migrator = new Database\Migration\Migrator($this->connection, $tempDir);

        // Run both migrations
        $results = $migrator->runFiles([
            '2024_01_01_000000_create_users_table_test3',
            '2024_01_02_000000_create_posts_table_test3'
        ]);

        expect($results)->toHaveCount(2);
        expect($this->connection->tableExists('users'))->toBeTrue();
        expect($this->connection->tableExists('posts'))->toBeTrue();

        // Cleanup
        array_map('unlink', glob($tempDir . '/*.php'));
        rmdir($tempDir);
    });
});
