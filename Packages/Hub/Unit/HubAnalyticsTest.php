<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Unit tests for Hub HubAnalyticsService.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Tests\Packages\Hub\Unit;

use Database\Connection;
use Database\DB;
use Database\Query\Builder;
use Hub\Services\HubAnalyticsService;
use Mockery;

describe('HubAnalyticsService', function () {
    function setupAnalyticsMocks(): array
    {
        $connection = Mockery::mock(Connection::class);
        DB::setDefaultConnection($connection);

        $analytics = new HubAnalyticsService();

        return [$connection, $analytics];
    }

    afterEach(function () {
        Mockery::close();
        DB::setDefaultConnection(null);
    });

    describe('getEngagementMetrics()', function () {
        it('calculates average messages per thread correctly', function () {
            [$connection, $analytics] = setupAnalyticsMocks();

            $builder = Mockery::mock(Builder::class);
            $builder->allows('setModelClass')->andReturnSelf();
            $connection->shouldReceive('table')->andReturn($builder);

            $builder->shouldReceive('count')->withNoArgs()->andReturn(10, 50, 5); // threads, messages, reminders
            $builder->shouldReceive('distinct')->andReturnSelf();
            $builder->shouldReceive('count')->with('user_id')->andReturn(5); // active users

            $metrics = $analytics->getEngagementMetrics();

            expect($metrics['avg_messages_per_thread'])->toBe(5.0)
                ->and($metrics['total_threads'])->toBe(10)
                ->and($metrics['active_users'])->toBe(5);
        });

        it('handles zero threads gracefully', function () {
            [$connection, $analytics] = setupAnalyticsMocks();

            $builder = Mockery::mock(Builder::class);
            $builder->allows('setModelClass')->andReturnSelf();
            $connection->shouldReceive('table')->andReturn($builder);

            $builder->shouldReceive('count')->withNoArgs()->andReturn(0);
            $builder->shouldReceive('distinct')->andReturnSelf();
            $builder->shouldReceive('count')->with('user_id')->andReturn(0);

            $metrics = $analytics->getEngagementMetrics();

            expect($metrics['avg_messages_per_thread'])->toBe(0.0);
        });
    });

    describe('getReminderMetrics()', function () {
        it('calculates completion rate correctly', function () {
            [$connection, $analytics] = setupAnalyticsMocks();

            $builder = Mockery::mock(Builder::class);
            $builder->allows('setModelClass')->andReturnSelf();
            $connection->shouldReceive('table')->with('hub_reminder')->andReturn($builder);

            $builder->shouldReceive('count')->andReturn(100, 75, 20, 5);
            $builder->shouldReceive('where')->andReturnSelf();

            $metrics = $analytics->getReminderMetrics();

            expect($metrics['completion_rate'])->toBe(75.0)
                ->and($metrics['total'])->toBe(100)
                ->and($metrics['completed'])->toBe(75);
        });
    });
});
