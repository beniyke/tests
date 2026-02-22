<?php

declare(strict_types=1);

namespace Tests\System\Unit\Helpers\File\Storage\Adapters;

use Helpers\File\FileSystem;
use Helpers\File\Paths;
use Helpers\File\Storage\Adapters\LocalAdapter;

beforeEach(function () {
    $this->testRoot = Paths::testPath('storage/anchor_local_test_' . uniqid());
    $this->adapter = new LocalAdapter(['root' => $this->testRoot]);
});

afterEach(function () {
    FileSystem::delete($this->testRoot);
});

afterAll(function () {
    $storageDir = Paths::testPath('storage');
    if (FileSystem::isDir($storageDir) && count(glob($storageDir . DIRECTORY_SEPARATOR . '*')) === 0) {
        FileSystem::delete($storageDir);
    }
});

test('it can store and retrieve files locally', function () {
    $this->adapter->put('test.txt', 'local data');

    expect($this->adapter->exists('test.txt'))->toBeTrue()
        ->and($this->adapter->get('test.txt'))->toBe('local data');
});

test('it creates root directory if not exists', function () {
    expect(FileSystem::isDir($this->testRoot))->toBeTrue();
});

test('it can delete local files', function () {
    $this->adapter->put('delete_me.txt', 'content');
    $this->adapter->delete('delete_me.txt');

    expect($this->adapter->exists('delete_me.txt'))->toBeFalse();
});

test('it can list local files', function () {
    $this->adapter->put('dir/a.txt', 'a');
    $this->adapter->put('dir/b.txt', 'b');

    $files = $this->adapter->files('dir');

    expect($files)->toHaveCount(2)
        ->and($files)->toContain('dir/a.txt', 'dir/b.txt');
});

test('it returns correct url', function () {
    $adapter = new LocalAdapter([
        'root' => $this->testRoot,
        'url' => 'http://localhost/storage'
    ]);

    expect($adapter->url('file.jpg'))->toBe('http://localhost/storage/file.jpg');
});
