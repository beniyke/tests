<?php

declare(strict_types=1);

namespace Tests\Packages\Slot\Helpers;

use Database\BaseModel;
use Slot\Traits\HasSlots;

class Doctor extends BaseModel
{
    use HasSlots;

    protected string $table = 'user';

    protected array $fillable = ['name', 'email', 'password', 'gender', 'refid', 'status'];
}
