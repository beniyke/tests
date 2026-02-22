<?php

declare(strict_types=1);

use Helpers\File\FileSystem;
use Helpers\File\Paths;
use Helpers\File\Storage\Adapters\LocalAdapter;

beforeEach(function () {
    $this->tempDir = Paths::storagePath('testing/streaming_' . uniqid());
    if (!is_dir($this->tempDir)) {
        FileSystem::mkdir($this->tempDir, 0755, true);
    }
    $this->adapter = new LocalAdapter(['root' => $this->tempDir]);
});

afterEach(function () {
    if (is_dir($this->tempDir)) {
        FileSystem::delete($this->tempDir);
    }
});

afterAll(function () {
    $testingDir = Paths::storagePath('testing');
    if (is_dir($testingDir)) {
        FileSystem::delete($testingDir);
    }
});

test('readStream returns resource', function () {
    $content = 'Hello, streaming world!';
    $path = 'test.txt';
    file_put_contents($this->tempDir . '/' . $path, $content);

    $stream = $this->adapter->readStream($path);

    expect($stream)->toBeResource();
    expect(stream_get_contents($stream))->toBe($content);
    fclose($stream);
});

test('readStream with range', function () {
    $content = '0123456789';
    $path = 'range.txt';
    file_put_contents($this->tempDir . '/' . $path, $content);

    // Read from byte 5
    $stream = $this->adapter->readStream($path, ['start' => 5]);

    expect($stream)->toBeResource();
    expect(stream_get_contents($stream))->toBe('56789');
    fclose($stream);
});

test('writeStream from resource', function () {
    $content = 'Stream this content to file.';
    $source = fopen('php://memory', 'r+');
    fwrite($source, $content);
    rewind($source);

    $path = 'written_from_stream.txt';
    $result = $this->adapter->writeStream($path, $source);

    expect($result)->toBeTrue();
    expect($this->tempDir . '/' . $path)->toBeFile();
    expect(file_get_contents($this->tempDir . '/' . $path))->toBe($content);

    fclose($source);
});

test('writeStream large file', function () {
    // Simulate a larger file (1MB)
    $size = 1024 * 1024;
    $source = fopen('php://temp', 'r+');
    $chunk = str_repeat('A', 1024); // 1KB chunk

    for ($i = 0; $i < 1024; $i++) {
        fwrite($source, $chunk);
    }
    rewind($source);

    $path = 'large_file.txt';
    $this->adapter->writeStream($path, $source);

    expect($this->tempDir . '/' . $path)->toBeFile();
    expect(filesize($this->tempDir . '/' . $path))->toBe($size);

    fclose($source);
});
