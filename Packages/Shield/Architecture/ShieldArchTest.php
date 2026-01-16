<?php

declare(strict_types=1);

namespace Tests\Packages\Shield\Architecture;

describe('Shield Architecture Rules', function () {

    arch('Shield does not use debug functions')
        ->expect('Shield')
        ->not->toUse(['dd', 'dump', 'var_dump', 'print_r']);

    arch('Shield services are strictly typed')
        ->expect('Shield\\Services')
        ->toUseStrictTypes();

    arch('Shield drivers implement interface')
        ->expect('Shield\\Drivers')
        ->toImplement('Shield\\Drivers\\CaptchaDriverInterface');
});
