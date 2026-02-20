<?php

declare(strict_types=1);

namespace Tests\Packages\Activity\Feature;

use Activity\Activity;
use Activity\Models\Activity as ActivityModel;
use Activity\Services\ActivityManagerService;
use App\Models\User;
use Core\Ioc\Container;
use Core\Services\ConfigServiceInterface;
use Helpers\DateTimeHelper;
use Mockery;
use ReflectionClass;
use Security\Auth\Interfaces\AuthManagerInterface;
use Testing\Support\DatabaseTestHelper;

beforeEach(function () {
    $this->refreshDatabase();
    DatabaseTestHelper::runPackageMigrations('Activity');
});

afterEach(function () {
    DateTimeHelper::setTestNow();
});

test('facade returns activity manager instance', function () {
    $manager = Activity::description('test');
    expect($manager)->toBeInstanceOf(ActivityManagerService::class);
});

test('loads default configuration', function () {
    $config = resolve(ConfigServiceInterface::class);
    $config->set('activity.default_tag', 'system');

    $manager = new ActivityManagerService($config);

    $reflection = new ReflectionClass($manager);
    $property = $reflection->getProperty('tag');
    $property->setAccessible(true);

    expect($property->getValue($manager))->toBe('system');
});

describe('Logging', function () {
    test('helper logs activity successfully', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com', 'password' => 'secret', 'gender' => 'male']);

        $result = Activity::description('User logged in')->user((int) $user->id)->immediate()->log();

        expect($result)->toBeTrue();

        $activity = ActivityModel::latest()->first();
        expect($activity)->not->toBeNull()
            ->and($activity->description)->toBe('User logged in')
            ->and($activity->user_id)->toEqual($user->id);
    });

    test('facade fluent logging works', function () {
        $user = User::create(['name' => 'Fluent User', 'email' => 'fluent@example.com', 'password' => 'secret', 'gender' => 'male']);

        Activity::description('Fluent log')
            ->user((int) $user->id)
            ->tag('auth')
            ->level('critical')
            ->metadata(['ip' => '127.0.0.1'])
            ->immediate()
            ->log();

        $activity = ActivityModel::latest()->first();

        expect($activity->tag)->toBe('auth')
            ->and($activity->level)->toBe('critical')
            ->and($activity->metadata['ip'])->toBe('127.0.0.1');
    });

    test('helper automatically resolves user_id if not provided', function () {
        $user = User::create(['name' => 'Auto User', 'email' => 'auto@example.com', 'password' => 'secret', 'gender' => 'male']);

        // Mock the AuthManager to return our user
        $authMock = Mockery::mock(AuthManagerInterface::class);
        $authMock->shouldReceive('user')->andReturn($user);
        $authMock->shouldReceive('guard')->andReturnSelf();

        // Swap the instance in the container
        Container::getInstance()->instance(AuthManagerInterface::class, $authMock);

        // We use the Facade directly to ensure it logs immediately for the test
        Activity::description('Auto-resolved log')->immediate()->log();

        $activity = ActivityModel::latest()->first();

        expect($activity)->not->toBeNull()
            ->and($activity->user_id)->toEqual($user->id);
    });

    test('interpolates data in description', function () {
        $user = User::create(['name' => 'Interpolation User', 'email' => 'inter@example.com', 'password' => 'secret', 'gender' => 'male']);

        Activity::description('User {action} item {item}')->data(['action' => 'bought', 'item' => 'Apple'])->user((int) $user->id)->immediate()->log();

        $activity = ActivityModel::latest()->first();
        expect($activity->description)->toBe('User bought item Apple');
    });
});

