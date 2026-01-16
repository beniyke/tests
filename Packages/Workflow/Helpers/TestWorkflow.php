<?php

declare(strict_types=1);

namespace Tests\Packages\Workflow\Helpers;

use Generator;
use Workflow\Contracts\Command;
use Workflow\Contracts\History;
use Workflow\Contracts\Queue;
use Workflow\Contracts\Workflow;

class TestWorkflow implements Workflow
{
    public function execute(array $input): Generator
    {
        yield new class () implements Command {
            public function getName(): string
            {
                return 'TestActivity';
            }

            public function execute(string $instanceId, History $history, Queue $queue): void
            {
                // Simulate dispatching activity
                $queue->dispatchActivity($instanceId, TestActivity::class, ['foo' => 'bar']);
            }

            public function replay(array $recordedEvent): void
            {
            }

            public function getPayload(): array
            {
                return [];
            }
        };
    }

    public function handleSignal(string $signalName, array $payload): void
    {
    }
}
