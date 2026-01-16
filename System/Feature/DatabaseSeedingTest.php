<?php

declare(strict_types=1);

use Database\DB;
use Database\Migration\SeedManager;
use Helpers\File\Paths;
use Testing\Concerns\RefreshDatabase;
use Tests\System\Fixtures\Models\TestProduct;

uses(RefreshDatabase::class)->group('system', 'database');

describe('Database Seeding - SeedManager', function () {
    test('runs a basic seeder and inserts data', function () {
        $seederClass = 'Tests\System\Support\Seeders\ProductSeeder';

        // Run the seeder
        $manager = new SeedManager(DB::connection(), Paths::testPath('System/Support/Seeders'));
        $result = $manager->run($seederClass);

        // Verify results
        expect($result['success'])->toBeTrue();
        expect($result['class'])->toBe($seederClass);
        expect($result['time'])->toBeGreaterThanOrEqual(0);

        // Verify data was inserted
        $products = TestProduct::all();
        expect($products)->toHaveCount(2);
        expect($products[0]->name)->toBe('Laptop');
        expect($products[1]->name)->toBe('Mouse');
    });

    test('calls other seeders using call method', function () {
        $namespace = 'Tests\System\Support\Seeders';

        // Run the main seeder
        $manager = new SeedManager(DB::connection(), Paths::testPath('System/Support/Seeders'));
        $result = $manager->run($namespace . '\DatabaseSeeder');

        // Verify results
        expect($result['success'])->toBeTrue();

        // Verify all seeders ran (Laptop, Mouse, Keyboard, USB Cable)
        $products = TestProduct::all();
        expect($products)->toHaveCount(4);
        expect($products->pluck('name'))->toContain('Keyboard', 'USB Cable', 'Laptop', 'Mouse');
    });

    test('throws exception when seeder file not found', function () {
        $manager = new SeedManager(DB::connection(), Paths::testPath('System/Support/Seeders'));

        expect(fn () => $manager->run('NonExistentSeeder'))
            ->toThrow(RuntimeException::class, 'Seeder file');
    });

    test('throws exception when seeder class not defined', function () {
        $manager = new SeedManager(DB::connection(), Paths::testPath('System/Support/Seeders'));

        expect(fn () => $manager->run('EmptySeeder'))
            ->toThrow(RuntimeException::class, 'not defined');
    });

    test('throws exception when class does not extend BaseSeeder', function () {
        $manager = new SeedManager(DB::connection(), Paths::testPath('System/Support/Seeders'));

        expect(fn () => $manager->run('InvalidSeeder'))
            ->toThrow(RuntimeException::class, 'must extend');
    });
});
