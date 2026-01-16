<?php

declare(strict_types=1);

namespace Tests\System\Fixtures\Models;

use Database\BaseModel;

class TestUpdateUser extends BaseModel
{
    protected string $table = 'test_rel_update_users';

    protected array $fillable = ['name', 'email', 'status'];
}
