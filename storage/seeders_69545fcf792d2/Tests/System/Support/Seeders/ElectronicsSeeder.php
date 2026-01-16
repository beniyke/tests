<?php

declare(strict_types=1);

namespace Tests\System\Support\Seeders;

use Database\Migration\BaseSeeder;

class ElectronicsSeeder extends BaseSeeder
{
    public function run(): void
    {
        $this->connection->table('test_products')->insert([
            'name' => 'Keyboard',
            'price' => 79.99,
            'stock' => 25,
        ]);
    }
}
