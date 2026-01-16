<?php

declare(strict_types=1);

namespace Tests\System\Support\Tasks;

use Queue\BaseTask;
use Queue\Scheduler;

/**
 * A simple task stub that always fails
 */
class FailTask extends BaseTask
{
    protected function execute(): bool
    {
        return false;
    }

    protected function successMessage(): string
    {
        return 'Task succeeded';
    }

    protected function failedMessage(): string
    {
        return 'Task failed';
    }

    public function period(Scheduler $scheduler): Scheduler
    {
        return $scheduler;
    }
}
