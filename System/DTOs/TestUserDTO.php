<?php

declare(strict_types=1);

namespace Tests\System\DTOs;

use Helpers\DTO;

class TestUserDTO extends DTO
{
    public string $name;

    public int $age;

    public ?string $email = null;
}
