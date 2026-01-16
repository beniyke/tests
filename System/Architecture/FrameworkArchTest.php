<?php

declare(strict_types=1);

/**
 * Architecture Tests
 *
 * These tests enforce architectural rules and coding standards across the codebase.
 * They catch violations like improper dependencies, naming violations, or layer breaches.
 *
 * Run with: php vendor/bin/pest tests/System/Architecture/ArchitectureTest.php
 */

describe('Framework Architecture Rules', function () {

    // =============================================================================
    // Package Namespace Rules
    // =============================================================================

    arch('Package namespace does not use native file functions')
        ->expect('Package')
        ->not->toUse([
            'file_exists',
            'is_dir',
            'is_file',
            'is_readable',
            'is_writable',
            'unlink',
            'mkdir',
            'rmdir',
            'file_get_contents',
            'file_put_contents',
            'glob',
        ]);

    arch('PackageManager only depends on approved interfaces')
        ->expect('Package\PackageManager')
        ->toOnlyUse([
            'Core\Services\ConfigServiceInterface',
            'Helpers\File\Adapters\Interfaces\PathResolverInterface',
            'Helpers\File\Adapters\Interfaces\FileMetaInterface',
            'Helpers\File\Adapters\Interfaces\FileReadWriteInterface',
            'Helpers\File\Adapters\Interfaces\FileManipulationInterface',
            'Database\ConnectionInterface',
            'Database\Migration\Migrator',
            'Database\Helpers\DatabaseOperationConfig',
            'RuntimeException',
            'Throwable',
            // PHP native classes/functions are OK
            'FilesystemIterator',
            'array_filter',
            'array_diff',
            'scandir',
            'rsort',
            'pathinfo',
            'error_log',
            'str_contains',
            'strrpos',
            'substr_replace',
            'preg_match',
            'str_replace',
            'str_ends_with',
            'trim',
            'file',
            'implode',
            'resolve',
        ]);

    // =============================================================================
    // Naming Convention Rules
    // =============================================================================

    arch('Service providers follow naming convention')
        ->expect('*\Providers\*ServiceProvider')
        ->toHaveSuffix('ServiceProvider');

    arch('Console commands follow naming convention')
        ->expect('*\Commands\*Command')
        ->toHaveSuffix('Command');

    arch('Middleware follows naming convention')
        ->expect('*\Middleware\*Middleware')
        ->toHaveSuffix('Middleware');

    arch('Test classes follow naming convention')
        ->expect('Tests')
        ->toHaveSuffix('Test')
        ->ignoring([
            'Tests\TestCase',
            'Tests\PackageTestCase',
            'Tests\UnitTestCase',
            'Tests\DatabaseTransactionTestCase',
            'Tests\System\Helpers',
            'Tests\System\DTOs',
            'Tests\System\Support',
            'Tests\System\Fixtures',
            'Tests\Packages\Bridge\Support',
            'Tests\Packages\Flow\Helpers',
            'Tests\Packages\Workflow\Helpers',
            'Tests\Packages\Slot\Helpers',
            'Tests\Packages\Hub\Support',
            'Tests\System\Integration\Package\Fixtures'
        ]);

    // =============================================================================
    // Strict Types Declaration
    // =============================================================================

    arch('All App files declare strict types')
        ->expect('App')
        ->toUseStrictTypes();

    arch('All System files declare strict types')
        ->expect('System')
        ->toUseStrictTypes();

    // =============================================================================
    // Debug Function Usage
    // =============================================================================

    arch('App code does not use debug functions')
        ->expect('App')
        ->not->toUse(['dd', 'dump', 'var_dump', 'print_r', 'var_export']);

    arch('System code does not use debug functions')
        ->expect('System')
        ->not->toUse(['dd', 'dump', 'var_dump', 'print_r', 'var_export']);

    // =============================================================================
    // Layer Dependency Rules
    // =============================================================================

    arch('System is independent from App')
        ->expect('System')
        ->not->toUse('App');

    arch('Helpers are pure utilities and independent')
        ->expect('Helpers')
        ->not->toUse(['App', 'Package']);

    // =============================================================================
    // Service Provider Rules
    // =============================================================================

    arch('Service providers extend base ServiceProvider')
        ->expect('*\Providers\*ServiceProvider')
        ->toExtend('Core\Services\ServiceProvider')
        ->ignoring([
            'Core\Services\ServiceProvider', // Ignore the base class itself
        ]);

    // =============================================================================
    // Middleware Rules
    // =============================================================================

    arch('Middleware implements MiddlewareInterface')
        ->expect('*\Middleware\*Middleware')
        ->toImplement('Core\Middleware\MiddlewareInterface')
        ->ignoring([
            'Core\Middleware\MiddlewareInterface', // Ignore the interface itself
        ]);
});

describe('Best Practices Enforcement', function () {

    // =============================================================================
    // Constructor Injection Pattern
    // =============================================================================

    arch('Package classes use dependency injection')
        ->expect('Package')
        ->toUseNothing()
        ->ignoring([
            // Allow these patterns in Package namespace
            'Core\Services\ConfigServiceInterface',
            'Helpers\File\Adapters\Interfaces\PathResolverInterface',
            'Helpers\File\Adapters\Interfaces\FileMetaInterface',
            'Helpers\File\Adapters\Interfaces\FileReadWriteInterface',
            'Helpers\File\Adapters\Interfaces\FileManipulationInterface',
            'Database\ConnectionInterface',
            'Database\Migration\Migrator',
            'Database\Helpers\DatabaseOperationConfig',
            'RuntimeException',
            'Throwable',
            'FilesystemIterator',
            'Symfony\Component\Console\Command\Command',
            'Symfony\Component\Console\Input\InputInterface',
            'Symfony\Component\Console\Output\OutputInterface',
            'Symfony\Component\Console\Input\InputArgument',
            'Symfony\Component\Console\Input\InputOption',
            'Symfony\Component\Console\Style\SymfonyStyle',
            'Helpers\File\Paths',
            'Helpers\File\FileSystem',
            'Helpers\Database\Query',
            'Helpers\Http\Client\Curl',
            'Helpers\Http\Client\Response',
            'ZipArchive',
            'resolve', // Global helper function
        ]);

    // =============================================================================
    // Exception Handling
    // =============================================================================

    arch('Custom exceptions extend base Exception')
        ->expect('*\Exceptions\*')
        ->toExtend('Exception');

    // =============================================================================
    // Final Classes (Value Objects, DTOs)
    // =============================================================================

    arch('Value objects are final')
        ->expect('*\ValueObjects\*')
        ->toBeFinal()
        ->ignoring([
            // Add any base value object classes here if needed
        ]);
});

describe('Security Rules', function () {

    // =============================================================================
    // Environment Access
    // =============================================================================

    arch('Only config service accesses environment variables')
        ->expect('System')
        ->not->toUse([
            'getenv',
            'putenv',
        ])
        ->ignoring([
            'Core\Services\Config', // Allowed to access env
            'Core\Services\ConfigService',
        ]);
});
