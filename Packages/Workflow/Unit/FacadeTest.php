<?php

declare(strict_types=1);

namespace Tests\Packages\Workflow\Unit;

use Mockery;
use Workflow\Contracts\History;
use Workflow\Engine\WorkflowRunner;
use Workflow\Workflow;

describe('Workflow Facade', function () {
    beforeEach(function () {
        $this->history = Mockery::mock(History::class);
        $this->runner = Mockery::mock(WorkflowRunner::class);

        // Register mocks in container
        container()->instance(History::class, $this->history);
        container()->instance(WorkflowRunner::class, $this->runner);
    });

    test('run() creates instance and executes it', function () {
        $workflowClass = 'TestWorkflow';
        $input = ['foo' => 'bar'];
        $instanceId = 'wf_123';

        $this->history->shouldReceive('createNewInstance')
            ->with($workflowClass, Mockery::any(), $input)
            ->once()
            ->andReturn($instanceId);

        $this->runner->shouldReceive('execute')
            ->with($instanceId)
            ->once();

        $resultId = Workflow::run($workflowClass, $input);

        expect($resultId)->toBe($instanceId);
    });

    test('execute() calls runner', function () {
        $instanceId = 'wf_123';

        $this->runner->shouldReceive('execute')
            ->with($instanceId)
            ->once();

        Workflow::execute($instanceId);
    });
});
