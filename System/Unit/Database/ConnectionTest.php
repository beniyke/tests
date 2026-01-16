<?php

declare(strict_types=1);

use Database\Connection;
use Database\ConnectionInterface;

beforeEach(function () {
    // Create SQLite in-memory connection for testing
    $this->connection = Connection::configure('sqlite::memory:')
        ->name('test_connection')
        ->connect();
});

afterEach(function () {
    if ($this->connection) {
        Connection::clearQueryLog();
    }
});

describe('Connection - Configuration and Initialization', function () {
    test('creates connection with DSN', function () {
        $connection = Connection::configure('sqlite::memory:');
        expect($connection)->toBeInstanceOf(ConnectionInterface::class);
    });

    test('sets connection name', function () {
        $connection = Connection::configure('sqlite::memory:')
            ->name('custom_name')
            ->connect();

        expect($connection->getConfig())->toHaveKey('dsn');
    });

    test('sets persistent connection', function () {
        $connection = Connection::configure('sqlite::memory:')
            ->persistent(true);

        expect($connection)->toBeInstanceOf(ConnectionInterface::class);
    });

    test('sets custom PDO options', function () {
        $connection = Connection::configure('sqlite::memory:')
            ->options([PDO::ATTR_TIMEOUT => 10]);

        expect($connection)->toBeInstanceOf(ConnectionInterface::class);
    });

    test('connects to database', function () {
        $connection = Connection::configure('sqlite::memory:')->connect();
        $pdo = $connection->getPdo();

        expect($pdo)->toBeInstanceOf(PDO::class);
    });

    test('detects driver from DSN', function () {
        $connection = Connection::configure('sqlite::memory:')->connect();

        expect($connection->getDriver())->toBe('sqlite');
    });
});

describe('Connection - Query Execution', function () {
    test('executes select query', function () {
        $this->connection->statement('CREATE TABLE test_users (id INTEGER PRIMARY KEY, name TEXT)');
        $this->connection->statement("INSERT INTO test_users (name) VALUES ('John')");

        $results = $this->connection->select('SELECT * FROM test_users');

        expect($results)->toBeArray();
        expect($results)->toHaveCount(1);
        expect($results[0]['name'])->toBe('John');
    });

    test('executes selectOne query', function () {
        $this->connection->statement('CREATE TABLE test_users (id INTEGER PRIMARY KEY, name TEXT)');
        $this->connection->statement("INSERT INTO test_users (name) VALUES ('John')");

        $result = $this->connection->selectOne('SELECT * FROM test_users WHERE name = ?', ['John']);

        expect($result)->toBeArray();
        expect($result['name'])->toBe('John');
    });

    test('executes insert with bindings', function () {
        $this->connection->statement('CREATE TABLE test_users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)');

        $this->connection->execute(
            'INSERT INTO test_users (name, email) VALUES (?, ?)',
            ['Jane', 'jane@example.com']
        );

        $result = $this->connection->selectOne('SELECT * FROM test_users WHERE name = ?', ['Jane']);

        expect($result['email'])->toBe('jane@example.com');
    });

    test('executes update query', function () {
        $this->connection->statement('CREATE TABLE test_users (id INTEGER PRIMARY KEY, name TEXT)');
        $this->connection->statement("INSERT INTO test_users (name) VALUES ('John')");

        $affected = $this->connection->update('UPDATE test_users SET name = ? WHERE name = ?', ['Jane', 'John']);

        expect($affected)->toBe(1);
    });

    test('executes delete query', function () {
        $this->connection->statement('CREATE TABLE test_users (id INTEGER PRIMARY KEY, name TEXT)');
        $this->connection->statement("INSERT INTO test_users (name) VALUES ('John')");

        $affected = $this->connection->delete('DELETE FROM test_users WHERE name = ?', ['John']);

        expect($affected)->toBe(1);
    });

    test('gets last insert ID', function () {
        $this->connection->statement('CREATE TABLE test_users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');

        $id = $this->connection->insertGetId('INSERT INTO test_users (name) VALUES (?)', ['John']);

        expect($id)->toBeGreaterThan(0);
    });
});

