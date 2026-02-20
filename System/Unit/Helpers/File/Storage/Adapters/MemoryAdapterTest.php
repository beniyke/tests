<?php

declare(strict_types=1);

namespace Tests\System\Unit\Helpers\File\Storage\Adapters;

use Helpers\File\Storage\Adapters\MemoryAdapter;

beforeEach(function () {
    $this->adapter = new MemoryAdapter();
});

test('it can store and retrieve files', function () {
    $this->adapter->put('test.txt', 'hello world');

    expect($this->adapter->exists('test.txt'))->toBeTrue()
        ->and($this->adapter->get('test.txt'))->toBe('hello world');
});

test('it can delete files', function () {
    $this->adapter->put('test.txt', 'hello world');
    $this->adapter->delete('test.txt');

    expect($this->adapter->exists('test.txt'))->toBeFalse();
});

test('it can copy files', function () {
    $this->adapter->put('source.txt', 'data');
    $this->adapter->copy('source.txt', 'dest.txt');

    expect($this->adapter->exists('dest.txt'))->toBeTrue()
        ->and($this->adapter->get('dest.txt'))->toBe('data');
});

test('it can move files', function () {
    $this->adapter->put('source.txt', 'data');
    $this->adapter->move('source.txt', 'dest.txt');

    expect($this->adapter->exists('dest.txt'))->toBeTrue()
        ->and($this->adapter->exists('source.txt'))->toBeFalse();
});

test('it can list files', function () {
    $this->adapter->put('dir/file1.txt', '1');
    $this->adapter->put('dir/file2.txt', '2');
    $this->adapter->put('other.txt', '3');

    $files = $this->adapter->files('dir');

    expect($files)->toHaveCount(2)
        ->and($files)->toContain('dir/file1.txt', 'dir/file2.txt');
});

test('it can create and delete directories', function () {
    $this->adapter->makeDirectory('nested/dir');
    $this->adapter->put('nested/dir/file.txt', 'data');

    expect($this->adapter->files('nested/dir'))->toHaveCount(1);

    $this->adapter->deleteDirectory('nested');

    expect($this->adapter->exists('nested/dir/file.txt'))->toBeFalse();
});
