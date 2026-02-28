<?php

declare(strict_types=1);

namespace Tests;

use Carbon\Carbon;
use Core\Event;
use Core\Ioc\Container;
use Core\Ioc\ContainerInterface;
use Core\ProviderManager;
use Core\Route\Route;
use Database\BaseModel;
use Database\ConnectionInterface;
use Database\DB;
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

        $container = Container::getInstance();

        // Capture the connection if it exists to preserve in-memory state
        $connection = $container->has(ConnectionInterface::class)
            ? $container->get(ConnectionInterface::class)
            : null;

        $container->restore();

        // Re-inject the connection to maintain state across tests in the same process
        if ($connection) {
            $container->instance(ConnectionInterface::class, $connection);
            DB::setDefaultConnection($connection);
            BaseModel::setConnection($connection);
        }

        // Reset static state for framework facades
        DB::reset();
        Event::reset();
        Route::reset();

        // Re-boot service providers to re-register static state (listeners, etc.)
        if ($container->has(ProviderManager::class)) {
            $container->get(ProviderManager::class)->reboot();
        }

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
