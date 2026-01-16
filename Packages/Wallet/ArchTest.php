<?php

declare(strict_types=1);

namespace Tests\Packages\Wallet;

use Database\BaseModel;

arch('wallet models')
    ->expect('Wallet\Models')
    ->toExtend(BaseModel::class);

arch('wallet contracts')
    ->expect('Wallet\Contracts')
    ->toBeInterfaces();

arch('wallet enums')
    ->expect('Wallet\Enums')
    ->toBeEnums();

arch('strict types')
    ->expect('Wallet')
    ->toUseStrictTypes();
