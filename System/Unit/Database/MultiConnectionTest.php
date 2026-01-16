<?php

declare(strict_types=1);

use Core\Ioc\Container;
use Core\Services\ConfigServiceInterface;
use Database\Connection;
use Database\DB;

describe('Multi-Connection Database Operations', function () {
    test('can register and use multiple connections', function () {
        // Setup two separate in-memory databases
        $conn1 = Connection::configure('sqlite::memory:')->name('conn_a')->connect();
        $conn2 = Connection::configure('sqlite::memory:')->name('conn_b')->connect();

        // Register them
        DB::addConnection('a', $conn1);
        DB::addConnection('b', $conn2);

        // Create table in conn_a
        $conn1->statement('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');
        // Create table in conn_b
        $conn2->statement('CREATE TABLE posts (id INTEGER PRIMARY KEY, title TEXT)');

        // Verify we can use them via DB facade
        DB::connection('a')->table('users')->insert(['name' => 'Alice']);
        DB::connection('b')->table('posts')->insert(['title' => 'Hello World']);

        // Assert data is in correct connection
        $user = DB::connection('a')->table('users')->first();
        $post = DB::connection('b')->table('posts')->first();

        expect($user->name)->toBe('Alice');
        expect($post->title)->toBe('Hello World');

        // Assert cross-connection isolation
        try {
            DB::connection('a')->table('posts')->get();
            $this->fail('Posts table should not exist in connection A');
        } catch (Exception $e) {
            expect($e->getMessage())->toContain('no such table: posts');
        }
    });

    test('default connection works as expected', function () {
        $defaultConn = Connection::configure('sqlite::memory:')->name('default')->connect();
        $defaultConn->statement('CREATE TABLE settings (key TEXT, value TEXT)');

        DB::setDefaultConnection($defaultConn);

        DB::table('settings')->insert(['key' => 'site_name', 'value' => 'Anchor']);

        $setting = DB::connection()->table('settings')->first();
        expect($setting->value)->toBe('Anchor');

        // Verify DB::table proxies to default
        $settingProxy = DB::table('settings')->first();
        expect($settingProxy->value)->toBe('Anchor');
    });

    test('connections are lazily loaded from config', function () {
        $mockConfig = [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'persistent' => false,
        ];

        $container = Container::getInstance();
        $originalConfig = $container->get(ConfigServiceInterface::class);

        $mock = Mockery::mock(ConfigServiceInterface::class);
        $mock->shouldReceive('get')->with('database.connections.lazy')->andReturn($mockConfig);

        $container->instance(ConfigServiceInterface::class, $mock);

        try {
            $conn = DB::connection('lazy');
            expect($conn)->toBeInstanceOf(Connection::class);
            expect($conn->getDriver())->toBe('sqlite');
        } finally {
            // Restore original config service
            $container->instance(ConfigServiceInterface::class, $originalConfig);
        }
    });

    test('testcase assertions work with named connections', function () {
        $conn = Connection::configure('sqlite::memory:')->name('named_assertion')->connect();
        $conn->statement('CREATE TABLE logs (id INTEGER PRIMARY KEY, message TEXT)');

        DB::addConnection('assertion_db', $conn);

        DB::connection('assertion_db')->table('logs')->insert(['message' => 'test message']);

        $this->assertDatabaseHas('logs', ['message' => 'test message'], 'assertion_db');
        $this->assertDatabaseMissing('logs', ['message' => 'non-existent'], 'assertion_db');
    });

    test('models can use different connections', function () {
        $conn = Connection::configure('sqlite::memory:')->name('model_db')->connect();
        $conn->statement('CREATE TABLE audit_logs (id INTEGER PRIMARY KEY, message TEXT)');

        DB::addConnection('audit', $conn);

        // Inline model class for testing
        $model = new class () extends Database\BaseModel {
            protected string $table = 'audit_logs';

            protected string $connection = 'audit';

            public bool $timestamps = false;
        };

        $model->create(['message' => 'audit entry']);

        $this->assertDatabaseHas('audit_logs', ['message' => 'audit entry'], 'audit');
    });
});
