<?php

declare(strict_types=1);

namespace Tests\Packages\Slot;

use Database\BaseModel;

arch('slot models')
    ->expect('Slot\Models')
    ->toExtend(BaseModel::class);

arch('slot interfaces')
    ->expect('Slot\Interfaces')
    ->toBeInterfaces();

arch('slot enums')
    ->expect('Slot\Enums')
    ->toBeEnums();

arch('strict types')
    ->expect('Slot')
    ->toUseStrictTypes();
