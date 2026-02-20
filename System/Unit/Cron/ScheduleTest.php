<?php

declare(strict_types=1);

namespace Tests\System\Unit\Cron;

use Core\Ioc\Container;
use Cron\Interfaces\CronInterface;
use Cron\Schedule;
use Cron\Task;
use Helpers\DateTimeHelper;
use Helpers\File\Adapters\Interfaces\PathResolverInterface;
use Helpers\File\Paths;
use ReflectionClass;

beforeEach(function () {
    /** @var PathResolverInterface $paths */
    $paths = resolve(PathResolverInterface::class);
    /** @var Schedule $this->schedule */
    $this->schedule = new Schedule($paths);
    Container::getInstance()->instance(CronInterface::class, $this->schedule);
});

test('it can manually add a command', function () {
    $task = $this->schedule->command('test:command');

    expect($task)->toBeInstanceOf(Task::class)
        ->and($task->getSignature())->toBe('test:command');
});

test('it discovers schedules in packages', function () {
    // We know Scout has a schedule.php
    $this->schedule->discover();

    $reflection = new ReflectionClass($this->schedule);
    $property = $reflection->getProperty('tasks');
    $property->setAccessible(true);
    $tasks = $property->getValue($this->schedule);

    $foundScout = false;
    foreach ($tasks as $task) {
        if ($task->getSignature() === 'scout:reminders') {
            $foundScout = true;
            break;
        }
    }

    expect($foundScout)->toBeTrue('Scout schedule was not discovered');
});

test('it discovers class-based schedules', function () {
    /** @var PathResolverInterface $paths */
    $paths = mock(PathResolverInterface::class);
    $paths->shouldReceive('basePath')->andReturn('/non/existent/packages');
    $paths->shouldReceive('appSourcePath')->andReturn('/non/existent/app/src');

    $fixturePath = Paths::testPath('Fixtures/Schedules');
    $paths->shouldReceive('appPath')->with('Schedules')->andReturn($fixturePath);

    // Manually load the fixture because it's not in the autoloader paths
    require_once $fixturePath . DIRECTORY_SEPARATOR . 'TestSchedule.php';

    $schedule = new Schedule($paths);
    $schedule->discover();

    $reflection = new ReflectionClass($schedule);
    $property = $reflection->getProperty('tasks');
    $property->setAccessible(true);
    $tasks = $property->getValue($schedule);

    $foundTestFixture = false;
    foreach ($tasks as $task) {
        if ($task->getSignature() === 'test:fixture') {
            $foundTestFixture = true;
            break;
        }
    }

    expect($foundTestFixture)->toBeTrue('Tests\Fixtures\Schedules\TestSchedule was not discovered');
});

test('it correctly identifies due tasks', function () {
    $task = $this->schedule->command('due:command')->everyMinute();

    $now = DateTimeHelper::now();
    expect($task->isDue($now))->toBeTrue();

    $taskHourly = $this->schedule->command('hourly:command')->hourly();

    // Mocking time logic
    $atTopOffHour = DateTimeHelper::createFromFormat('Y-m-d H:i:s', '2026-02-06 10:00:00');
    expect($taskHourly->isDue($atTopOffHour))->toBeTrue();

    $atHalfHour = DateTimeHelper::createFromFormat('Y-m-d H:i:s', '2026-02-06 10:30:00');
    expect($taskHourly->isDue($atHalfHour))->toBeFalse();
});

test('it identifies complex due tasks', function () {
    // Every 2 minutes between minute 10 and 20
    $task = $this->schedule->command('complex:command')->cron('10-20/2 * * * *');

    expect($task->isDue(DateTimeHelper::createFromFormat('Y-m-d H:i:s', '2026-02-06 10:10:00')))->toBeTrue()
        ->and($task->isDue(DateTimeHelper::createFromFormat('Y-m-d H:i:s', '2026-02-06 10:11:00')))->toBeFalse()
        ->and($task->isDue(DateTimeHelper::createFromFormat('Y-m-d H:i:s', '2026-02-06 10:12:00')))->toBeTrue()
        ->and($task->isDue(DateTimeHelper::createFromFormat('Y-m-d H:i:s', '2026-02-06 10:20:00')))->toBeTrue()
        ->and($task->isDue(DateTimeHelper::createFromFormat('Y-m-d H:i:s', '2026-02-06 10:22:00')))->toBeFalse();
});
