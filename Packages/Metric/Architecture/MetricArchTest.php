<?php

declare(strict_types=1);

describe('Metric Architecture', function () {
    arch('metric models should only use allowed dependencies')
        ->expect('Metric\\Models')
        ->toOnlyUse([
            'Database\\BaseModel',
            'Database\\Relations',
            'App\\Models\\User',
            'Metric\\Models',
        ]);

    arch('metric services should not directly use models from other packages')
        ->expect('Metric\\Services')
        ->not->toUse([
            'Audit\\Models',
            'Hub\\Models',
            'Slot\\Models',
            'Workflow\\Models',
        ]);

    /*
    arch('metric services should use facades for inter-package communication')
        ->expect('Metric\\Services')
        ->toUse([
            'Audit\\Audit',
            'Hub\\Hub',
            'Slot\\Slot',
            'Workflow\\Workflow',
        ])
        ->ignoring(['Helpers\\*', 'Core\\Exceptions\\*', 'Metric\\Services\\Builders\\*', 'Metric\\Services\\MetricAnalyticsService']);
*/

    arch('metric services should have Service suffix')
        ->expect('Metric\\Services')
        ->toHaveSuffix('Service');
});
