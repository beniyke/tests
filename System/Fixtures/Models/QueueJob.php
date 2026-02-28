<?php

declare(strict_types=1);

namespace Tests\System\Fixtures\Models;

use Database\BaseModel;

class QueueJob extends BaseModel
{
    protected string $table = 'test_rel_queue_job';

    protected array $fillable = ['queue', 'payload', 'attempts', 'reserved_at', 'available_at', 'created_at'];

    public bool $timestamps = false;
}