describe('Analytics', function () {
    test('calculates retention stats', function () {
        $user = User::create(['name' => 'Analytics User', 'email' => 'analytics@example.com', 'password' => 'secret', 'gender' => 'male']);

        // Create activity today
        DateTimeHelper::setTestNow('2026-01-20 12:00:00');
        activity('Login', null, (int) $user->id);
        // We might need to flush deferred tasks here or use immediate
        Activity::description('Login')->user((int) $user->id)->immediate()->log();

        $stats = Activity::analytics()->getRetentionStats();

        expect($stats['dau'])->toBe(1)
            ->and($stats['mau'])->toBe(1)
            ->and($stats['stickiness'])->toBe('100%');
    });

    test('tracks channel distribution', function () {
        $user = User::create(['name' => 'Channel User', 'email' => 'channel@example.com', 'password' => 'secret', 'gender' => 'male']);

        // Disable auto-capture for this test to allow manual channel setting
        $config = resolve(ConfigServiceInterface::class);
        $config->set('activity.capture.channel', false);

        Activity::description('CLI Log')->user((int) $user->id)->channel('cli')->immediate()->log();
        Activity::description('Web Log')->user((int) $user->id)->channel('web')->immediate()->log();

        // Re-enable for subsequent tests if any (safe practice)
        $config->set('activity.capture.channel', true);

        $stats = Activity::analytics()->getChannelStats();

        // Note: Caching might affect immediate retrieval in real scenarios,
        // but simple array cache in tests usually works instantly or we might need to clear it.
        // For unit tests without Redis, array driver is usually immediate.

        expect($stats)->toHaveCount(2);
    });
});

describe('Retrieval', function () {
    test('lists user activities with pagination', function () {
        $user = User::create(['name' => 'Retrieval User', 'email' => 'retrieval@example.com', 'password' => 'secret', 'gender' => 'male']);

        Activity::description('Log 1')->user((int) $user->id)->immediate()->log();
        Activity::description('Log 2')->user((int) $user->id)->immediate()->log();

        $manager = resolve(ActivityManagerService::class);
        $paginator = $manager->listUserActivities($user, 1, 1);

        expect($paginator->total())->toBe(2)
            ->and($paginator->items())->toHaveCount(1)
            ->and($paginator->items()[0]->description)->toBe('Log 2');
    });

    test('lists recent activities across the system', function () {
        $user1 = User::create(['name' => 'User 1', 'email' => 'u1@example.com', 'password' => 'secret', 'gender' => 'male']);
        $user2 = User::create(['name' => 'User 2', 'email' => 'u2@example.com', 'password' => 'secret', 'gender' => 'male']);

        DateTimeHelper::setTestNow('2026-02-01 10:00:00');
        Activity::description('User 1 Log')->user((int) $user1->id)->immediate()->log();

        DateTimeHelper::setTestNow('2026-02-07 10:00:00');
        Activity::description('User 2 Log')->user((int) $user2->id)->immediate()->log();

        $manager = resolve(ActivityManagerService::class);
        $paginator = $manager->listRecentActivities(7);

        expect($paginator->items())->toHaveCount(2);

        $paginatorToday = $manager->listRecentActivities(0);
        expect($paginatorToday->items())->toHaveCount(1)
            ->and($paginatorToday->items()[0]->description)->toBe('User 2 Log');
    });

    test('generates human readable summary', function () {
        $user = User::create(['name' => 'Summary User', 'email' => 'sum@example.com', 'password' => 'secret', 'gender' => 'male']);

        DateTimeHelper::setTestNow('2026-02-07 10:00:00');
        Activity::description('Logged in from {browser}')->data(['browser' => 'Chrome'])->user((int) $user->id)->immediate()->log();

        $activity = ActivityModel::latest()->first();

        DateTimeHelper::setTestNow('2026-02-07 10:05:00');
        $manager = resolve(ActivityManagerService::class);
        $summary = $manager->getSummary($activity);

        expect($summary)->toContain('Logged in from Chrome')
            ->and($summary)->toContain('5 minutes ago');
    });
});

describe('Pruning', function () {
    test('prunes old activities', function () {
        $user = User::create(['name' => 'Prune User', 'email' => 'prune@example.com', 'password' => 'secret', 'gender' => 'male']);

        // Create old activity
        DateTimeHelper::setTestNow('2025-01-01 12:00:00');
        Activity::description('Old Log')->user((int) $user->id)->immediate()->log();

        // Create new activity
        DateTimeHelper::setTestNow('2026-01-28 12:00:00');
        Activity::description('New Log')->user((int) $user->id)->immediate()->log();

        // Prune older than 30 days
        $count = ActivityModel::prune(30);

        expect($count)->toBe(1);
        expect(ActivityModel::count())->toBe(1);
    });
});
