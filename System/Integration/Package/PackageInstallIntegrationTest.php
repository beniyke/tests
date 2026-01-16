<?php

declare(strict_types=1);

use Helpers\File\FileSystem;
use Helpers\File\Paths;
use Package\PackageManager;

beforeEach(function () {
    $this->testPackagePath = __DIR__ . '/Fixtures/TestPackage';
    $this->appConfigPath = __DIR__ . '/Fixtures/App/Config';
    $this->appStoragePath = __DIR__ . '/Fixtures/App/storage/database/migrations';

    // Create fixture directories
    FileSystem::mkdir($this->testPackagePath . '/Config', 0755, true);
    FileSystem::mkdir($this->testPackagePath . '/Config/Nested', 0755, true); // Test recursive
    FileSystem::mkdir($this->testPackagePath . '/Database/Migrations', 0755, true);
    FileSystem::mkdir($this->appConfigPath, 0755, true);
    FileSystem::mkdir($this->appStoragePath, 0755, true);

    // Create test config files
    FileSystem::put(
        $this->testPackagePath . '/Config/test.php',
        "<?php\nreturn ['test' => true];"
    );

    // Create nested config file to test recursion
    FileSystem::put(
        $this->testPackagePath . '/Config/Nested/nested.php',
        "<?php\nreturn ['nested' => true];"
    );

    // Create test migration with valid structure (extending BaseMigration)
    FileSystem::put(
        $this->testPackagePath . '/Database/Migrations/2025_01_01_000000_test_migration.php',
        "<?php\n\nuse Database\Migration\BaseMigration;\n\nclass TestMigration extends BaseMigration {\n    public function up(): void {}\n    public function down(): void {}\n}"
    );

    FileSystem::put(
        $this->testPackagePath . '/setup.php',
        "<?php\nreturn ['providers' => ['Test\Provider'], 'middleware' => []];"
    );

    $this->packageManager = resolve(PackageManager::class);
});

afterEach(function () {
    // Clean up fixture directories
    FileSystem::delete($this->testPackagePath);
    FileSystem::delete(dirname($this->appConfigPath));

    // Clean up published files from real App directories
    FileSystem::delete(Paths::basePath('App/Config/test.php'));
    FileSystem::delete(Paths::basePath('App/Config/Nested'));
    FileSystem::delete(Paths::basePath('App/storage/database/migrations/2025_01_01_000000_test_migration.php'));
    FileSystem::delete(Paths::basePath('App/storage/database/migrations/Archive'));
});

describe('Package Installation Integration', function () {
    // ... tests ...
});

describe('Recursive Directory Copying (E2E)', function () {
    // ... tests ...
    it('handles empty source directories gracefully', function () {
        // Arrange: Create empty config directory
        $emptyPackagePath = __DIR__ . '/Fixtures/EmptyPackage';
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
        $largePackagePath = __DIR__ . '/Fixtures/LargePackage';
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
    // ...
});

describe('Package Uninstall Integration', function () {
    // ...
});
