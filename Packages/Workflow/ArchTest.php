<?php

declare(strict_types=1);

namespace Tests\Packages\Workflow;

use Database\BaseModel;

arch('workflow models')
    ->expect('Workflow\Models')
    ->toExtend(BaseModel::class);

arch('strict types')
    ->expect('Workflow')
    ->toUseStrictTypes();
