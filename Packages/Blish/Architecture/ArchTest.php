<?php

declare(strict_types=1);

namespace Tests\Packages\Blish\Architecture;

describe('Blish Architecture', function () {
    arch('it should use strict types')
        ->expect('Blish')
        ->toUseStrictTypes();

    arch('it should not use forbidden functions')
        ->expect('Blish')
        ->not->toUse(['dd', 'dump', 'var_dump', 'die', 'exit']);

    arch('it should only use its own models')
        ->expect('Blish\Services')
        ->toOnlyUse([
            'Blish\Models',
            'Blish\Services',
            'Blish\Notifications',
            'Blish\Events',
            'Helpers',
            'System',
            'Core',
            'Mail',
            'Verify',
            'Link',
            'Audit',
            'Database',
            'resolve',
            'url',
            'config',
        ]);

    arch('it should not use FQCN in method bodies')
        ->expect('Blish')
        ->not->toUse([
            '\\Blish\\Models\\',
            '\\Blish\\Services\\',
        ]);
});
