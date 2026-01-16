<?php

declare(strict_types=1);

namespace Tests\Packages\Slot\Helpers;

use Database\BaseModel;

class Patient extends BaseModel
{
    protected string $table = 'user';

    protected array $fillable = ['name', 'email', 'password', 'gender', 'refid', 'status'];
}
