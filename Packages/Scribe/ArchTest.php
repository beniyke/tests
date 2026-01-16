<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Architecture tests for the Scribe package.
 */

test('Scribe models must extend BaseModel')
    ->expect('Scribe\Models')
    ->toExtend('Database\BaseModel');

test('Scribe must not leak models to other packages')
    ->expect('Scribe\Models')
    ->toOnlyBeUsedIn('Scribe');

test('Scribe must not import models from other packages')
    ->expect('Scribe')
    ->not->toUse([
        'Link\Models',
        'Stack\Models',
        'Pay\Models',
        'Support\Models',
        'Audit\Models',
        'Media\Models',
    ]);

test('Scribe must use strict types')
    ->expect('Scribe')
    ->toUseStrictTypes();

test('Scribe services must be strictly typed')
    ->expect('Scribe\Services')
    ->toBeClasses()
    ->toUseStrictTypes();

test('Scribe must not use debugging functions')
    ->expect('Scribe')
    ->not->toUse(['dd', 'dump', 'die', 'var_dump', 'print_r']);
