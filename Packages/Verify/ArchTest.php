<?php

declare(strict_types=1);

namespace Tests\Packages\Verify;

arch('verify contracts')
    ->expect('Verify\Contracts')
    ->toBeInterfaces();

arch('strict types')
    ->expect('Verify')
    ->toUseStrictTypes();
