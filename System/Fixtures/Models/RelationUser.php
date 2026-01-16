<?php

declare(strict_types=1);

namespace Tests\System\Fixtures\Models;

use Database\BaseModel;

class RelationUser extends BaseModel
{
    protected string $table = 'test_rel_users';

    protected array $fillable = ['name', 'email', 'country_id'];

    public function profile()
    {
        return $this->hasOne(RelationProfile::class, 'user_id');
    }

    public function posts()
    {
        return $this->hasMany(RelationPost::class, 'user_id');
    }

    public function roles()
    {
        return $this->belongsToMany(RelationRole::class, 'test_rel_user_roles', 'user_id', 'role_id');
    }

    public function image()
    {
        return $this->morphOne(RelationImage::class, 'imageable');
    }
}
