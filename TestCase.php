<?php

declare(strict_types=1);

namespace Tests;

use Carbon\Carbon;
use Core\Ioc\Container;
use Core\Ioc\ContainerInterface;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase as BaseTestCase;
use ReflectionClass;
use Testing\Concerns\InteractsWithAuthentication;
use Testing\Concerns\InteractsWithDatabase;
use Testing\Concerns\InteractsWithFakes;
use Testing\Concerns\InteractsWithPackages;
use Testing\Concerns\InteractsWithTime;
use Testing\Concerns\MakesHttpRequests;
use Testing\Concerns\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithAuthentication;
    use InteractsWithDatabase;
    use InteractsWithFakes;
    use InteractsWithTime;
    use MakesHttpRequests;
    use RefreshDatabase;
    use InteractsWithPackages;

    protected array $afterApplicationDestroyedCallbacks = [];

    protected string $seederPath;

    protected string $testPackagePath;

    protected string $appConfigPath;

    protected string $appStoragePath;

    protected bool $refreshDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureContainerIntegrity();

        if (method_exists($this, 'refreshDatabase')) {
            $this->refreshDatabase();
        }
    }

    protected function ensureContainerIntegrity(): void
    {
        $container = Container::getInstance();

        if ($container instanceof MockInterface) {
            $reflection = new ReflectionClass(Container::class);

            $property = $reflection->getProperty('instance');
            $property->setAccessible(true);
            $property->setValue(null, null);

            $container = Container::getInstance();
            $container->singleton(ContainerInterface::class, fn () => $container);
        }
    }

    protected function tearDown(): void
    {
        $this->callAfterApplicationDestroyedCallbacks();

        Carbon::setTestNow();

        parent::tearDown();
    }

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
