<?php

declare(strict_types=1);

namespace Tests\Packages\Workflow\Helpers;

use Throwable;
use Workflow\Contracts\Activity;

class TestActivity implements Activity
{
    public function handle(array $payload): array
    {
        return ['status' => 'success', 'data' => $payload];
    }

    public function onFailure(string $instanceId, Throwable $e): void
    {
    }

    public function compensate(string $instanceId, array $originalPayload): void
    {
    }
}
