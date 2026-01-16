<?php

declare(strict_types=1);

namespace Tests\System\Fixtures\Models;

use Database\BaseModel;

class QueueFailedJob extends BaseModel
{
    protected string $table = 'test_rel_queue_failed_jobs';

    protected array $fillable = ['job_connection', 'queue', 'payload', 'exception', 'failed_at'];

    public bool $timestamps = false;
}
