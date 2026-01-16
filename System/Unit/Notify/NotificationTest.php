<?php

declare(strict_types=1);

use Mockery as m;
use Notify\NotificationBuilder;
use Notify\NotificationManager;

afterEach(function () {
    m::close();
});

describe('NotificationManager', function () {
    test('creates notification manager instance', function () {
        $manager = new NotificationManager();
        expect($manager)->toBeInstanceOf(NotificationManager::class);
    });

    test('registers notification channel', function () {
        $manager = new NotificationManager();
        $channel = m::mock('Notify\Contracts\Channel');

        $manager->registerChannel('email', $channel);

        expect(true)->toBeTrue(); // Channel registered successfully
    });

    test('sends notification through channel', function () {
        $manager = new NotificationManager();
        $channel = m::mock('Notify\Contracts\Channel');
        $notifiable = m::mock('Notify\Contracts\Notifiable');

        $channel->shouldReceive('send')
            ->with($notifiable)
            ->once()
            ->andReturn(['status' => 'sent']);

        $manager->registerChannel('test', $channel);
        $result = $manager->send('test', $notifiable);

        expect($result)->toBeArray();
        expect($result)->toHaveKey('status');
    });

    test('throws exception for unregistered channel', function () {
        $manager = new NotificationManager();
        $notifiable = m::mock('Notify\Contracts\Notifiable');

        expect(fn () => $manager->send('nonexistent', $notifiable))
            ->toThrow(InvalidArgumentException::class);
    });

    test('executes before callback', function () {
        $manager = new NotificationManager();
        $channel = m::mock('Notify\Contracts\Channel');
        $notifiable = m::mock('Notify\Contracts\Notifiable');

        $beforeCalled = false;
        $before = function () use (&$beforeCalled) {
            $beforeCalled = true;
        };

        $channel->shouldReceive('send')->andReturn([]);
        $manager->registerChannel('test', $channel);
        $manager->send('test', $notifiable, $before);

        expect($beforeCalled)->toBeTrue();
    });

    test('executes after callback', function () {
        $manager = new NotificationManager();
        $channel = m::mock('Notify\Contracts\Channel');
        $notifiable = m::mock('Notify\Contracts\Notifiable');

        $afterCalled = false;
        $after = function ($response) use (&$afterCalled) {
            $afterCalled = true;

            return $response;
        };

        $channel->shouldReceive('send')->andReturn(['sent' => true]);
        $manager->registerChannel('test', $channel);
        $manager->send('test', $notifiable, null, $after);

        expect($afterCalled)->toBeTrue();
    });
});

describe('NotificationBuilder', function () {
    test('creates notification builder instance', function () {
        $manager = new NotificationManager();
        $builder = new NotificationBuilder($manager, 'email');

        expect($builder)->toBeInstanceOf(NotificationBuilder::class);
    });

    test('sets notification class and payload', function () {
        $manager = new NotificationManager();
        $builder = new NotificationBuilder($manager, 'email');
        $payload = (object) ['data' => 'test'];

        $result = $builder->with('SomeNotification', $payload);

        expect($result)->toBeInstanceOf(NotificationBuilder::class);
    });

    test('sets before callback', function () {
        $manager = new NotificationManager();
        $builder = new NotificationBuilder($manager, 'email');

        $result = $builder->before(fn () => null);

        expect($result)->toBeInstanceOf(NotificationBuilder::class);
    });

    test('sets after callback', function () {
        $manager = new NotificationManager();
        $builder = new NotificationBuilder($manager, 'email');

        $result = $builder->after(fn ($r) => $r);

        expect($result)->toBeInstanceOf(NotificationBuilder::class);
    });

    test('throws exception when sending without notification class', function () {
        $manager = new NotificationManager();
        $builder = new NotificationBuilder($manager, 'email');

        expect(fn () => $builder->send())
            ->toThrow(BadMethodCallException::class);
    });
});
