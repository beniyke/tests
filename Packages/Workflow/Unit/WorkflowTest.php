<?php

declare(strict_types=1);

namespace Tests\Packages\Workflow\Unit;

use Generator;
use Helpers\File\Contracts\LoggerInterface;
use Helpers\File\Paths;
use Mockery;
use Tests\Packages\Workflow\Helpers\TestWorkflow;
use Workflow\Commands\Timer;
use Workflow\Contracts\History;
use Workflow\Contracts\Queue;
use Workflow\Contracts\Workflow;
use Workflow\Engine\WorkflowRunner;

describe('Workflow System', function () {
    beforeEach(function () {
        require_once Paths::basePath('packages/Workflow/Helper/workflow.php');

        $this->history = Mockery::mock(History::class);
        $this->queue = Mockery::mock(Queue::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->logger->shouldReceive('setLogFile')->andReturnSelf();
        $this->logger->shouldReceive('error')->andReturnUsing(function ($msg) {
            echo "LOGGER ERROR: $msg\n";
        });
        $this->logger->shouldReceive('info')->andReturnUsing(function ($msg) {
            echo "LOGGER INFO: $msg\n";
        });
        $this->logger->shouldIgnoreMissing();
    });

    test('WorkflowRunner executes workflow', function () {
        $instanceId = 'test-instance-1';
        $input = ['start' => true];

        $this->history->shouldReceive('getHistory')
            ->with($instanceId)
            ->andReturn([
                0 => ['workflow_class' => TestWorkflow::class, 'input' => $input],
            ]);

        // Expect command execution
        $this->queue->shouldReceive('dispatchActivity')
            ->once();

        $runner = new WorkflowRunner($this->history, $this->queue, $this->logger);
        $runner->execute($instanceId);
    });

    test('WorkflowRunner executes closure-based workflow', function () {
        $instanceId = 'test-closure-1';
        $input = [];

        // Mock a workflow that yields a closure
        $workflow = new class () implements Workflow {
            public function execute(array $input): Generator
            {
                yield function () {
                    return 'closure result';
                };
            }

            public function handleSignal(string $signalName, array $payload): void
            {
            }
        };

        $this->history->shouldReceive('getHistory')
            ->with($instanceId)
            ->andReturn([
                0 => ['workflow_class' => get_class($workflow), 'input' => $input],
            ]);

        // Expect closure result to be recorded
        $this->history->shouldReceive('recordEvent')
            ->with($instanceId, 'InlineActivityCompleted', ['result' => 'closure result'])
            ->once();

        $this->history->shouldReceive('recordEvent')
            ->with($instanceId, 'WorkflowCompleted')
            ->once();

        $runner = new WorkflowRunner($this->history, $this->queue, $this->logger);
        $runner->execute($instanceId);
    });

    test('WorkflowRunner handles Timer command', function () {
        $instanceId = 'test-timer-1';
        $input = [];

        $timer = days(3);
        expect($timer)->toBeInstanceOf(Timer::class);
        expect($timer->getPayload()['seconds'])->toBe(3 * 24 * 60 * 60);

        $this->queue->shouldReceive('dispatchTimer')
            ->with($instanceId, 3 * 24 * 60 * 60)
            ->once();

        $timer->execute($instanceId, $this->history, $this->queue);
    });
});
