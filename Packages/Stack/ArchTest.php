<?php

declare(strict_types=1);

namespace Tests\Packages\Stack;

describe('Stack Package Architecture', function () {
    arch('Stack package does not import external package models')
        ->expect('Stack')
        ->not->toUse([
            'Vault\\Models',
            'Media\\Models',
            'Support\\Models',
            'Hub\\Models',
            'Link\\Models',
            'Audit\\Models',
        ]);

    arch('Stack models extend BaseModel')
        ->expect('Stack\\Models')
        ->toExtend('Database\\BaseModel');

    arch('Stack files use strict types')
        ->expect('Stack')
        ->toUseStrictTypes();

    arch('No debugging functions in Stack')
        ->expect('Stack')
        ->not->toUse(['dd', 'dump', 'var_dump', 'print_r', 'ray']);
});
