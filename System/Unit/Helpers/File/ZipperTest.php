<?php

declare(strict_types=1);

use Helpers\File\FileSystem;
use Helpers\File\Paths;
use Helpers\File\Zipper\Zipper;

describe('Zipper', function () {
    beforeEach(function () {
        $this->testDir = Paths::storagePath('zipper_test_'.uniqid());
        FileSystem::mkdir($this->testDir, 0777, true);

        $this->file1 = $this->testDir.'/file1.txt';
        $this->file2 = $this->testDir.'/file2.txt';

        FileSystem::put($this->file1, 'content1');
        FileSystem::put($this->file2, 'content2');

        $this->zipper = new Zipper();
    });

    afterEach(function () {
        FileSystem::delete($this->testDir);
    });

    test('creates zip file', function () {
        $zipPath = $this->testDir;
        $zipName = 'archive.zip';

        $this->zipper->path($zipPath)
            ->add([$this->file1, $this->file2])
            ->save($zipName);

        expect(file_exists($zipPath.'/'.$zipName))->toBeTrue();
    });

    test('extracts zip file', function () {
        $zipPath = $this->testDir;
        $zipName = 'archive.zip';
        $extractPath = $this->testDir.'/extracted';

        // Create zip first
        $this->zipper->path($zipPath)
            ->add([$this->file1, $this->file2])
            ->save($zipName);

        // Extract
        $zipper = new Zipper();
        $zipper->file($zipPath.'/'.$zipName)
            ->path($extractPath)
            ->extract();

        expect(file_exists($extractPath.'/file1.txt'))->toBeTrue();
        expect(file_exists($extractPath.'/file2.txt'))->toBeTrue();
        expect(file_get_contents($extractPath.'/file1.txt'))->toBe('content1');
    });

    test('zipData zips directory', function () {
        $zipFile = $this->testDir.'/data.zip';

        $result = $this->zipper->zipData([$this->testDir], $zipFile);

        expect($result)->toBeTrue();
        expect(file_exists($zipFile))->toBeTrue();
    });

    test('save returns Result object with success', function () {
        $zipPath = $this->testDir;
        $zipName = 'result_test.zip';

        $result = $this->zipper->path($zipPath)
            ->add([$this->file1])
            ->save($zipName);

        expect($result)->toBeInstanceOf(Helpers\File\Zipper\Result::class);
        expect($result->isSuccess())->toBeTrue();
        expect($result->getMessage())->toContain('successfully created');
    });

    test('save returns Result with error when no files added', function () {
        $zipPath = $this->testDir;
        $zipName = 'empty.zip';

        $result = $this->zipper->path($zipPath)->save($zipName);

        expect($result)->toBeInstanceOf(Helpers\File\Zipper\Result::class);
        expect($result->isSuccess())->toBeFalse();
        expect($result->getMessage())->toContain('Cannot find files');
    });

    test('extract returns Result object', function () {
        $zipPath = $this->testDir;
        $zipName = 'extract_result.zip';
        $extractPath = $this->testDir.'/extracted_result';

        // Create zip first
        $this->zipper->path($zipPath)
            ->add([$this->file1])
            ->save($zipName);

        // Extract
        $zipper = new Zipper();
        $result = $zipper->file($zipPath.'/'.$zipName)
            ->path($extractPath)
            ->extract();

        expect($result)->toBeInstanceOf(Helpers\File\Zipper\Result::class);
        expect($result->isSuccess())->toBeTrue();
        expect($result->getMessage())->toContain('extracted successfully');
    });

    test('handles missing files gracefully', function () {
        $zipPath = $this->testDir;
        $zipName = 'missing.zip';

        $result = $this->zipper->path($zipPath)
            ->add([$this->file1, '/nonexistent/file.txt'])
            ->save($zipName);

        expect($result->isSuccess())->toBeTrue();
        expect($result->getMessage())->toContain('Missing files');
    });

    test('zipData handles single file', function () {
        $zipFile = $this->testDir.'/single.zip';

        $result = $this->zipper->zipData([$this->file1], $zipFile);

        expect($result)->toBeTrue();
        expect(file_exists($zipFile))->toBeTrue();
    });

    test('fluent interface works correctly', function () {
        $zipPath = $this->testDir;
        $zipName = 'fluent.zip';

        $result = $this->zipper
            ->path($zipPath)
            ->add([$this->file1, $this->file2])
            ->save($zipName);

        expect($result->isSuccess())->toBeTrue();
        expect(file_exists($zipPath.'/'.$zipName))->toBeTrue();
    });
});
