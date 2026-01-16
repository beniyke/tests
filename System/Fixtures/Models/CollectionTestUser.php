<?php

declare(strict_types=1);

namespace Tests\System\Fixtures\Models;

use Database\BaseModel;

class CollectionTestUser extends BaseModel
{
    protected string $table = 'test_rel_collection_users';

    protected array $fillable = ['name', 'email', 'age', 'status'];
}
