<?php

declare(strict_types=1);

namespace Tests\Packages\Workflow\Helpers\WorkflowStubs;

use Throwable;
use Workflow\Contracts\Activity;
use Workflow\Contracts\ActivityOptions;

class SendWelcomeEmail implements Activity
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
        // Simulate sending email
        // In real implementation: resolve(Mailer::class)->send($welcomeEmailMailable);

        return ['sent' => true, 'timestamp' => time()];
    }

    public function onFailure(string $instanceId, Throwable $e): void
    {
        // Log email failure
    }

    public function compensate(string $instanceId, array $originalPayload): void
    {
        // Cannot un-send email, maybe send apology?
    }
}
