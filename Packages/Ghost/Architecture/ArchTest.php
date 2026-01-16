<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * User impersonation and session management.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Tests\Packages\Ghost\Architecture;

describe('Ghost Architecture Rules', function () {

    arch('Ghost unit tests do not use database')
        ->expect('Tests\\Packages\\Ghost\\Unit')
        ->not->toUse([
            'assertDatabaseHas',
            'assertDatabaseMissing',
            'assertDatabaseCount',
            'assertModelExists',
            'assertModelMissing',
        ]);

    arch('Ghost unit tests do not touch the container')
        ->expect('Tests\\Packages\\Ghost\\Unit')
        ->not->toUse(['resolve', 'container', 'app']);

    arch('Ghost unit tests do not make HTTP requests')
        ->expect('Tests\\Packages\\Ghost\\Unit')
        ->not->toUse(['get', 'post', 'put', 'patch', 'delete', 'getJson', 'postJson']);

    arch('All Ghost test files declare strict types')
        ->expect('Tests\\Packages\\Ghost')
        ->toUseStrictTypes();

    arch('Ghost test files have Test suffix')
        ->expect('Tests\\Packages\\Ghost')
        ->toHaveSuffix('Test');
});
