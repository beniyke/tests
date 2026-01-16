<?php

declare(strict_types=1);

use Core\Bootstrapper;
use Core\Error\ErrorHandler;
use Core\Ioc\ContainerInterface;
use Core\Kernel;
use Core\ProviderManager;
use Core\Services\ConfigServiceInterface;

beforeEach(function () {
    $this->container = Mockery::mock(ContainerInterface::class);
    $this->config = Mockery::mock(ConfigServiceInterface::class);
    $this->bootstrapper = Mockery::mock(Bootstrapper::class);
    $this->providerManager = Mockery::mock(ProviderManager::class);
    $this->errorHandler = Mockery::mock(ErrorHandler::class);

    // Default expectation for kernel instance binding
    $this->container->shouldReceive('instance')
        ->with(Kernel::class, Mockery::type(Kernel::class))
        ->zeroOrMoreTimes();
});

describe('Kernel - Initialization', function () {
    test('constructor initializes container bindings', function () {
        $this->container->shouldReceive('singleton')
            ->with(ContainerInterface::class, Mockery::type('Closure'))
            ->once();

        $this->container->shouldReceive('singleton')
            ->with(ProviderManager::class, Mockery::type('Closure'))
            ->once();

        $this->container->shouldReceive('singleton')
            ->with(Bootstrapper::class, Mockery::type('Closure'))
            ->once();

        new Kernel($this->container, '/app/path');
    });
});

describe('Kernel - Boot Process', function () {
    test('boot runs bootstrapper', function () {
        // Setup container bindings
        $this->container->shouldReceive('singleton')->times(3);

        $this->container->shouldReceive('get')
            ->with(Bootstrapper::class)
            ->andReturn($this->bootstrapper);

        $this->bootstrapper->shouldReceive('run')->once();

        $this->container->shouldReceive('make')
            ->with(ErrorHandler::class)
            ->andReturn($this->errorHandler);

        $this->errorHandler->shouldReceive('register')->once();

        $this->container->shouldReceive('get')
            ->with(ConfigServiceInterface::class)
            ->andReturn($this->config);

        $this->config->shouldReceive('get')
            ->with('debug', false)
            ->andReturn(false);

        $this->container->shouldReceive('get')
            ->with(ProviderManager::class)
            ->andReturn($this->providerManager);

        $this->providerManager->shouldReceive('boot')->once();

        $this->config->shouldReceive('get')
            ->with('timezone')
            ->andReturn('UTC');

        $kernel = new Kernel($this->container, '/app/path');
        $kernel->boot();
    });

    test('boot registers error handler', function () {
        $this->container->shouldReceive('singleton')->times(3);

        $this->container->shouldReceive('get')
            ->with(Bootstrapper::class)
            ->andReturn($this->bootstrapper);

        $this->bootstrapper->shouldReceive('run');

        $this->container->shouldReceive('make')
            ->with(ErrorHandler::class)
            ->andReturn($this->errorHandler);

        $this->errorHandler->shouldReceive('register')->once();

        $this->container->shouldReceive('get')
            ->with(ConfigServiceInterface::class)
            ->andReturn($this->config);

        $this->config->shouldReceive('get')->with('debug', false)->andReturn(false);

        $this->container->shouldReceive('get')
            ->with(ProviderManager::class)
            ->andReturn($this->providerManager);

        $this->providerManager->shouldReceive('boot');

        $this->config->shouldReceive('get')->with('timezone')->andReturn('UTC');

        $kernel = new Kernel($this->container, '/app/path');
        $kernel->boot();
    });

    test('boot registers service providers', function () {
        $this->container->shouldReceive('singleton')->times(3);

        $this->container->shouldReceive('get')
            ->with(Bootstrapper::class)
            ->andReturn($this->bootstrapper);

        $this->bootstrapper->shouldReceive('run');

        $this->container->shouldReceive('make')
            ->with(ErrorHandler::class)
            ->andReturn($this->errorHandler);

        $this->errorHandler->shouldReceive('register');

        $this->container->shouldReceive('get')
            ->with(ConfigServiceInterface::class)
            ->andReturn($this->config);

        $this->config->shouldReceive('get')->with('debug', false)->andReturn(false);

        $this->container->shouldReceive('get')
            ->with(ProviderManager::class)
            ->andReturn($this->providerManager);

        $this->providerManager->shouldReceive('boot')->once();

        $this->config->shouldReceive('get')->with('timezone')->andReturn('UTC');

        $kernel = new Kernel($this->container, '/app/path');
        $kernel->boot();
    });

    test('boot sets timezone from config', function () {
        $this->container->shouldReceive('singleton')->times(3);

        $this->container->shouldReceive('get')
            ->with(Bootstrapper::class)
            ->andReturn($this->bootstrapper);

        $this->bootstrapper->shouldReceive('run');

        $this->container->shouldReceive('make')
            ->with(ErrorHandler::class)
            ->andReturn($this->errorHandler);

        $this->errorHandler->shouldReceive('register');

        $this->container->shouldReceive('get')
            ->with(ConfigServiceInterface::class)
            ->andReturn($this->config);

        $this->config->shouldReceive('get')->with('debug', false)->andReturn(false);

        $this->container->shouldReceive('get')
            ->with(ProviderManager::class)
            ->andReturn($this->providerManager);

        $this->providerManager->shouldReceive('boot');

        $this->config->shouldReceive('get')
            ->with('timezone')
            ->andReturn('America/New_York');

        $kernel = new Kernel($this->container, '/app/path');
        $kernel->boot();

        expect(date_default_timezone_get())->toBe('America/New_York');
    });

    test('boot throws exception if timezone missing', function () {
        $this->container->shouldReceive('singleton')->times(3);

        $this->container->shouldReceive('get')
            ->with(Bootstrapper::class)
            ->andReturn($this->bootstrapper);

        $this->bootstrapper->shouldReceive('run');

        $this->container->shouldReceive('make')
            ->with(ErrorHandler::class)
            ->andReturn($this->errorHandler);

        $this->errorHandler->shouldReceive('register');

        $this->container->shouldReceive('get')
            ->with(ConfigServiceInterface::class)
            ->andReturn($this->config);

        $this->config->shouldReceive('get')->with('debug', false)->andReturn(false);

        $this->container->shouldReceive('get')
            ->with(ProviderManager::class)
            ->andReturn($this->providerManager);

        $this->providerManager->shouldReceive('boot');

        $this->config->shouldReceive('get')
            ->with('timezone')
            ->andReturn(null);

        $kernel = new Kernel($this->container, '/app/path');
        $kernel->boot();
    })->throws(Core\Error\ConfigurationException::class, 'Timezone configuration is missing');
});

describe('Kernel - Utility Methods', function () {
    test('getContainer returns container instance', function () {
        $this->container->shouldReceive('singleton')->times(3);

        $kernel = new Kernel($this->container, '/app/path');

        expect($kernel->getContainer())->toBe($this->container);
    });
});
