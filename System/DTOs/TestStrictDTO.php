<?php

declare(strict_types=1);

namespace Tests\System\DTOs;

use Helpers\DTO;

class TestStrictDTO extends DTO
{
    public ?string $required = null;

    public ?int $count = null;
}
