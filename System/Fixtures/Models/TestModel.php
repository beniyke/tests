<?php

declare(strict_types=1);

namespace Tests\System\Fixtures\Models;

use Database\BaseModel;

class TestModel extends BaseModel
{
    protected string $table = 'test_rel_models';

    protected array $fillable = ['name', 'email', 'status'];

    protected array $hidden = ['password'];

    protected array $casts = ['is_active' => 'bool', 'metadata' => 'json'];
}
