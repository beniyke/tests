<?php

declare(strict_types=1);

namespace Tests\Packages\Workflow\Feature;

use Helpers\File\Contracts\LoggerInterface;
use Mockery;
use Tests\Packages\Workflow\Helpers\WorkflowStubs\CreateUserAccount;
use Tests\Packages\Workflow\Helpers\WorkflowStubs\UserOnboardingWorkflow;
use Workflow\Contracts\History;
use Workflow\Contracts\Queue;
use Workflow\Engine\WorkflowRunner;

describe('User Onboarding Workflow Example', function () {
    beforeEach(function () {
        $this->history = Mockery::mock(History::class);
        $this->queue = Mockery::mock(Queue::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->logger->shouldReceive('setLogFile')->andReturnSelf();
        $this->logger->shouldReceive('info', 'debug', 'error', 'warning', 'critical')->andReturnNull();
    });

    test('runs onboarding workflow successfully', function () {
        $instanceId = 'onboarding-123';
        $input = ['email' => 'john@example.com', 'name' => 'John Doe'];

        // Mock History for initial run
        $this->history->shouldReceive('getHistory')
            ->with($instanceId)
            ->andReturn([
                0 => ['workflow_class' => UserOnboardingWorkflow::class, 'input' => $input],
            ]);

        // Expect CreateUserAccount activity dispatch
        $this->queue->shouldReceive('dispatchActivity')
            ->with($instanceId, CreateUserAccount::class, Mockery::any(), Mockery::any())
            ->once();

        $runner = new WorkflowRunner($this->history, $this->queue, $this->logger);
        $runner->execute($instanceId);
    });
});
