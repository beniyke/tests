<?php

declare(strict_types=1);

namespace Tests\System\Fixtures\Models;

use Database\BaseModel;

class RelationRole extends BaseModel
{
    protected string $table = 'test_rel_roles';

    protected array $fillable = ['name'];

    public function users()
    {
        return $this->belongsToMany(RelationUser::class, 'test_rel_user_roles', 'role_id', 'user_id');
    }
}
