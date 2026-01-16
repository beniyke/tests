<?php

declare(strict_types=1);

namespace Tests\System\Support\Helpers;

use App\Models\User;
use App\Services\Auth\Interfaces\AuthServiceInterface;
use Database\BaseModel;
use Database\Connection;
use Database\ConnectionInterface;
use Database\Schema\Schema;
use Exception;
use Helpers\File\Paths;
use RuntimeException;
use Testing\Mocks\AuthMock;

class DatabaseTestHelper
{
    protected static ?ConnectionInterface $connection = null;

    public static function setupInMemoryDatabase(): ConnectionInterface
    {
        if (self::$connection === null) {
            try {
                $connection = Connection::configure('sqlite::memory:');
                $connection->connect();

                self::$connection = $connection;
            } catch (Exception $e) {
                throw new RuntimeException("Failed to connect to in-memory database: " . $e->getMessage());
            }
        }

        return self::$connection;
    }

    public static function resetDefaultConnection(): void
    {
        if (self::$connection) {
            self::$connection->disconnect();
        }
        self::$connection = null;
    }

    public static function dropAllTables(): void
    {
        if (self::$connection) {
            $tables = self::$connection->getTables();
            foreach ($tables as $table) {
                Schema::dropIfExists($table);
            }
        }
    }

    public static function cleanupTables(array $tables): void
    {
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }

    public static function runPackageMigrations(string $packageName, ?string $basePath = null): void
    {
        $migrationPath = $basePath ?? Paths::basePath("packages/{$packageName}/Database/Migrations");

        if (!is_dir($migrationPath)) {
            return;
        }

        $files = glob($migrationPath . '/*.php');
        if ($files === false) {
            return;
        }

        sort($files);

        foreach ($files as $file) {
            require_once $file;
            $filename = basename($file, '.php');

            $className = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $filename);
            $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $className)));

            if (class_exists($className)) {
                $migration = new $className();
                if (method_exists($migration, 'up')) {
                    $migration->up();
                }
            }
        }
    }

    public static function runAppMigrations(?string $basePath = null): void
    {
        $migrationPath = $basePath ?? Paths::basePath('App/storage/database/migrations');

        if (!is_dir($migrationPath)) {
            return;
        }

        $files = glob($migrationPath . '/*.php');
        if ($files === false) {
            return;
        }

        sort($files);

        foreach ($files as $file) {
            require_once $file;
            $filename = basename($file, '.php');

            $className = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $filename);
            $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $className)));

            if (class_exists($className)) {
                $migration = new $className();
                if (method_exists($migration, 'up')) {
                    $migration->up();
                }
            }
        }
    }

    public static function setupTestEnvironment(array $packages = [], bool $includeAppMigrations = false): ConnectionInterface
    {
        // Bind AuthMock for packages that require it
        $container = container();
        if (!$container->has(AuthServiceInterface::class)) {
            $container->singleton(AuthServiceInterface::class, AuthMock::class);
        }

        $connection = self::setupInMemoryDatabase();
        Schema::setConnection($connection);
        BaseModel::setConnection($connection);

        if ($includeAppMigrations) {
            self::runAppMigrations();
        }

        foreach ($packages as $package) {
            self::runPackageMigrations($package);
        }

        return $connection;
    }

    public static function createModelSchema(): void
    {
        self::runMigrationsFrom(Paths::basePath('System/Testing/Fixtures/Migrations'));
    }

    public static function createRelationSchema(): void
    {
        self::runMigrationsFrom(Paths::basePath('System/Testing/Fixtures/Migrations'));
    }

    public static function runMigrationsFrom(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $files = glob($path . '/*.php');
        if ($files === false) {
            return;
        }

        sort($files);

        foreach ($files as $file) {
            require_once $file;
            $filename = basename($file, '.php');

            $className = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $filename);
            $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $className)));

            // Try namespaced, Migration suffix, and global
            $namespaced = 'Testing\\Fixtures\\Migrations\\' . $className;
            $namespacedMigration = $namespaced . 'Migration';

            if (class_exists($namespacedMigration)) {
                $class = $namespacedMigration;
            } elseif (class_exists($namespaced)) {
                $class = $namespaced;
            } else {
                $class = $className;
            }

            if (class_exists($class)) {
                $migration = new $class();
                if (method_exists($migration, 'up')) {
                    $migration->up();
                }
            }
        }
    }

    public static function createMockUser(int $id = 1, array $attributes = []): User
    {
        $defaults = [
            'name' => 'Test User ' . $id,
            'email' => 'user' . $id . '_' . rand(1, 999) . '@example.com',
            'password' => 'password',
            'gender' => 'male',
            'refid' => 'USR' . $id . '_' . rand(1, 999),
        ];

        return User::create(array_merge($defaults, $attributes));
    }
}
