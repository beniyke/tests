<?php

declare(strict_types=1);

namespace Tests\System\Fixtures\Models;

use Database\BaseModel;

class TestComment extends BaseModel
{
    protected string $table = 'test_comment';

    protected array $fillable = ['post_id', 'content'];

    public function post()
    {
        return $this->belongsTo(TestPost::class, 'post_id');
    }
}
