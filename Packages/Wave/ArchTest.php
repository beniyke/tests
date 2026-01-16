<?php

declare(strict_types=1);

namespace Tests\Packages\Wave;

arch('wave models')
    ->expect('Wave\Models')
    ->toExtend('Database\BaseModel');
