<?php

declare(strict_types=1);

namespace Tests\Packages\Link;

describe('Link Package Architecture', function () {
    arch('Link package does not import external package models')
        ->expect('Link')
        ->not->toUse([
            'Vault\\Models',
            'Media\\Models',
            'Support\\Models',
            'Hub\\Models',
        ]);

    arch('Link models extend BaseModel')
        ->expect('Link\\Models')
        ->toExtend('Database\\BaseModel');

    arch('Link exceptions extend Exception')
        ->expect('Link\\Exceptions')
        ->toExtend('Exception');

    arch('Link files use strict types')
        ->expect('Link')
        ->toUseStrictTypes();

    arch('No debugging functions in Link')
        ->expect('Link')
        ->not->toUse(['dd', 'dump', 'var_dump', 'print_r', 'ray']);
});
