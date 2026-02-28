<?php

declare(strict_types=1);

namespace Tests\System\Fixtures\Models;

use Database\BaseModel;

class TestProduct extends BaseModel
{
    protected string $table = 'test_product';

    protected array $fillable = ['name', 'price', 'stock'];

    public bool $timestamps = false;
}