describe('Connection - Transactions', function () {
    test('begins transaction', function () {
        $result = $this->connection->beginTransaction();

        expect($result)->toBeTrue();
        expect($this->connection->inTransaction())->toBeTrue();
    });

    test('commits transaction', function () {
        $this->connection->statement('CREATE TABLE test_users (id INTEGER PRIMARY KEY, name TEXT)');

        $this->connection->beginTransaction();
        $this->connection->statement("INSERT INTO test_users (name) VALUES ('John')");
        $this->connection->commit();

        expect($this->connection->inTransaction())->toBeFalse();

        $result = $this->connection->selectOne('SELECT * FROM test_users WHERE name = ?', ['John']);
        expect($result)->not->toBeFalse();
    });

    test('rolls back transaction', function () {
        $this->connection->statement('CREATE TABLE test_users (id INTEGER PRIMARY KEY, name TEXT)');

        $this->connection->beginTransaction();
        $this->connection->statement("INSERT INTO test_users (name) VALUES ('John')");
        $this->connection->rollBack();

        expect($this->connection->inTransaction())->toBeFalse();

        $results = $this->connection->select('SELECT * FROM test_users');
        expect($results)->toHaveCount(0);
    });

    test('executes transaction with callback', function () {
        $this->connection->statement('CREATE TABLE test_users (id INTEGER PRIMARY KEY, name TEXT)');

        $result = $this->connection->transaction(function ($conn) {
            $conn->statement("INSERT INTO test_users (name) VALUES ('John')");

            return 'success';
        });

        expect($result)->toBe('success');

        $user = $this->connection->selectOne('SELECT * FROM test_users WHERE name = ?', ['John']);
        expect($user)->not->toBeFalse();
    });

    test('rolls back transaction on exception', function () {
        $this->connection->statement('CREATE TABLE test_users (id INTEGER PRIMARY KEY, name TEXT)');

        try {
            $this->connection->transaction(function ($conn) {
                $conn->statement("INSERT INTO test_users (name) VALUES ('John')");
                throw new Exception('Test exception');
            });
        } catch (Exception $e) {
            // Expected exception
        }

        $results = $this->connection->select('SELECT * FROM test_users');
        expect($results)->toHaveCount(0);
    });

    test('executes after commit callback', function () {
        $this->connection->statement('CREATE TABLE test_users (id INTEGER PRIMARY KEY, name TEXT)');

        $called = false;

        $this->connection->beginTransaction();
        $this->connection->afterCommit(function () use (&$called) {
            $called = true;
        });
        $this->connection->statement("INSERT INTO test_users (name) VALUES ('John')");
        $this->connection->commit();

        expect($called)->toBeTrue();
    });

    test('executes after rollback callback', function () {
        $this->connection->statement('CREATE TABLE test_users (id INTEGER PRIMARY KEY, name TEXT)');

        $called = false;

        $this->connection->beginTransaction();
        $this->connection->afterRollback(function () use (&$called) {
            $called = true;
        });
        $this->connection->statement("INSERT INTO test_users (name) VALUES ('John')");
        $this->connection->rollBack();

        expect($called)->toBeTrue();
    });
});

