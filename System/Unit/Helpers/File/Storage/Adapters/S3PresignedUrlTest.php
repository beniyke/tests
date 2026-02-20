<?php

declare(strict_types=1);

namespace Tests\System\Unit\Helpers\File\Storage\Adapters;

use Helpers\File\Storage\Adapters\S3Adapter;

test('it generates valid presigned url structure', function () {
    $adapter = new S3Adapter([
        'key' => 'test-key',
        'secret' => 'test-secret',
        'region' => 'us-east-1',
        'bucket' => 'test-bucket',
        'endpoint' => 'https://s3.us-east-1.amazonaws.com'
    ]);

    $url = $adapter->temporaryUrl('folder/test.jpg', time() + 3600);

    // Verify Basic Structure
    expect($url)->toBeString();
    expect($url)->toContain('https://s3.us-east-1.amazonaws.com/test-bucket/folder/test.jpg');

    // Parse Query Params
    $queryString = parse_url($url, PHP_URL_QUERY);
    parse_str($queryString, $params);

    // Verify SigV4 Params
    expect($params)->toHaveKey('X-Amz-Algorithm', 'AWS4-HMAC-SHA256');
    expect($params)->toHaveKey('X-Amz-Credential');
    expect($params)->toHaveKey('X-Amz-Date');
    expect($params)->toHaveKey('X-Amz-Expires');
    expect($params)->toHaveKey('X-Amz-SignedHeaders', 'host');
    expect($params)->toHaveKey('X-Amz-Signature');

    // Check Credential Format
    expect($params['X-Amz-Credential'])->toContain('test-key/');
    expect($params['X-Amz-Credential'])->toContain('/us-east-1/s3/aws4_request');
});
