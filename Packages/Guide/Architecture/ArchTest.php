<?php

declare(strict_types=1);

namespace Tests\Packages\Guide\Architecture;

describe('Guide Architecture Rules', function () {

    arch('Guide models extend BaseModel')
        ->expect('Guide\Models')
        ->toExtend('Database\BaseModel');

    arch('Guide services are strictly typed')
        ->expect('Guide\Services')
        ->toUseStrictTypes();

    arch('Guide does not directly use other package models')
        ->expect('Guide')
        ->not->toUse([
            'Audit\Models',
            'Media\Models',
            'Stack\Models',
            'Blish\Models',
        ]);
});
