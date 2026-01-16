<?php

declare(strict_types=1);

namespace Tests;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Testing\Concerns\InteractsWithAuthentication;
use Testing\Concerns\InteractsWithDatabase;
use Testing\Concerns\InteractsWithFakes;
use Testing\Concerns\InteractsWithInMemoryDatabase;
use Testing\Concerns\InteractsWithPackages;
use Testing\Concerns\InteractsWithTime;
use Testing\Concerns\MakesHttpRequests;
use Testing\Concerns\RefreshDatabase;

abstract class PackageTestCase extends BaseTestCase
{
    use InteractsWithAuthentication;
    use InteractsWithDatabase;
    use InteractsWithFakes;
    use InteractsWithTime;
    use MakesHttpRequests;
    use InteractsWithPackages;
    use InteractsWithInMemoryDatabase;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup test environment with App migrations
        $connection = \Testing\Support\DatabaseTestHelper::setupTestEnvironment([], true);

        // Ensure connection is bound to container (redundant if Helper does it, but safe)
        if (class_exists(\Core\Ioc\Container::class)) {
            \Core\Ioc\Container::getInstance()->instance(\Database\ConnectionInterface::class, $connection);
        }
    }

    protected function tearDown(): void
    {
        $this->callAfterApplicationDestroyedCallbacks();

        Carbon::setTestNow();

        $this->teardownInMemoryDatabase();

        parent::tearDown();
    }

    protected array $afterApplicationDestroyedCallbacks = [];

    protected function beforeApplicationDestroyed(callable $callback): void
    {
        $this->afterApplicationDestroyedCallbacks[] = $callback;
    }

    protected function callAfterApplicationDestroyedCallbacks(): void
    {
        foreach ($this->afterApplicationDestroyedCallbacks as $callback) {
            $callback();
        }

        $this->afterApplicationDestroyedCallbacks = [];
    }
}
