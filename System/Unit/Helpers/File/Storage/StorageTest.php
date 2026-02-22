<?php

declare(strict_types=1);

namespace Tests\System\Unit\Helpers\File\Storage;

use Core\Services\ConfigServiceInterface;
use Helpers\File\FileSystem;
use Helpers\File\Paths;
use Helpers\File\Storage\Adapters\LocalAdapter;
use Helpers\File\Storage\Adapters\S3Adapter;
use Helpers\File\Storage\StorageInterface;
use Helpers\File\Storage\StorageManager;
use InvalidArgumentException;
use Mockery;

beforeEach(function () {
    $this->config = Mockery::mock(ConfigServiceInterface::class);
    $this->manager = new StorageManager($this->config);
});

afterEach(function () {
    Mockery::close();
});

afterAll(function () {
    $storageDir = Paths::testPath('storage');
    // Only clean up dirs created by this test file
    foreach (['anchor_storage_test', 'anchor_storage_local', 'anchor_storage_cache'] as $dir) {
        $path = $storageDir . DIRECTORY_SEPARATOR . $dir;
        if (FileSystem::isDir($path)) {
            FileSystem::delete($path);
        }
    }
    // Remove parent if empty
    if (FileSystem::isDir($storageDir) && count(glob($storageDir . DIRECTORY_SEPARATOR . '*')) === 0) {
        FileSystem::delete($storageDir);
    }
});

test('it resolves local disk by default', function () {
    $this->config->shouldReceive('get')
        ->with('filesystems.default', 'local')
        ->andReturn('local');

    $this->config->shouldReceive('get')
        ->with('filesystems.disks.local')
        ->andReturn([
            'driver' => 'local',
            'root' => Paths::testPath('storage/anchor_storage_test'),
        ]);

    $disk = $this->manager->disk();

    expect($disk)->toBeInstanceOf(LocalAdapter::class)
        ->and($disk)->toBeInstanceOf(StorageInterface::class);
});

test('it can resolve multiple disks', function () {
    $this->config->shouldReceive('get')
        ->with('filesystems.disks.local')
        ->andReturn([
            'driver' => 'local',
            'root' => Paths::testPath('storage/anchor_storage_local'),
        ]);

    $this->config->shouldReceive('get')
        ->with('filesystems.disks.s3')
        ->andReturn([
            'driver' => 's3',
            'bucket' => 'test-bucket',
        ]);

    $localDisk = $this->manager->disk('local');
    $s3Disk = $this->manager->disk('s3');

    expect($localDisk)->toBeInstanceOf(LocalAdapter::class)
        ->and($s3Disk)->toBeInstanceOf(S3Adapter::class);
});

test('it caches resolved disks', function () {
    $this->config->shouldReceive('get')
        ->with('filesystems.disks.local')
        ->once()
        ->andReturn([
            'driver' => 'local',
            'root' => Paths::testPath('storage/anchor_storage_cache'),
        ]);

    $disk1 = $this->manager->disk('local');
    $disk2 = $this->manager->disk('local');

    expect($disk1)->toBe($disk2);
});

test('it throws exception for undefined disk', function () {
    $this->config->shouldReceive('get')
        ->with('filesystems.disks.invalid')
        ->andReturn(null);

    $this->manager->disk('invalid');
})->throws(InvalidArgumentException::class, 'Disk [invalid] is not defined.');

test('it can be extended with custom drivers', function () {
    $this->manager->extend('custom', function ($config, $diskConfig) {
        return Mockery::mock(StorageInterface::class);
    });

    $this->config->shouldReceive('get')
        ->with('filesystems.disks.my-custom')
        ->andReturn([
            'driver' => 'custom',
        ]);

    $disk = $this->manager->disk('my-custom');

    expect($disk)->toBeInstanceOf(StorageInterface::class);
});
