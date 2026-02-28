<?php

declare(strict_types=1);

namespace Tests\System\Support\Core\Container;

class TestTopLevel
{
    public function __construct(public TestMiddleDependency $middle)
    {
    }
}
