<?php

declare(strict_types=1);

namespace Tests\System\Support\Core\Container;

class Unresolvable
{
    public function __construct(public $unknown)
    {
    }
}
