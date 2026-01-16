<?php

declare(strict_types=1);

use Core\Services\ConfigServiceInterface;
use Core\Support\Adapters\Interfaces\OSCheckerInterface;
use Helpers\File\Contracts\CacheInterface;
use Queue\Commands\PauseQueueCommand;
use Queue\Commands\ResumeQueueCommand;
use Queue\Interfaces\JobServiceInterface;
use Queue\QueueDispatcher;
use Queue\QueueManager;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

beforeEach(function () {
    // Mock CacheInterface
    $this->cache = Mockery::mock(CacheInterface::class);
    $this->cache->shouldReceive('withPath')->andReturn($this->cache);

    // Mock OSCheckerInterface
    $this->osChecker = Mockery::mock(OSCheckerInterface::class);

    // Bind mocks to container
    $container = container();
    $container->bind(CacheInterface::class, fn () => $this->cache);
    $container->bind(OSCheckerInterface::class, fn () => $this->osChecker);
});

test('queue:pause command pauses a running queue', function () {
    $queueName = 'default';
    $cacheKey = "worker_status_{$queueName}";

    // Expect check if running
    $this->cache->shouldReceive('has')
        ->with($cacheKey)
        ->once()
        ->andReturn(true);

    // Expect read current status (not paused)
    $this->cache->shouldReceive('read')
        ->with($cacheKey)
        ->times(2) // Once for check, once for verification
        ->andReturn(date('Y-m-d H:i:s'), 'pause');

    // Expect write pause
    $this->cache->shouldReceive('write')
        ->with($cacheKey, 'pause')
        ->once();

    $application = new Application();
    $application->add(new PauseQueueCommand());
    $command = $application->find('queue:pause');
    $commandTester = new CommandTester($command);

    $commandTester->execute([
        '--identifier' => $queueName,
    ]);

    $output = $commandTester->getDisplay();
    expect($output)->toContain("Queue '{$queueName}' has been paused successfully");
});

test('queue:resume command resumes a paused queue', function () {
    $queueName = 'default';
    $cacheKey = "worker_status_{$queueName}";

    // Expect check if running (file exists)
    $this->cache->shouldReceive('has')
        ->with($cacheKey)
        ->once()
        ->andReturn(true);

    // Expect read current status (is paused)
    $this->cache->shouldReceive('read')
        ->with($cacheKey)
        ->times(2) // Once for check, once for verification
        ->andReturn('pause', date('Y-m-d H:i:s'));

    // Expect write timestamp (resume)
    $this->cache->shouldReceive('write')
        ->with($cacheKey, Mockery::type('string'))
        ->once();

    $application = new Application();
    $application->add(new ResumeQueueCommand());
    $command = $application->find('queue:resume');
    $commandTester = new CommandTester($command);

    $commandTester->execute([
        '--identifier' => $queueName,
    ]);

    $output = $commandTester->getDisplay();
    expect($output)->toContain("Queue '{$queueName}' has been resumed successfully");
});

test('queue:pause warns if queue is not running', function () {
    $queueName = 'missing_queue';
    $cacheKey = "worker_status_{$queueName}";

    // Expect check if running
    $this->cache->shouldReceive('has')
        ->with($cacheKey)
        ->once()
        ->andReturn(false);

    $application = new Application();
    $application->add(new PauseQueueCommand());
    $command = $application->find('queue:pause');
    $commandTester = new CommandTester($command);

    $commandTester->execute([
        '--identifier' => $queueName,
    ]);

    $output = $commandTester->getDisplay();
    expect($output)->toContain("Queue '{$queueName}' is not running");
});

test('queue:resume warns if queue is not paused', function () {
    $queueName = 'active_queue';
    $cacheKey = "worker_status_{$queueName}";

    // Expect check if running
    $this->cache->shouldReceive('has')
        ->with($cacheKey)
        ->once()
        ->andReturn(true);

    // Expect read current status (not paused)
    $this->cache->shouldReceive('read')
        ->with($cacheKey)
        ->once()
        ->andReturn(date('Y-m-d H:i:s'));

    $application = new Application();
    $application->add(new ResumeQueueCommand());
    $command = $application->find('queue:resume');
    $commandTester = new CommandTester($command);

    $commandTester->execute([
        '--identifier' => $queueName,
    ]);

    $output = $commandTester->getDisplay();
    expect($output)->toContain("Queue '{$queueName}' is not paused");
});

test('QueueDispatcher respects pause state', function () {
    $queueName = 'default';
    $cacheKey = "worker_status_{$queueName}";

    // Mock dependencies for QueueDispatcher
    $config = Mockery::mock(ConfigServiceInterface::class);
    $config->shouldReceive('get')->andReturn(null);

    $jobService = Mockery::mock(JobServiceInterface::class);
    $jobService->shouldReceive('cleanStuckJobs')->andReturn(0);

    $manager = Mockery::mock(QueueManager::class);

    // Expect check if paused
    $this->cache->shouldReceive('withPath')->andReturn($this->cache);
    $this->cache->shouldReceive('has')->with($cacheKey)->andReturn(true);
    $this->cache->shouldReceive('read')->with($cacheKey)->andReturn('pause');

    $dispatcher = new QueueDispatcher($config, $jobService, $manager, $this->cache);
    $dispatcher->pending($queueName);

    $result = $dispatcher->run();

    expect($result)->toContain("Queue '{$queueName}' is currently PAUSED");
});
