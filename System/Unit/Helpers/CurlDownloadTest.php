<?php

declare(strict_types=1);

use Helpers\File\FileSystem;
use Helpers\File\Paths;
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
        $sourceFile = Paths::testPath('storage/curl_test_src_' . uniqid() . '.txt');
        $destFile = Paths::testPath('storage/curl_test_dst_' . uniqid() . '.txt');
        $content = 'Hello World ' . uniqid();
        FileSystem::write($sourceFile, $content);

        $curl = new Curl();

        // Correctly format file:// URL for both Windows and Unix
        $normalizedPath = str_replace('\\', '/', $sourceFile);
        $url = 'file://' . (str_starts_with($normalizedPath, '/') ? '' : '/') . $normalizedPath;

        $result = $curl->download($url, $destFile);

        expect($result)->toBeTrue();
        expect(FileSystem::exists($destFile))->toBeTrue();
        expect(FileSystem::get($destFile))->toBe($content);

        // Cleanup
        FileSystem::delete($sourceFile);
        FileSystem::delete($destFile);
    });

    test('cleanups temp file on 404 or failure', function () {
        $destFile = Paths::testPath('storage/curl_test_fail_' . uniqid() . '.txt');

        $curl = new Curl();
        // Use a non-existent local file to trigger CURL failure
        $url = 'http://' . uniqid() . '.invalid/file.txt';
        $result = $curl->download($url, $destFile);

        expect($result)->toBeFalse();
        expect(FileSystem::exists($destFile))->toBeFalse();

        // Find if any .tmp file was left
        $tmpFiles = glob($destFile . '.tmp.*');
        expect($tmpFiles)->toBeEmpty();
    });
});
