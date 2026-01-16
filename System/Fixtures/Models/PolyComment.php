<?php

declare(strict_types=1);

namespace Tests\System\Fixtures\Models;

use Database\BaseModel;

class PolyComment extends BaseModel
{
    protected string $table = 'test_rel_poly_comments';

    protected array $fillable = ['body', 'commentable_id', 'commentable_type'];

    public function commentable()
    {
        return $this->morphTo('commentable');
    }
}
