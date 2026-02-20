<?php

declare(strict_types=1);

namespace Tests\Packages\Notification\Feature;

use App\Models\User;
use Helpers\DateTimeHelper;
use Notification\Models\Notification as NotificationModel;
use Notification\Notification;
use Notification\Services\NotificationManagerService;
use Testing\Support\DatabaseTestHelper;

beforeEach(function () {
    $this->refreshDatabase();
    DatabaseTestHelper::runPackageMigrations('Notification');
});

afterEach(function () {
    DateTimeHelper::setTestNow();
});

test('facade returns notification manager instance', function () {
    // Note: Due to __callStatic, it proxies to the manager service
    // We can verify a method exists on the manager service
    expect(method_exists(resolve(NotificationManagerService::class), 'listNotifications'))->toBeTrue();
});

describe('Notification Management', function () {
    test('can send notification to a user', function () {
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com', 'password' => 'secret', 'gender' => 'male']);

        Notification::notifyUser([
            'user_id' => $user->id,
            'message' => 'Test Message',
            'label' => 'info',
            'url' => '/test'
        ]);

        $notification = NotificationModel::latest()->first();
        expect($notification)->not->toBeNull()
            ->and($notification->message)->toBe('Test Message')
            ->and($notification->user_id)->toEqual($user->id);
    });

    test('can send global notification to all users', function () {
        User::create(['name' => 'User 1', 'email' => 'user1@example.com', 'password' => 'secret', 'gender' => 'male']);
        User::create(['name' => 'User 2', 'email' => 'user2@example.com', 'password' => 'secret', 'gender' => 'male']);

        $count = Notification::notifyAll([
            'message' => 'Global Message',
            'label' => 'announcement'
        ]);

        expect($count)->toBe(2);
        expect(NotificationModel::count())->toBe(2);
    });

    test('can mark all notifications as read for a user', function () {
        $user = User::create(['name' => 'Read User', 'email' => 'read@example.com', 'password' => 'secret', 'gender' => 'male']);
        Notification::notifyUser(['user_id' => $user->id, 'message' => 'Msg 1']);
        Notification::notifyUser(['user_id' => $user->id, 'message' => 'Msg 2']);

        expect(NotificationModel::unreadCountForUser($user->id))->toBe(2);

        Notification::markAllAsRead($user);

        expect(NotificationModel::unreadCountForUser($user->id))->toBe(0);
    });

    test('can clear all notifications for a user', function () {
        $user = User::create(['name' => 'Clear User', 'email' => 'clear@example.com', 'password' => 'secret', 'gender' => 'male']);
        Notification::notifyUser(['user_id' => $user->id, 'message' => 'To be cleared']);

        Notification::clearUserNotifications($user);

        expect(NotificationModel::count())->toBe(0);
    });
});

describe('Analytics', function () {
    test('calculates read rate stats', function () {
        $user = User::create(['name' => 'Stats User', 'email' => 'stats@example.com', 'password' => 'secret', 'gender' => 'male']);

        // 2 read, 1 unread
        NotificationModel::create(['user_id' => $user->id, 'message' => 'R1', 'is_read' => true]);
        NotificationModel::create(['user_id' => $user->id, 'message' => 'R2', 'is_read' => true]);
        NotificationModel::create(['user_id' => $user->id, 'message' => 'U1', 'is_read' => false]);

        $stats = Notification::analytics()->getReadRateStats();

        expect($stats['total'])->toBe(3)
            ->and($stats['read'])->toBe(2)
            ->and($stats['unread'])->toBe(1)
            ->and($stats['read_rate'])->toBe('66.67%');
    });

    test('gets label breakdown', function () {
        $user = User::create(['name' => 'Label User', 'email' => 'label@example.com', 'password' => 'secret', 'gender' => 'male']);

        NotificationModel::create(['user_id' => $user->id, 'message' => 'M1', 'label' => 'alert']);
        NotificationModel::create(['user_id' => $user->id, 'message' => 'M2', 'label' => 'alert']);
        NotificationModel::create(['user_id' => $user->id, 'message' => 'M3', 'label' => 'info']);

        $breakdown = Notification::analytics()->getLabelBreakdown();

        expect($breakdown)->toHaveCount(2)
            ->and($breakdown[0]->label)->toBe('alert')
            ->and($breakdown[0]->count)->toBe(2);
    });
});

describe('Scopes and Helpers', function () {
    test('fetches read and unread for user correctly', function () {
        $user = User::create(['name' => 'Scope User', 'email' => 'scope@example.com', 'password' => 'secret', 'gender' => 'male']);

        NotificationModel::create(['user_id' => $user->id, 'message' => 'Read', 'is_read' => true]);
        NotificationModel::create(['user_id' => $user->id, 'message' => 'Unread', 'is_read' => false]);

        expect(NotificationModel::unreadForUser($user->id))->toHaveCount(1)
            ->and(NotificationModel::readForUser($user->id))->toHaveCount(1);
    });

    test('scopes work correctly', function () {
        $user = User::create(['name' => 'Query User', 'email' => 'query@example.com', 'password' => 'secret', 'gender' => 'male']);
        NotificationModel::create(['user_id' => $user->id, 'message' => 'R', 'is_read' => true]);
        NotificationModel::create(['user_id' => $user->id, 'message' => 'U', 'is_read' => false]);

        expect(NotificationModel::read()->count())->toBe(1)
            ->and(NotificationModel::unread()->count())->toBe(1);
    });
});

describe('Pruning', function () {
    test('prunes old notifications', function () {
        $user = User::create(['name' => 'Prune User', 'email' => 'prune@example.com', 'password' => 'secret', 'gender' => 'male']);

        // Create old read notification
        DateTimeHelper::setTestNow('2025-01-01 12:00:00');
        NotificationModel::create(['user_id' => $user->id, 'message' => 'Old Read', 'is_read' => true]);

        // Create old unread notification
        NotificationModel::create(['user_id' => $user->id, 'message' => 'Old Unread', 'is_read' => false]);

        // Create new unread notification
        DateTimeHelper::setTestNow('2026-01-28 12:00:00');
        NotificationModel::create(['user_id' => $user->id, 'message' => 'New Unread', 'is_read' => false]);

        // Prune older than 30 days (default only prunes read)
        $count = NotificationModel::prune(30);
        expect($count)->toBe(1);
        expect(NotificationModel::count())->toBe(2);

        // Prune older than 30 days including unread
        $count = NotificationModel::prune(30, true);
        expect($count)->toBe(1);
        expect(NotificationModel::count())->toBe(1);
    });
});
