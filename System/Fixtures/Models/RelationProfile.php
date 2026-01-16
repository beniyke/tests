<?php

declare(strict_types=1);

namespace Tests\System\Fixtures\Models;

use Database\BaseModel;

class RelationProfile extends BaseModel
{
    protected string $table = 'test_rel_profiles';

    protected array $fillable = ['user_id', 'bio', 'avatar'];

    public function user()
    {
        return $this->belongsTo(RelationUser::class, 'user_id');
    }
}
