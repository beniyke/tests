<?php

declare(strict_types=1);

namespace Tests\Packages\Workflow\Helpers;

use Generator;
use Workflow\Contracts\Workflow;

class WorkflowFeatureTestSimpleWorkflow implements Workflow
{
    public function execute(array $input): Generator
    {
        yield new WorkflowFeatureTestActivity(['data' => $input['value']]);
    }

    public function handleSignal(string $signalName, array $payload): void
    {
    }
}
