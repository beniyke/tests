<?php

declare(strict_types=1);

namespace Tests\System\Support\Core\Container;

class TestDependent
{
    public function __construct(public TestDependency $dependency)
    {
    }
}
