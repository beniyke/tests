<?php

declare(strict_types=1);

namespace Tests\System\Support\Seeders;

use Database\Migration\BaseSeeder;

class AccessoriesSeeder extends BaseSeeder
{
    public function run(): void
    {
        $this->connection->table('test_products')->insert([
            'name' => 'USB Cable',
            'price' => 9.99,
            'stock' => 100,
        ]);
    }
}
