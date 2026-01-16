<?php

declare(strict_types=1);

namespace Tests\System\Fixtures\Models;

use Database\BaseModel;

class PolyImage extends BaseModel
{
    protected string $table = 'test_rel_poly_images';

    protected array $fillable = ['url', 'imageable_id', 'imageable_type'];

    public function imageable()
    {
        return $this->morphTo('imageable');
    }
}
