<?php

declare(strict_types=1);

namespace Tests\System\Fixtures\Models;

use Database\BaseModel;

class RelationPost extends BaseModel
{
    protected string $table = 'test_rel_posts';

    protected array $fillable = ['user_id', 'title', 'content'];

    public function user()
    {
        return $this->belongsTo(RelationUser::class, 'user_id');
    }

    public function comments()
    {
        return $this->hasMany(RelationComment::class, 'post_id');
    }

    public function images()
    {
        return $this->morphMany(RelationImage::class, 'imageable');
    }
}
