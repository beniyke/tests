<?php

declare(strict_types=1);

namespace Tests\System\Fixtures\Models;

use Database\BaseModel;

class PolyUser extends BaseModel
{
    protected string $table = 'test_rel_poly_users';

    protected array $fillable = ['name'];

    public function image()
    {
        return $this->morphOne(PolyImage::class, 'imageable');
    }
}
