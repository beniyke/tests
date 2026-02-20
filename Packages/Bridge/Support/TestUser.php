<?php

declare(strict_types=1);

namespace Tests\Packages\Bridge\Support;

use Bridge\Traits\HasApiTokens;
use Database\BaseModel;
use Security\Auth\Contracts\Authenticatable;
use Security\Auth\Contracts\Tokenable;

class TestUser extends BaseModel implements Authenticatable, Tokenable
{
    use HasApiTokens;

    protected string $table = 'user';

    public function canAuthenticate(): bool
    {
        return true;
    }

    public function getTokenableId(): int
    {
        return (int) $this->id;
    }

    public function getTokenableType(): string
    {
        return static::class;
    }

    protected array $fillable = ['name', 'email', 'gender', 'refid', 'password'];

    public function getAuthId(): int|string
    {
        return $this->id;
    }

    public function getAuthPassword(): string
    {
        return $this->password ?? '';
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }
}
