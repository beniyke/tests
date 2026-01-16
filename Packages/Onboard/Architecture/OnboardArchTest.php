<?php

declare(strict_types=1);

describe('Onboard Architecture', function () {
    arch('onboard models should only use allowed dependencies')
        ->expect('Onboard\\Models')
        ->toOnlyUse([
            'Database\\BaseModel',
            'Database\\Relations',
            'App\\Models\\User',
            'Onboard\\Models',
            'Helpers\\DateTimeHelper',
        ]);

    arch('onboard services should not directly use models from other packages')
        ->expect('Onboard\\Services')
        ->not->toUse([
            'Audit\\Models\\AuditLog',
            'Metric\\Models\\Metric',
            'Media\\Models\\Media',
            'Flow\\Models\\Project',
        ]);
});
