<?php

declare(strict_types=1);

namespace Tests\System\Unit\Helpers\File\Storage\Adapters;

use Helpers\File\FileSystem;
use Helpers\File\Paths;
use Helpers\File\Storage\Adapters\ZipAdapter;
use RuntimeException;
use ZipArchive;

beforeEach(function () {
    $this->zipPath = Paths::testPath('storage/anchor_test_' . uniqid() . '.zip');
    $this->adapter = new ZipAdapter(['path' => $this->zipPath]);
});

afterEach(function () {
    if (FileSystem::exists($this->zipPath)) {
        FileSystem::delete($this->zipPath);
    }
});

test('it throws exception if path is missing', function () {
    new ZipAdapter([]);
})->throws(RuntimeException::class);

test('it can store and retrieve files in zip', function () {
    $this->adapter->put('test.txt', 'zip content');

    expect($this->adapter->exists('test.txt'))->toBeTrue()
        ->and($this->adapter->get('test.txt'))->toBe('zip content');
});

test('it can delete files from zip', function () {
    $this->adapter->put('test.txt', 'content');
    $this->adapter->delete('test.txt');

    expect($this->adapter->exists('test.txt'))->toBeFalse();
});

test('it can list files in zip', function () {
    $this->adapter->put('dir/file1.txt', '1');
    $this->adapter->put('dir/file2.txt', '2');
    $this->adapter->put('other.txt', '3');

    $files = $this->adapter->files('dir');

    expect($files)->toHaveCount(2)
        ->and($files)->toContain('dir/file1.txt', 'dir/file2.txt');
});

test('it creates empty directories', function () {
    $this->adapter->makeDirectory('new_dir');

    // ZipArchive usually lists directories as entries ending with /
    $zip = new ZipArchive();
    $zip->open($this->zipPath);
    $stat = $zip->statName('new_dir/');
    $zip->close();

    expect($stat)->not->toBeFalse();
});
