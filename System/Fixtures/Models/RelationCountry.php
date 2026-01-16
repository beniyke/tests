<?php

declare(strict_types=1);

namespace Tests\System\Fixtures\Models;

use Database\BaseModel;

class RelationCountry extends BaseModel
{
    protected string $table = 'test_rel_countries';

    protected array $fillable = ['name'];

    public function users()
    {
        return $this->hasMany(RelationUser::class, 'country_id');
    }

    public function posts()
    {
        return $this->hasManyThrough(RelationPost::class, RelationUser::class, 'country_id', 'user_id');
    }
}
