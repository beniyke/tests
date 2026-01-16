<?php

declare(strict_types=1);

namespace Tests\Packages\Tenancy;

use Database\BaseModel;

arch('tenancy models')
    ->expect('Tenancy\Models')
    ->toExtend(BaseModel::class);

arch('strict types')
    ->expect('Tenancy')
    ->toUseStrictTypes();
