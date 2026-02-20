<?php

declare(strict_types=1);

namespace Tests\System\Support\Helpers\Mixins;

class SampleMixin
{
    public function greet()
    {
        return function ($name) {
            return "Hello, {$name}!";
        };
    }

    protected function secret()
    {
        return function () {
            return "Shhh...";
        };
    }
}
