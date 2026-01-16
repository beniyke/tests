<?php

declare(strict_types=1);

namespace Tests\Packages\Money;

arch('money contracts')
    ->expect('Money\Contracts')
    ->toBeInterfaces();

arch('strict types')
    ->expect('Money')
    ->toUseStrictTypes();
