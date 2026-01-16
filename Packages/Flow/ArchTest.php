<?php

declare(strict_types=1);

namespace Tests\Packages\Flow;

use Database\BaseModel;

arch('flow models')
    ->expect('Flow\Models')
    ->toExtend(BaseModel::class);

arch('flow enums')
    ->expect('Flow\Enums')
    ->toBeEnums();

arch('strict types')
    ->expect('Flow')
    ->toUseStrictTypes();
