<?php

declare(strict_types=1);

namespace Tests\Packages\Bridge\Support;

use Bridge\Contracts\TokenableInterface;
use Bridge\Traits\HasApiTokens;
use Database\BaseModel;

class TestUser extends BaseModel implements TokenableInterface
{
    use HasApiTokens;

    protected string $table = 'user';

    protected array $fillable = ['name', 'email', 'gender', 'refid', 'password'];
}
