<?php

declare(strict_types=1);

namespace Tests\System\Support\Core\Container;

class OptionalDependent
{
    public function __construct(public ?OptionalInterface $opt = null)
    {
    }
}
