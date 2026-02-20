<?php

declare(strict_types=1);

use Helpers\File\Storage\Adapters\AzureBlobAdapter;

it('can instantiate azure blob adapter', function () {
    $adapter = new AzureBlobAdapter(['name' => 'test', 'key' => base64_encode('test_key')]);
    expect($adapter)->toBeInstanceOf(AzureBlobAdapter::class);
});
