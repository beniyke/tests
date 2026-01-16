<?php

declare(strict_types=1);

namespace Tests\System\DTOs;

use Helpers\DTO;

class TestProductDTO extends DTO
{
    public readonly string $id;

    public string $title;

    public float $price;

    public bool $active = true;
}
