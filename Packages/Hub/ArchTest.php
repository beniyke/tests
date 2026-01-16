<?php

declare(strict_types=1);

namespace Tests\Packages\Hub;

use Hub\Hub;
use Link\Link;

describe('Hub Package Architecture', function () {
    arch('Hub package does not import external package models')
        ->expect('Hub')
        ->not->toUse([
            'Vault\Models',
            'Media\Models',
            'Support\Models',
        ]);

    arch('Hub models extend BaseModel')
        ->expect('Hub\\Models')
        ->toExtend('Database\\BaseModel');

    arch('Hub events are readonly')
        ->expect('Hub\\Events')
        ->toHaveConstructor();

    arch('Hub files use strict types')
        ->expect('Hub')
        ->toUseStrictTypes();

    arch('No debugging functions in Hub')
        ->expect('Hub')
        ->not->toUse(['dd', 'dump', 'var_dump', 'print_r', 'ray']);

    arch('Hub uses Link via facade only')
        ->expect(Hub::class)
        ->toUse(Link::class);
});