describe('Connection - Prepared Statement Caching', function () {
    test('caches prepared statements', function () {
        $this->connection->statement('CREATE TABLE test_users (id INTEGER PRIMARY KEY, name TEXT)');

        // Execute same query multiple times
        $this->connection->execute('SELECT * FROM test_users WHERE name = ?', ['John']);
        $this->connection->execute('SELECT * FROM test_users WHERE name = ?', ['Jane']);

        $stats = $this->connection->getCacheStats();

        expect($stats)->toHaveKey('hits');
        expect($stats)->toHaveKey('misses');
        expect($stats['hits'])->toBeGreaterThanOrEqual(1);
    });

    test('respects max cache size', function () {
        $this->connection->setMaxCacheSize(2);
        $this->connection->statement('CREATE TABLE test_users (id INTEGER PRIMARY KEY, name TEXT)');

        // Execute different queries
        $this->connection->execute('SELECT * FROM test_users WHERE id = ?', [1]);
        $this->connection->execute('SELECT * FROM test_users WHERE id = ?', [2]);
        $this->connection->execute('SELECT * FROM test_users WHERE id = ?', [3]);

        $stats = $this->connection->getCacheStats();

        expect($stats['size'])->toBeLessThanOrEqual(2);
    });

    test('clears statement cache', function () {
        $this->connection->statement('CREATE TABLE test_users (id INTEGER PRIMARY KEY, name TEXT)');
        $this->connection->execute('SELECT * FROM test_users WHERE name = ?', ['John']);

        $this->connection->clearStatementCache();

        $stats = $this->connection->getCacheStats();

        expect($stats['size'])->toBe(0);
        expect($stats['hits'])->toBe(0);
        expect($stats['misses'])->toBe(0);
    });
});

describe('Connection - Database Introspection', function () {
    test('gets list of tables', function () {
        $this->connection->statement('CREATE TABLE test_users (id INTEGER PRIMARY KEY)');
        $this->connection->statement('CREATE TABLE test_posts (id INTEGER PRIMARY KEY)');

        $tables = $this->connection->getTables();

        expect($tables)->toBeArray();
        expect($tables)->toContain('test_users');
        expect($tables)->toContain('test_posts');
    });

    test('checks if table exists', function () {
        $this->connection->statement('CREATE TABLE test_users (id INTEGER PRIMARY KEY)');

        expect($this->connection->tableExists('test_users'))->toBeTrue();
        expect($this->connection->tableExists('nonexistent'))->toBeFalse();
    });

    test('checks if column exists', function () {
        $this->connection->statement('CREATE TABLE test_users (id INTEGER PRIMARY KEY, name TEXT)');

        expect($this->connection->columnExists('test_users', 'name'))->toBeTrue();
        expect($this->connection->columnExists('test_users', 'nonexistent'))->toBeFalse();
    });

    test('truncates table', function () {
        $this->connection->statement('CREATE TABLE test_users (id INTEGER PRIMARY KEY, name TEXT)');
        $this->connection->statement("INSERT INTO test_users (name) VALUES ('John')");

        $this->connection->truncateTable('test_users');

        $results = $this->connection->select('SELECT * FROM test_users');
        expect($results)->toHaveCount(0);
    });
});

describe('Connection - Query Logging', function () {
    test('logs executed queries', function () {
        Connection::clearQueryLog();

        $this->connection->statement('CREATE TABLE test_users (id INTEGER PRIMARY KEY)');
        $this->connection->execute('SELECT * FROM test_users');

        $log = Connection::getQueryLog();

        expect($log)->toBeArray();
        expect(count($log))->toBeGreaterThan(0);
    });

    test('query log includes execution time', function () {
        Connection::clearQueryLog();

        $this->connection->statement('CREATE TABLE test_users (id INTEGER PRIMARY KEY)');

        $log = Connection::getQueryLog();

        expect($log[0])->toHaveKey('time_ms');
        expect($log[0])->toHaveKey('sql');
        expect($log[0])->toHaveKey('bindings');
    });

    test('clears query log', function () {
        $this->connection->execute('SELECT 1');

        Connection::clearQueryLog();

        $log = Connection::getQueryLog();
        expect($log)->toHaveCount(0);
    });
});

describe('Connection - Table Builder', function () {
    test('creates query builder from table', function () {
        $this->connection->statement('CREATE TABLE test_users (id INTEGER PRIMARY KEY, name TEXT)');

        $builder = $this->connection->table('test_users');

        expect($builder)->toBeInstanceOf(Database\Query\Builder::class);
    });

    test('executes query through table builder', function () {
        $this->connection->statement('CREATE TABLE test_users (id INTEGER PRIMARY KEY, name TEXT)');
        $this->connection->statement("INSERT INTO test_users (name) VALUES ('John')");

        $results = $this->connection->table('test_users')->where('name', 'John')->get();

        expect($results)->toHaveCount(1);
    });
});
