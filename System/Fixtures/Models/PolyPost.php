<?php

declare(strict_types=1);

namespace Tests\System\Fixtures\Models;

use Database\BaseModel;

class PolyPost extends BaseModel
{
    protected string $table = 'test_rel_poly_posts';

    protected array $fillable = ['title'];

    public function image()
    {
        return $this->morphOne(PolyImage::class, 'imageable');
    }

    public function comments()
    {
        return $this->morphMany(PolyComment::class, 'commentable');
    }
}
