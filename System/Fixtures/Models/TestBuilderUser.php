<?php

declare(strict_types=1);

namespace Tests\System\Fixtures\Models;

use Database\BaseModel;

class TestBuilderUser extends BaseModel
{
    protected string $table = 'test_rel_builder_users';

    protected array $fillable = ['name', 'email', 'age', 'votes', 'created_at', 'metadata'];

    protected array $casts = [
        'metadata' => 'json',
        'created_at' => 'datetime',
    ];
}
