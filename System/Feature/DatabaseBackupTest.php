<?php

declare(strict_types=1);

use Cli\Build\DBA;
use Database\Connection;
use Database\DB;
use Database\Helpers\DatabaseOperationConfig;
use Helpers\File\Adapters\Interfaces\FileManipulationInterface;
use Helpers\File\Adapters\Interfaces\FileMetaInterface;
use Helpers\File\Adapters\Interfaces\FileReadWriteInterface;
use Helpers\File\Adapters\Interfaces\PathResolverInterface;
use Helpers\File\FileSystem;
use Helpers\File\Paths;

/**
 * Database Backup and Restore Test
 *
 * Note: This test uses a file-based SQLite database instead of the in-memory
 * database provided by TestCase::setUpDatabase() because backup/restore operations
 * require actual file I/O to test properly.
 */
beforeEach(function () {
    // Define test paths
    $this->backupDir = Paths::testPath('storage/backups');
    $this->dbFile = Paths::testPath('storage/test_backup.sqlite');

    // Ensure clean state using FileSystem facade
    FileSystem::mkdir($this->backupDir, 0777, true);
    FileSystem::put($this->dbFile, '');

    // Configure and connect to the test database
    $this->connection = Connection::configure("sqlite:$this->dbFile")->connect();

    // Store original connection before replacing
    $this->originalConnection = DB::connection();

    // Set as default connection
    DB::setDefaultConnection($this->connection);

    // Configure DBA with test-specific settings
    $config = [
        'driver' => 'sqlite',
        'connections' => [
            'sqlite' => [
                'driver' => 'sqlite',
                'path' => dirname($this->dbFile),
                'file' => basename($this->dbFile),
            ],
        ],
        'database' => $this->dbFile,
    ];

    // Note: DatabaseOperationConfig::getBackupPath() calls Paths::basePath(), so we provide a relative path
    $operationConfig = new DatabaseOperationConfig([
        'operations' => [
            'backup' => ['path' => 'tests/storage/backups'],
        ],
    ]);

    // Bind DBA - container auto-resolves file system dependencies
    container()->singleton(DBA::class, function ($container) use ($config, $operationConfig) {
        return new DBA(
            $container->get(PathResolverInterface::class),
            $container->get(FileManipulationInterface::class),
            $container->get(FileMetaInterface::class),
            $container->get(FileReadWriteInterface::class),
            $config,
            $operationConfig,
            $this->connection
        );
    });
});

afterEach(function () {
    if ($this->connection) {
        $this->connection->disconnect();
        $this->connection = null;
    }

    // Restore original connection to prevent state pollution
    if (isset($this->originalConnection) && $this->originalConnection) {
        DB::setDefaultConnection($this->originalConnection);
    }

    // Allow time for file locks to release on Windows
    usleep(200000); // 200ms

    // Cleanup using FileSystem facade
    // Note: On Windows, SQLite file locks can persist briefly even after disconnect
    // We use @ suppression as a last resort since the files will be cleaned up on next run
    if (FileSystem::exists($this->dbFile)) {
        @FileSystem::delete($this->dbFile);
    }

    if (FileSystem::isDir($this->backupDir)) {
        @FileSystem::delete($this->backupDir);
    }
});

// Test SQLite backup and restore functionality
test('sqlite backup and restore', function () {
    // 1. Setup Data - create table and insert test records
    $this->connection->statement('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');
    $this->connection->statement("INSERT INTO users (name) VALUES ('Alice')");
    $this->connection->statement("INSERT INTO users (name) VALUES ('Bob')");

    // 2. Export Database - create backup file
    /** @var DBA $dba */
    $dba = resolve(DBA::class);

    $result = $dba->exportDatabase();
    expect($result['status'])->toBeTrue('Export failed: ' . ($result['message'] ?? ''));
    $backupFile = basename($result['filepath']);

    // 3. Wipe Data - delete all records to simulate data loss
    $this->connection->delete('DELETE FROM users');
    $count = $this->connection->table('users')->count();
    expect($count)->toBe(0);

    // 4. Import Database - restore from backup
    $result = $dba->importDatabase($backupFile);
    expect($result['status'])->toBeTrue('Import failed: ' . ($result['message'] ?? ''));

    // 5. Verify Data Restored - check that all data is back
    $count = $this->connection->table('users')->count();
    expect($count)->toBe(2);

    $names = $this->connection->table('users')->orderBy('id')->pluck('name');
    expect($names)->toBe(['Alice', 'Bob']);
});
