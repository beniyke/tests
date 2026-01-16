<?php

declare(strict_types=1);

namespace Tests\System\Support\Seeders;

use Database\Migration\BaseSeeder;

class ProductSeeder extends BaseSeeder
{
    public function run(): void
    {
        $this->connection->table('test_products')->insert([
            'name' => 'Laptop',
            'price' => 999.99,
            'stock' => 10,
        ]);

        $this->connection->table('test_products')->insert([
            'name' => 'Mouse',
            'price' => 29.99,
            'stock' => 50,
        ]);
    }
}
