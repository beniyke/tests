<?php

declare(strict_types=1);

namespace Tests\Packages\Hub\Unit;

use Helpers\DateTimeHelper;
use Hub\Enums\RepeatInterval;
use Hub\Models\Reminder;
use Hub\Services\Builders\ReminderBuilder;
use Hub\Services\HubManagerService;
use Mockery;
use Tests\Packages\Hub\Support\HubMockHelper;

describe('ReminderBuilder', function () {
    function setupReminderBuilderMocks(): array
    {
        $manager = Mockery::mock(HubManagerService::class);

        return [$manager, new ReminderBuilder($manager)];
    }

    afterEach(function () {
        Mockery::close();
        DateTimeHelper::setTestNow();
    });

    beforeEach(function () {
        $this->bootPackage('Hub', null, true);

        $now = DateTimeHelper::immutable('2026-01-02 12:00:00');
        DateTimeHelper::setTestNow($now);
    });

    describe('fluent API', function () {
        it('sets user correctly', function () {
            [$manager, $builder] = setupReminderBuilderMocks();

            $manager->allows('createReminder')
                ->andReturnUsing(function ($data) {
                    expect($data['user_id'])->toBe(42);

                    return HubMockHelper::mockModel(Reminder::class);
                });

            $builder->for(42)->message('Test')->create();
        });

        it('sets message correctly', function () {
            [$manager, $builder] = setupReminderBuilderMocks();

            $manager->allows('createReminder')
                ->andReturnUsing(function ($data) {
                    expect($data['message'])->toBe('Follow up on proposal');

                    return HubMockHelper::mockModel(Reminder::class);
                });

            $builder->for(1)->message('Follow up on proposal')->create();
        });

        it('sets time via inMinutes()', function () {
            [$manager, $builder] = setupReminderBuilderMocks();
            $now = DateTimeHelper::now();

            $manager->allows('createReminder')
                ->andReturnUsing(function ($data) use ($now) {
                    // Check if it's roughly 30 minutes from "now"
                    expect((int) round(abs($data['remind_at']->diffInMinutes($now, false))))->toBe(30);

                    return HubMockHelper::mockModel(Reminder::class);
                });

            $builder->for(1)->message('Test')->inMinutes(30)->create();
        });

        it('sets time via inHours()', function () {
            [$manager, $builder] = setupReminderBuilderMocks();
            $now = DateTimeHelper::now();

            $manager->allows('createReminder')
                ->andReturnUsing(function ($data) use ($now) {
                    // Check if it's roughly 2 hours from "now"
                    expect((int) round(abs($data['remind_at']->diffInHours($now, false))))->toBe(2);

                    return HubMockHelper::mockModel(Reminder::class);
                });

            $builder->for(1)->message('Test')->inHours(2)->create();
        });

        it('sets tomorrow correctly', function () {
            [$manager, $builder] = setupReminderBuilderMocks();

            $manager->allows('createReminder')
                ->andReturnUsing(function ($data) {
                    expect($data['remind_at']->isTomorrow())->toBeTrue();
                    expect($data['remind_at']->format('H:i'))->toBe('09:00');

                    return HubMockHelper::mockModel(Reminder::class);
                });

            $builder->for(1)->message('Test')->tomorrow()->create();
        });

        it('sets repeat interval via daily()', function () {
            [$manager, $builder] = setupReminderBuilderMocks();

            $manager->allows('createReminder')
                ->andReturnUsing(function ($data) {
                    expect($data['repeat_interval'])->toBe(RepeatInterval::DAILY);

                    return HubMockHelper::mockModel(Reminder::class);
                });

            $builder->for(1)->message('Test')->daily()->create();
        });

        it('sets repeat interval via weekly()', function () {
            [$manager, $builder] = setupReminderBuilderMocks();

            $manager->allows('createReminder')
                ->andReturnUsing(function ($data) {
                    expect($data['repeat_interval'])->toBe(RepeatInterval::WEEKLY);

                    return HubMockHelper::mockModel(Reminder::class);
                });

            $builder->for(1)->message('Test')->weekly()->create();
        });
    });
});
