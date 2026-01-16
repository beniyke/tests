<?php

declare(strict_types=1);

namespace Tests\System\Fixtures\Models;

use Database\BaseModel;

class TestSoftDeleteUser extends BaseModel
{
    protected string $table = 'test_rel_soft_delete_users';

    protected array $fillable = ['name', 'email', 'deleted_at'];

    protected bool $softDeletes = true;
}
