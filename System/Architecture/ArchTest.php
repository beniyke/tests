<?php

declare(strict_types=1);

namespace Tests\System\Architecture;

use Tests\TestCase;

arch('globals')
    ->expect(['dd', 'dump', 'var_dump', 'print_r', 'die', 'exit'])
    ->not->toBeUsed()
    ->ignoring(['Tokit\Tokit', 'Queue\Worker', 'Helpers\Http\Response', 'Helpers\VarDump', 'Core\Console', 'Cli\Helpers\Output']);

arch('naming conventions')
    ->expect('Tests')
    ->toHaveSuffix('Test')
    ->ignoring([
        'Tests\System\Helpers',
        'Tests\Packages\Flow\Helpers',
        'Tests\Packages\Workflow\Helpers',
        'Tests\TestCase',
        'Tests\PackageTestCase',
        'Tests\DatabaseTransactionTestCase',
        'Tests\UnitTestCase',
        'Tests\System\Fixtures',
        'Tests\System\DTOs',
        'Tests\System\Support',
        'Tests\Packages\Bridge\Support',
        'Tests\Packages\Slot\Helpers',
        'Tests\Packages\Hub\Support',
        'Tests\System\Integration\Package\Fixtures'
    ]);

arch('strict types')
    ->expect('Tests')
    ->toUseStrictTypes();

arch('unit isolation')
    ->expect('Tests\Packages\Money\Unit')
    ->not->toUse(['Database', 'Queue', 'Helpers\Http']);

arch('feature inheritance')
    ->expect('Tests\Packages\Wave\Feature')
    ->toExtend(TestCase::class);

arch('services naming convention')
    ->expect([
        'App\Services',
        '*\Services',
    ])
    ->toHaveSuffix('Service')
    ->ignoring([
        'App\Services\Concerns',
        '*\Services\Concerns',
        'App\Services\Contracts',
        '*\Services\Contracts',
        'App\Services\Auth\Interfaces',
        'App\Services\Account\Interfaces',
        'App\Services\System\Interfaces',
    ]);

arch('events naming convention')
    ->expect('*\Events')
    ->toHaveSuffix('Event')
    ->ignoring('*\Events\Concerns');

arch('listeners naming convention')
    ->expect('*\Listeners')
    ->toHaveSuffix('Listener')
    ->ignoring('*\Listeners\Concerns');

arch('notifications naming convention')
    ->expect([
        'App\Notifications',
        '*\Notifications',
    ])
    ->toHaveSuffix('Notification')
    ->ignoring('*\Notifications\Concerns');

arch('package test isolation')
    ->expect('Tests')
    ->not->toBeUsed()
    ->ignoring('Tests');
