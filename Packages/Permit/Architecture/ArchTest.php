<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Architecture tests for the Permit package.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

describe('Permit Package Architecture', function () {

    arch('Permit package uses strict types')
        ->expect('Permit')
        ->toUseStrictTypes();

    arch('Permit models extend BaseModel')
        ->expect('Permit\Models')
        ->toExtend('Database\BaseModel');

    arch('Permit services are final or have clear interfaces')
        ->expect('Permit\Services')
        ->classes()
        ->not->toBeAbstract();

    arch('Permit exceptions extend base Exception')
        ->expect('Permit\Exceptions')
        ->toExtend('Exception');

    arch('Permit traits are properly named')
        ->expect('Permit\Traits')
        ->toHaveSuffix('');

    arch('Permit does not use debugging functions')
        ->expect('Permit')
        ->not->toUse(['dd', 'dump', 'var_dump', 'print_r', 'ray']);

    arch('Permit commands extend Symfony Command')
        ->expect('Permit\Commands')
        ->toExtend('Symfony\Component\Console\Command\Command');
});
