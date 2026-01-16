<?php

declare(strict_types=1);

namespace Tests\Packages\Workflow\Helpers;

use Throwable;
use Workflow\Contracts\Activity;
use Workflow\Contracts\ActivityOptions;

class WorkflowFeatureTestActivity implements Activity
{
    public array $payload;

    public $options;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
        $this->options = ActivityOptions::make();
    }

    public function handle(array $payload): array
    {
        return ['status' => 'done'];
    }

    public function onFailure(string $instanceId, Throwable $e): void
    {
    }

    public function compensate(string $instanceId, array $originalPayload): void
    {
    }
}
