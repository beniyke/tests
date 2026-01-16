<?php

declare(strict_types=1);

namespace Tests\System\Support\Queue;

use Queue\Worker;

/**
 * A testable Worker subclass that exposes internals for testing
 */
class TestableWorker extends Worker
{
    public bool $terminated = false;

    public bool $running = true;

    protected function terminate(): void
    {
        $this->terminated = true;
        $this->running = false; // Stop the loop
    }

    protected function sleep(int $seconds): void
    {
        // Don't sleep in tests
    }

    protected function run(): void
    {
        // Don't run actual jobs in unit tests
    }

    protected function execute(): void
    {
        // Don't execute tasks in unit tests
    }
}
