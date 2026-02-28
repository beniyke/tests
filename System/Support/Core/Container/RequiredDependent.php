<?php

declare(strict_types=1);

namespace Tests\System\Support\Core\Container;

class RequiredDependent
{
    public function __construct(public RequiredInterface $req)
    {
    }
}
