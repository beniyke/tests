<?php

declare(strict_types=1);

namespace Tests\System\Support\Seeders;

use Database\Migration\BaseSeeder;

class DatabaseSeeder extends BaseSeeder
{
    public function run(): void
    {
        $this->call([
            ProductSeeder::class,
            ElectronicsSeeder::class,
            AccessoriesSeeder::class,
        ]);
    }
}
