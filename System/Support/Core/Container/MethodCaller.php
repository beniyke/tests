<?php

declare(strict_types=1);

namespace Tests\System\Support\Core\Container;

class MethodCaller
{
    public function action(MethodDependency $dep, $param)
    {
        return [$dep, $param];
    }
}
