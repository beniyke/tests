<?php

declare(strict_types=1);

namespace Tests\System\Unit\Helpers\File\Storage\Adapters;

use Helpers\File\Storage\Adapters\S3Adapter;

test('it can instantiate s3 adapter', function () {
    $adapter = new S3Adapter([
        'key' => 'test',
        'secret' => 'test',
        'region' => 'us-east-1',
        'bucket' => 'test-bucket',
    ]);

    expect($adapter)->toBeInstanceOf(S3Adapter::class);
});
