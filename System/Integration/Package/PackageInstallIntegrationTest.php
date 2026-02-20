<?php

declare(strict_types=1);

use Helpers\File\Adapters\Interfaces\PathResolverInterface;
use Helpers\File\FileSystem;
use Helpers\File\Paths;
use Package\PackageManager;

beforeEach(function () {
    $this->testPackagePath = Paths::testPath('System/Integration/Package/Fixtures/TestPackage');
    $this->appConfigPath = Paths::testPath('System/Integration/Package/Fixtures/App/Config');
    $this->appStoragePath = Paths::testPath('System/Integration/Package/Fixtures/App/storage/database/migrations');

    // Create fixture directories
    FileSystem::mkdir($this->testPackagePath . '/Config', 0755, true);
    FileSystem::mkdir($this->testPackagePath . '/Config/Nested', 0755, true);
    FileSystem::mkdir($this->testPackagePath . '/Database/Migrations', 0755, true);
    FileSystem::mkdir($this->appConfigPath, 0755, true);
    FileSystem::mkdir($this->appStoragePath, 0755, true);

    // Create test config files for registration tests
    FileSystem::put($this->appConfigPath . '/providers.php', "<?php\nreturn [\n];");
    FileSystem::put($this->appConfigPath . '/middleware.php', "<?php\nreturn [\n    'web' => [],\n    'api' => [],\n];");

    FileSystem::put(
        $this->testPackagePath . '/Config/test.php',
        "<?php\nreturn ['test' => true];"
    );

    FileSystem::put(
        $this->testPackagePath . '/Config/Nested/nested.php',
        "<?php\nreturn ['nested' => true];"
    );

    FileSystem::put(
        $this->testPackagePath . '/Database/Migrations/2025_01_01_000000_test_migration.php',
        "<?php\n\nuse Database\Migration\BaseMigration;\n\nclass TestMigration extends BaseMigration {\n    public function up(): void {}\n    public function down(): void {}\n}"
    );

    FileSystem::put(
        $this->testPackagePath . '/setup.php',
        "<?php\nreturn ['providers' => ['Test\Provider'], 'middleware' => []];"
    );

    // Mock Paths to point to fixtures
    $this->paths = Mockery::mock(PathResolverInterface::class);
    $this->paths->shouldReceive('appPath')->andReturnUsing(function ($sub = null) {
        return $sub ? $this->appConfigPath . '/../' . $sub : dirname($this->appConfigPath);
    });
    $this->paths->shouldReceive('configPath')->andReturnUsing(function ($sub = null) {
        return $sub ? $this->appConfigPath . '/' . $sub : $this->appConfigPath;
    });
    $this->paths->shouldReceive('storagePath')->andReturnUsing(function ($sub = null) {
        return $sub ? $this->appStoragePath . '/../../' . $sub : dirname(dirname($this->appStoragePath));
    });

    // Bind mock to container
    $this->container = container();
    $this->container->instance(PathResolverInterface::class, $this->paths);

    $this->packageManager = $this->container->get(PackageManager::class);
});

afterEach(function () {
    // Clean up fixture directories
    FileSystem::delete($this->testPackagePath);
    FileSystem::delete(Paths::testPath('System/Integration/Package/Fixtures/App'));
    FileSystem::delete(Paths::testPath('System/Integration/Package/Fixtures/EmptyPackage'));
    FileSystem::delete(Paths::testPath('System/Integration/Package/Fixtures/LargePackage'));

    Mockery::close();
});

describe('Package Installation Integration', function () {
    it('installs a package by publishing config and migrations', function () {
        // Act
        $results = $this->packageManager->install($this->testPackagePath);

        // Assert
        expect($results['config_count'])->toBe(2); // test.php and Nested/nested.php
        expect($results['migration_count'])->toBe(1);
        expect(FileSystem::exists($this->appConfigPath . '/test.php'))->toBeTrue();
        expect(FileSystem::exists($this->appConfigPath . '/Nested/nested.php'))->toBeTrue();
        expect(FileSystem::exists($this->appStoragePath . '/2025_01_01_000000_test_migration.php'))->toBeTrue();
    });

    it('registers providers and middleware from manifest', function () {
        // Arrange
        $manifest = [
            'providers' => ['Test\Provider'],
            'middleware' => [
                'web' => ['Test\Middleware']
            ]
        ];

        // Act
        $this->packageManager->install($this->testPackagePath, $manifest);

        // Assert
        $providers = FileSystem::get($this->appConfigPath . '/providers.php');
        $middleware = FileSystem::get($this->appConfigPath . '/middleware.php');

        expect($providers)->toContain('Test\Provider::class');
        expect($middleware)->toContain('Test\Middleware::class');
    });
});

