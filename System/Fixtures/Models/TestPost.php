<?php

declare(strict_types=1);

namespace Tests\System\Fixtures\Models;

use Database\BaseModel;

class TestPost extends BaseModel
{
    protected string $table = 'test_rel_feature_posts';

    protected array $fillable = ['user_id', 'title', 'content', 'published'];

    public function user()
    {
        return $this->belongsTo(TestUser::class, 'user_id');
    }

    public function comments()
    {
        return $this->hasMany(TestComment::class, 'post_id');
    }
}
