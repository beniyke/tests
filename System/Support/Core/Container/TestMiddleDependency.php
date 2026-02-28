<?php

declare(strict_types=1);

namespace Tests\System\Support\Core\Container;

class TestMiddleDependency
{
    public function __construct(public TestDeepDependency $deep)
    {
    }
}
