<?php

declare(strict_types=1);

namespace Tests\System\Fixtures\Models;

use Database\BaseModel;

class TestUser extends BaseModel
{
    protected string $table = 'test_rel_feature_users';

    protected array $fillable = ['name', 'email', 'status'];

    public function posts()
    {
        return $this->hasMany(TestPost::class, 'user_id');
    }
}