describe('Recursive Directory Copying (E2E)', function () {
    // ... tests ...
    it('handles empty source directories gracefully', function () {
        // Arrange: Create empty config directory
        $emptyPackagePath = Paths::testPath('System/Integration/Package/Fixtures/EmptyPackage');
        FileSystem::mkdir($emptyPackagePath . '/Config', 0755, true);

        // Act: Attempt to publish
        $count = $this->packageManager->publishConfig($emptyPackagePath);

        // Assert: Returns 0 for empty directory
        expect($count)->toBe(0);

        // Cleanup
        FileSystem::delete($emptyPackagePath);
    });
    // ... other tests ...
});

describe('Performance Benchmarking', function () {
    it('copies large directory structure efficiently', function () {
        // Arrange: Create 100 config files in various subdirectories
        $largePackagePath = Paths::testPath('System/Integration/Package/Fixtures/LargePackage');
        FileSystem::mkdir($largePackagePath . '/Config', 0755, true);

        for ($i = 0; $i < 10; $i++) {
            $subdir = $largePackagePath . "/Config/Module{$i}";
            FileSystem::mkdir($subdir, 0755, true);

            for ($j = 0; $j < 10; $j++) {
                FileSystem::put(
                    "{$subdir}/config{$j}.php",
                    "<?php\nreturn ['module' => {$i}, 'config' => {$j}];"
                );
            }
        }

        // Act: Measure copy performance
        $startTime = microtime(true);
        $count = $this->packageManager->publishConfig($largePackagePath);
        $duration = microtime(true) - $startTime;

        // Assert: All 100 files copied
        expect($count)->toBe(100);

        // Performance expectation: Should complete in under 10 seconds for 100 files
        // (Windows file I/O is slower than Unix)
        expect($duration)->toBeLessThan(10.0);

        // Log performance for monitoring
        echo "\nCopied {$count} files in " . number_format($duration, 4) . " seconds";
        echo "\nAverage: " . number_format($duration / $count * 1000, 2) . "ms per file";

        // Cleanup
        FileSystem::delete($largePackagePath);
    })->skip(function () {
        // Skip in CI or fast test runs
        return getenv('SKIP_PERFORMANCE_TESTS') === 'true';
    }, 'Performance test - run with SKIP_PERFORMANCE_TESTS=false');
});

describe('Error Handling E2E', function () {
    it('returns zero counts when package has no config or migrations', function () {
        // Arrange
        $emptyPath = Paths::testPath('System/Integration/Package/Fixtures/MinimalPackage');
        FileSystem::mkdir($emptyPath);

        // Act
        $results = $this->packageManager->install($emptyPath);

        // Assert
        expect($results['config_count'])->toBe(0);
        expect($results['migration_count'])->toBe(0);

        // Cleanup
        FileSystem::delete($emptyPath);
    });

    it('returns 0 when source directory does not exist for explicit publishing', function () {
        expect($this->packageManager->publishConfig('/non/existent'))->toBe(0);
    });
});

describe('Package Uninstall Integration', function () {
    it('removes published files during uninstallation', function () {
        // Arrange: Install first
        $this->packageManager->install($this->testPackagePath);
        expect(FileSystem::exists($this->appConfigPath . '/test.php'))->toBeTrue();

        // Act
        $this->packageManager->uninstall($this->testPackagePath);

        // Assert
        expect(FileSystem::exists($this->appConfigPath . '/test.php'))->toBeFalse();
        expect(FileSystem::exists($this->appConfigPath . '/Nested'))->toBeFalse();
        expect(FileSystem::exists($this->appStoragePath . '/2025_01_01_000000_test_migration.php'))->toBeFalse();
    });
});
