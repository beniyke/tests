<?php

declare(strict_types=1);

namespace Tests\Packages\Bridge;

use Database\BaseModel;

arch('bridge models')
    ->expect('Bridge\Models')
    ->toExtend(BaseModel::class);

arch('bridge contracts')
    ->expect('Bridge\Contracts')
    ->toBeInterfaces();

arch('strict types')
    ->expect('Bridge')
    ->toUseStrictTypes();
