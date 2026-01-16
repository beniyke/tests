<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Architecture tests for the Audit package.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

describe('Audit Package Architecture', function () {

    arch('Audit package uses strict types')
        ->expect('Audit')
        ->toUseStrictTypes();

    arch('Audit models extend BaseModel')
        ->expect('Audit\Models')
        ->toExtend('Database\BaseModel');

    arch('Audit does not use debugging functions')
        ->expect('Audit')
        ->not->toUse(['dd', 'dump', 'var_dump', 'print_r', 'ray']);

    arch('Audit commands extend Symfony Command')
        ->expect('Audit\Commands')
        ->toExtend('Symfony\Component\Console\Command\Command');
});
