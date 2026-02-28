<?php

declare(strict_types=1);

namespace Tests\System\Support\Core\Container;

class TestClassA
{
    public function __construct(public TestContextInterface $dependency)
    {
    }
}
