<?php

declare(strict_types=1);

use Helpers\Http\Client\Curl;

describe('Curl Download', function () {
    test('validates URL', function () {
        $curl = new Curl();
        $result = $curl->download('invalid-url', 'path/to/dest');
        expect($result)->toBeFalse();
    });

    test('validates destination directory', function () {
        $curl = new Curl();
        // Use a path that is unlikely to exist on any OS
        $path = DIRECTORY_SEPARATOR . 'non_existent_folder_' . uniqid() . DIRECTORY_SEPARATOR . 'file.txt';
        $result = $curl->download('https://google.com', $path);
        expect($result)->toBeFalse();
    });

    test('successfully downloads a file', function () {
        $sourceFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'curl_test_src_' . uniqid() . '.txt';
        $destFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'curl_test_dst_' . uniqid() . '.txt';
        $content = 'Hello World ' . uniqid();
        file_put_contents($sourceFile, $content);

        $curl = new Curl();

        // Correctly format file:// URL for both Windows and Unix
        $normalizedPath = str_replace('\\', '/', $sourceFile);
        $url = 'file://' . (str_starts_with($normalizedPath, '/') ? '' : '/') . $normalizedPath;

        $result = $curl->download($url, $destFile);

        expect($result)->toBeTrue();
        expect(file_exists($destFile))->toBeTrue();
        expect(file_get_contents($destFile))->toBe($content);

        // Cleanup
        @unlink($sourceFile);
        @unlink($destFile);
    });

    test('cleanups temp file on 404 or failure', function () {
        $destFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'curl_test_fail_' . uniqid() . '.txt';

        $curl = new Curl();
        // Use a non-existent local file to trigger CURL failure
        $url = 'http://' . uniqid() . '.invalid/file.txt';
        $result = $curl->download($url, $destFile);

        expect($result)->toBeFalse();
        expect(file_exists($destFile))->toBeFalse();

        // Find if any .tmp file was left
        $tmpFiles = glob($destFile . '.tmp.*');
        expect($tmpFiles)->toBeEmpty();
    });
});
