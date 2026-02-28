<?php

declare(strict_types=1);

namespace Tests\System\Support\Core\Container;

class MakeWithParams
{
    public function __construct(public $a, public $b)
    {
    }
}
