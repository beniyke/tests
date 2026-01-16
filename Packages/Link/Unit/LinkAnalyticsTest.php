<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Unit tests for Link LinkAnalyticsService.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Tests\Packages\Link\Unit;

use Database\Connection;
use Database\DB;
use Database\Query\Builder;
use Link\Services\LinkAnalyticsService;
use Mockery;

describe('LinkAnalyticsService', function () {
    function setupAnalyticsMocks(): array
    {
        $connection = Mockery::mock(Connection::class);
        DB::setDefaultConnection($connection);

        $analytics = new LinkAnalyticsService();

        return [$connection, $analytics];
    }

    afterEach(function () {
        Mockery::close();
        DB::setDefaultConnection(null);
    });

    describe('getExpirationMetrics()', function () {
        it('calculates metrics correctly', function () {
            [$connection, $analytics] = setupAnalyticsMocks();

            $builder = Mockery::mock(Builder::class);
            $connection->shouldReceive('table')->with('link')->andReturn($builder);

            $builder->shouldReceive('count')->once()->andReturn(100); // total

            $builder->shouldReceive('whereNull')->with('revoked_at')->andReturnSelf();
            $builder->shouldReceive('where')->with(Mockery::any())->andReturnSelf();
            $builder->shouldReceive('count')->once()->andReturn(60); // active

            $builder->shouldReceive('whereNotNull')->with('expires_at')->andReturnSelf();
            $builder->shouldReceive('where')->with('expires_at', '<=', Mockery::any())->andReturnSelf();
            $builder->shouldReceive('count')->once()->andReturn(30); // expired

            $builder->shouldReceive('whereNotNull')->with('revoked_at')->andReturnSelf();
            $builder->shouldReceive('count')->once()->andReturn(10); // revoked

            $metrics = $analytics->getExpirationMetrics();

            expect($metrics['total'])->toBe(100)
                ->and($metrics['active'])->toBe(60)
                ->and($metrics['expired'])->toBe(30)
                ->and($metrics['revoked'])->toBe(10)
                ->and($metrics['active_percentage'])->toBe(60.0);
        });

        it('handles zero links gracefully', function () {
            [$connection, $analytics] = setupAnalyticsMocks();

            $builder = Mockery::mock(Builder::class);
            $connection->shouldReceive('table')->with('link')->andReturn($builder);

            $builder->shouldReceive('count')->once()->andReturn(0);
            $builder->shouldReceive('whereNull')->andReturnSelf();
            $builder->shouldReceive('where')->andReturnSelf();
            $builder->shouldReceive('count')->andReturn(0);
            $builder->shouldReceive('whereNotNull')->andReturnSelf();
            $builder->shouldReceive('count')->andReturn(0);

            $metrics = $analytics->getExpirationMetrics();

            expect($metrics['active_percentage'])->toBe(0);
        });
    });

    describe('getScopeDistribution()', function () {
        it('calculates scope frequency correctly', function () {
            [$connection, $analytics] = setupAnalyticsMocks();

            $builder = Mockery::mock(Builder::class);
            $connection->shouldReceive('table')->with('link')->andReturn($builder);

            $builder->shouldReceive('select')->with('scopes')->andReturnSelf();
            $builder->shouldReceive('whereNotNull')->with('scopes')->andReturnSelf();
            $builder->shouldReceive('get')->andReturn([
                (object) ['scopes' => '["view", "download"]'],
                (object) ['scopes' => '["view"]'],
                (object) ['scopes' => '["view", "share"]'],
            ]);

            $distribution = $analytics->getScopeDistribution();

            expect($distribution['view'])->toBe(3)
                ->and($distribution['download'])->toBe(1)
                ->and($distribution['share'])->toBe(1);
        });
    });
});
