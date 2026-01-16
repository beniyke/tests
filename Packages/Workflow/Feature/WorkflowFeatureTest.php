<?php

declare(strict_types=1);

namespace Tests\Packages\Workflow\Feature;

use Helpers\File\Contracts\LoggerInterface;
use Testing\Support\DatabaseTestHelper;
use Tests\Packages\Workflow\Helpers\WorkflowFeatureTestActivity;
use Tests\Packages\Workflow\Helpers\WorkflowFeatureTestSimpleWorkflow;
use Workflow\Contracts\ActivityOptions;
use Workflow\Contracts\Queue;
use Workflow\Engine\WorkflowRunner;
use Workflow\Infrastructure\DatabaseHistoryRepository;

beforeEach(function () {
    // Setup Test Environment (Schema + Migrations)
    DatabaseTestHelper::setupTestEnvironment(['Workflow']);
});

afterEach(function () {
    DatabaseTestHelper::dropAllTables();
    DatabaseTestHelper::resetDefaultConnection();
});

// Test that workflow can execute a simple activity
test('workflow can execute simple activity', function () {
    $instanceId = 'test-instance-' . uniqid();
    $input = ['value' => 'test'];

    // 1. Start Workflow - create new workflow instance
    $repo = new DatabaseHistoryRepository();
    $repo->createInstance($instanceId, WorkflowFeatureTestSimpleWorkflow::class, $input);

    // 2. Run Workflow (Step 1) - mock queue to capture dispatched activity
    $queue = $this->createMock(Queue::class);
    $queue->expects($this->once())
        ->method('dispatchActivity')
        ->with(
            $instanceId,
            WorkflowFeatureTestActivity::class,
            ['data' => 'test'],
            $this->callback(function ($options) {
                return $options instanceof ActivityOptions && $options->timeout === 60;
            })
        );

    $runner = new WorkflowRunner($repo, $queue, $this->createMock(LoggerInterface::class));
    $runner->execute($instanceId);
});

// Test that workflow replays completed activities and completes successfully
test('workflow replays and completes', function () {
    $instanceId = 'test-replay-' . uniqid();
    $input = ['value' => 'test'];
    $repo = new DatabaseHistoryRepository();

    // 1. Setup initial state (Workflow Started)
    $repo->createInstance($instanceId, WorkflowFeatureTestSimpleWorkflow::class, $input);

    // 2. Simulate Activity Completion - record that activity already completed
    $repo->recordEvent($instanceId, 'TestActivityCompleted', ['result' => ['status' => 'done']]);

    // 3. Run Workflow (Should replay activity and complete) - should not dispatch again
    $queue = $this->createMock(Queue::class);
    $queue->expects($this->never())->method('dispatchActivity'); // Should not dispatch again

    $runner = new WorkflowRunner($repo, $queue, $this->createMock(LoggerInterface::class));
    $runner->execute($instanceId);

    // 4. Verify Workflow Completed - check that workflow reached completion
    $history = $repo->getHistory($instanceId);
    $lastEvent = end($history);
    expect($lastEvent['type'])->toBe('WorkflowCompleted');
});
