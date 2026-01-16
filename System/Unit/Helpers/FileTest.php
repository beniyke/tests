<?php

declare(strict_types=1);

use Helpers\File\FileSystem;
use Helpers\File\Paths;

describe('FileSystem', function () {
    beforeEach(function () {
        $this->testDir = Paths::testPath('storage/file_test_'.uniqid());
        $this->testFile = $this->testDir.'/test.txt';

        if (! FileSystem::isDir($this->testDir)) {
            FileSystem::mkdir($this->testDir, 0777, true);
        }
    });

    afterEach(function () {
        if (FileSystem::exists($this->testFile)) {
            FileSystem::delete($this->testFile);
        }
        if (FileSystem::isDir($this->testDir)) {
            FileSystem::delete($this->testDir);
        }
    });

    test('writes to file', function () {
        $result = FileSystem::put($this->testFile, 'test content');

        expect($result)->toBeTrue();
        expect(FileSystem::exists($this->testFile))->toBeTrue();
    });

    test('reads from file', function () {
        FileSystem::put($this->testFile, 'test content');

        expect(FileSystem::get($this->testFile))->toBe('test content');
    });

    test('checks file exists', function () {
        expect(FileSystem::exists($this->testFile))->toBeFalse();

        FileSystem::put($this->testFile, 'test');
        expect(FileSystem::exists($this->testFile))->toBeTrue();
    });

    test('deletes file', function () {
        FileSystem::put($this->testFile, 'test');

        $result = FileSystem::delete($this->testFile);
        expect($result)->toBeTrue();
        expect(FileSystem::exists($this->testFile))->toBeFalse();
    });

    test('copies file', function () {
        FileSystem::put($this->testFile, 'test');
        $destination = $this->testDir.'/copy.txt';

        $result = FileSystem::copy($this->testFile, $destination);

        expect($result)->toBeTrue();
        expect(file_exists($destination))->toBeTrue();

        unlink($destination);
    });

    test('moves file', function () {
        FileSystem::put($this->testFile, 'test');
        $destination = $this->testDir.'/moved.txt';

        $result = FileSystem::move($this->testFile, $destination);

        expect($result)->toBeTrue();
        expect(file_exists($destination))->toBeTrue();
        expect(file_exists($this->testFile))->toBeFalse();

        unlink($destination);
    });

    test('gets file size', function () {
        FileSystem::put($this->testFile, 'test content');

        expect(FileSystem::size($this->testFile))->toBeGreaterThan(0);
    });

    test('gets file extension', function () {
        expect(FileSystem::extension('/path/to/file.txt'))->toBe('txt');
        expect(FileSystem::extension('/path/to/file.tar.gz'))->toBe('gz');
    });
});

describe('Paths', function () {
    test('joins paths correctly', function () {
        $result = Paths::join('path', 'to', 'file.txt');
        expect($result)->toContain('path');
        expect($result)->toContain('file.txt');
    });

    test('normalizes path', function () {
        $result = Paths::normalize('/path//to///file.txt');
        expect($result)->not->toContain('//');
    });

    test('gets basename', function () {
        expect(Paths::basename('/path/to/file.txt'))->toBe('file.txt');
    });

    test('gets dirname', function () {
        expect(Paths::dirname('/path/to/file.txt'))->toContain('path');
    });
});
