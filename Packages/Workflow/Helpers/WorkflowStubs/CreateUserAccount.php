<?php

declare(strict_types=1);

namespace Tests\Packages\Workflow\Helpers\WorkflowStubs;

use RuntimeException;
use Throwable;
use Workflow\Contracts\Activity;
use Workflow\Contracts\ActivityOptions;

class CreateUserAccount implements Activity
{
    private array $payload;

    private ?ActivityOptions $options;

    public function __construct(array $payload, ?ActivityOptions $options = null)
    {
        $this->payload = $payload;
        $this->options = $options;
    }

    public function handle(array $payload): array
    {
        // Simulate database creation
        // In a real app: $user = User::create($payload);

        $fakeUserId = 'user_'.md5($payload['email']);

        // Simulate random failure to demonstrate retries
        if (rand(1, 10) === 1) {
            throw new RuntimeException('Database connection failed (simulated)');
        }

        return ['id' => $fakeUserId, 'status' => 'created'];
    }

    public function onFailure(string $instanceId, Throwable $e): void
    {
        // Log failure
    }

    public function compensate(string $instanceId, array $originalPayload): void
    {
        // Delete user if workflow fails later
    }
}
