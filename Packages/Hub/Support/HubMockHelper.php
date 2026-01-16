<?php

declare(strict_types=1);

namespace Tests\Packages\Hub\Support;

use Mockery;

class HubMockHelper
{
    public static function mockModel(string $class): Mockery\MockInterface
    {
        $mock = Mockery::mock($class);
        $mock->shouldAllowMockingProtectedMethods();
        $mock->allows('castAttributeOnSet')->andReturnUsing(fn ($k, $v) => $v);
        $mock->allows('castAttributeOnGet')->andReturnUsing(fn ($k, $v) => $v);

        return $mock;
    }
}
