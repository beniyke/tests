<?php

declare(strict_types=1);

namespace Tests\System\Support\Core\Container;

class DefaultValue
{
    public function __construct(public $value = 'default')
    {
    }
}
