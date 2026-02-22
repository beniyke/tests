<?php

declare(strict_types=1);

namespace Tests\System\Support\Security;

use Security\Auth\Contracts\Authenticatable;

class StubAuthenticatable implements Authenticatable
{
    public function getAuthId(): int|string
    {
        return 1;
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function canAuthenticate(): bool
    {
        return true;
    }
}
