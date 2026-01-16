<?php

declare(strict_types=1);

namespace Tests\Packages\Pay;

use Database\BaseModel;

arch('pay models')
    ->expect('Pay\Models')
    ->toExtend(BaseModel::class);

arch('pay contracts')
    ->expect('Pay\Contracts')
    ->toBeInterfaces();

arch('pay enums')
    ->expect('Pay\Enums')
    ->toBeEnums();

arch('strict types')
    ->expect('Pay')
    ->toUseStrictTypes();
