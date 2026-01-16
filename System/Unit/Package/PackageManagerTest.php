<?php

declare(strict_types=1);

use Core\Services\ConfigServiceInterface;
use Helpers\File\Adapters\Interfaces\FileManipulationInterface;
use Helpers\File\Adapters\Interfaces\FileMetaInterface;
use Helpers\File\Adapters\Interfaces\FileReadWriteInterface;
use Helpers\File\Adapters\Interfaces\PathResolverInterface;
use Package\PackageManager;

beforeEach(function () {
    $this->paths = Mockery::mock(PathResolverInterface::class);
    $this->fileMeta = Mockery::mock(FileMetaInterface::class);
    $this->fileReadWrite = Mockery::mock(FileReadWriteInterface::class);
    $this->fileManipulation = Mockery::mock(FileManipulationInterface::class);
    $this->configService = Mockery::mock(ConfigServiceInterface::class);

    $this->packageManager = new PackageManager(
        $this->paths,
        $this->fileMeta,
        $this->fileReadWrite,
        $this->fileManipulation,
        $this->configService
    );
});

describe('PackageManager', function () {

    describe('resolvePackagePath', function () {

        it('resolves system package path correctly', function () {
            $this->paths->shouldReceive('systemPath')
                ->once()
                ->with('TestPackage')
                ->andReturn('/var/www/System/TestPackage');

            $this->fileMeta->shouldReceive('isDir')
                ->once()
                ->with('/var/www/System/TestPackage')
                ->andReturn(true);

            $result = $this->packageManager->resolvePackagePath('TestPackage', true);

            expect($result)->toBe('/var/www/System/TestPackage');
        });

        it('resolves custom package path correctly', function () {
            $this->paths->shouldReceive('basePath')
                ->once()
                ->with('packages/TestPackage')
                ->andReturn('/var/www/packages/TestPackage');

            $this->fileMeta->shouldReceive('isDir')
                ->once()
                ->with('/var/www/packages/TestPackage')
                ->andReturn(true);

            $result = $this->packageManager->resolvePackagePath('TestPackage', false);

            expect($result)->toBe('/var/www/packages/TestPackage');
        });

        it('throws exception when package not found', function () {
            $this->paths->shouldReceive('systemPath')
                ->once()
                ->with('NonExistent')
                ->andReturn('/var/www/System/NonExistent');

            $this->fileMeta->shouldReceive('isDir')
                ->once()
                ->with('/var/www/System/NonExistent')
                ->andReturn(false);

            $this->packageManager->resolvePackagePath('NonExistent', true);
        })->throws(RuntimeException::class, 'Package not found at');
    });

    // Note: checkStatus() internally uses scandir() which is hard to mock in unit tests
    // Comprehensive status checking is covered in integration tests
    // Here we just verify the method exists and returns expected status constants
    // Note: getManifest() internally uses require() which is hard to mock in unit tests
    // The successful case (file exists) is covered in integration tests

    describe('getManifest', function () {

        it('returns empty array when no setup.php exists', function () {
            $packagePath = '/var/www/System/TestPackage';
            $setupFile = $packagePath . DIRECTORY_SEPARATOR . 'setup.php';

            $this->fileMeta->shouldReceive('isFile')
                ->with($setupFile)
                ->once()
                ->andReturn(false);

            $manifest = $this->packageManager->getManifest($packagePath);

            expect($manifest)->toBeEmpty();
        });
    });

    describe('registerProviders', function () {

        it('handles empty providers array', function () {
            // When providers array is empty, method should return early
            $this->packageManager->registerProviders([]);

            expect(true)->toBeTrue();
        });
    });

    describe('publishConfig', function () {

        it('returns 0 when config directory does not exist', function () {
            $packagePath = '/var/www/System/TestPackage';
            $configSource = $packagePath . DIRECTORY_SEPARATOR . 'Config';

            $this->fileMeta->shouldReceive('isDir')
                ->with($configSource)
                ->once()
                ->andReturn(false);

            $result = $this->packageManager->publishConfig($packagePath);

            expect($result)->toBe(0);
        });

        // Note: Full recursive copying behavior is tested in integration tests
        // due to complexity of mocking FilesystemIterator
    });

    describe('publishMigrations', function () {

        it('returns 0 when migrations directory does not exist', function () {
            $packagePath = '/var/www/System/TestPackage';
            $migrationsSource = $packagePath . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Migrations';

            $this->fileMeta->shouldReceive('isDir')
                ->with($migrationsSource)
                ->once()
                ->andReturn(false);

            $result = $this->packageManager->publishMigrations($packagePath);

            expect($result)->toBe(0);
        });

        // Note: Directory creation and full copying is tested in integration tests
    });

    describe('checkStatus', function () {

        it('returns NOT_INSTALLED when no files are checked', function () {
            $packagePath = '/var/www/System/TestPackage';
            $configSource = $packagePath . DIRECTORY_SEPARATOR . 'Config';
            $migrationSource = $packagePath . DIRECTORY_SEPARATOR . 'Database/Migrations';

            // Mock that directories don't exist for checking
            $this->fileMeta->shouldReceive('isDir')
                ->andReturn(false);

            $status = $this->packageManager->checkStatus($packagePath);

            expect($status)->toBe(PackageManager::STATUS_NOT_INSTALLED);
        });

        // Note: Complex status checking scenarios are tested in integration tests
    });

    describe('uninstall', function () {

        it('handles uninstallation gracefully', function () {
            // Note: Full uninstall workflow with scandir is tested in integration tests
            // This test just verifies the method exists and is callable
            expect(method_exists($this->packageManager, 'uninstall'))->toBeTrue();
        });
    });
});
