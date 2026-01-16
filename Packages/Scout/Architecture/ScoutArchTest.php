<?php

declare(strict_types=1);

test('Scout must not import models from other packages')
    ->expect('Scout')
    ->not->toUse([
        'Audit\Models',
        'Link\Models',
        'Slot\Models',
        'Media\Models',
    ]);
