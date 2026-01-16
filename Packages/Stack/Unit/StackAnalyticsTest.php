<?php

declare(strict_types=1);

namespace Tests\Packages\Stack\Unit;

use Database\Connection;
use Database\DB;
use Database\Query\Builder;
use Mockery;
use Stack\Models\Form;
use Stack\Services\StackAnalyticsService;

describe('StackAnalyticsService', function () {
    function setupAnalyticsMocks(): array
    {
        $connection = Mockery::mock(Connection::class);
        DB::setDefaultConnection($connection);

        $analytics = new StackAnalyticsService();

        return [$connection, $analytics];
    }

    afterEach(function () {
        Mockery::close();
        DB::setDefaultConnection(null);
    });

    describe('getConversionMetrics()', function () {
        it('calculates conversion rate correctly', function () {
            [$connection, $analytics] = setupAnalyticsMocks();
            $form = Mockery::mock(Form::class, ['id' => 1]);
            $form->shouldReceive('castAttributeOnSet')->andReturnArg(1);

            $builder = Mockery::mock(Builder::class);
            $connection->shouldReceive('table')->with('stack_event')->andReturn($builder);
            $connection->shouldReceive('table')->with('stack_submission')->andReturn($builder);

            $builder->shouldReceive('where')->with('stack_form_id', 1)->andReturnSelf();
            $builder->shouldReceive('where')->with('event_type', 'view')->andReturnSelf();
            $builder->shouldReceive('count')->once()->andReturn(100); // 100 views

            $builder->shouldReceive('where')->with('stack_form_id', 1)->andReturnSelf();
            $builder->shouldReceive('count')->once()->andReturn(25); // 25 submissions

            $form->id = 1;
            $metrics = $analytics->getConversionMetrics($form);

            expect($metrics['views'])->toBe(100)
                ->and($metrics['submissions'])->toBe(25)
                ->and($metrics['conversion_rate'])->toBe(25.0);
        });

        it('handles zero views gracefully', function () {
            [$connection, $analytics] = setupAnalyticsMocks();
            $form = Mockery::mock(Form::class, ['id' => 1]);
            $form->shouldReceive('castAttributeOnSet')->andReturnArg(1);

            $builder = Mockery::mock(Builder::class);
            $connection->shouldReceive('table')->andReturn($builder);
            $builder->shouldReceive('where')->andReturnSelf();
            $builder->shouldReceive('count')->andReturn(0);

            $form->id = 1;
            $metrics = $analytics->getConversionMetrics($form);

            expect((float) $metrics['conversion_rate'])->toBe(0.0);
        });
    });
});
