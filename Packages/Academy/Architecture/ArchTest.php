<?php

declare(strict_types=1);

describe('Academy Architecture', function () {
    arch('Models should extend BaseModel')
        ->expect('Academy\Models')
        ->toExtend('Database\BaseModel');

    arch('Services should be in Services namespace')
        ->expect('Academy\Services')
        ->toBeClasses();

    arch('Enums should be in Enums namespace')
        ->expect('Academy\Enums')
        ->toBeEnums();

    arch('Package Feature tests extend PackageTestCase')
        ->expect('Tests\Packages\Academy\Feature')
        ->toExtend('Tests\PackageTestCase');
});
