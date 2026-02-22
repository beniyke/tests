<?php

declare(strict_types=1);

use Helpers\File\FileSystem;
use Helpers\File\Paths;
use Package\Services\HydrationService;

describe('HydrationService Extraction', function () {
    $tempDir = Paths::testPath('temp_hydration_' . uniqid());
    $zipPath = Paths::join($tempDir, 'test.zip');
    $extractPath = Paths::join($tempDir, 'extracted');

    beforeEach(function () use ($tempDir, $extractPath) {
        FileSystem::mkdir($tempDir);
        FileSystem::mkdir($extractPath);
    });

    afterEach(function () use ($tempDir) {
        FileSystem::delete($tempDir);
    });

    test('extract correctly unpacks System and libs directories', function () use ($zipPath, $extractPath) {
        // Create a dummy ZIP
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);
        $zip->addEmptyDir('anchor-master/');
        $zip->addFromString('anchor-master/System/test.txt', 'system content');
        $zip->addFromString('anchor-master/libs/test.txt', 'libs content');
        $zip->addFromString('anchor-master/README.md', 'should not be extracted');
        $zip->close();

        $service = new HydrationService();
        $results = $service->extract($zipPath, $extractPath, ['System', 'libs']);

        expect($results['errors'])->toBeEmpty();
        expect($results['count'])->toBe(2);

        expect(FileSystem::exists($extractPath . DIRECTORY_SEPARATOR . 'System' . DIRECTORY_SEPARATOR . 'test.txt'))->toBeTrue();
        expect(FileSystem::get($extractPath . DIRECTORY_SEPARATOR . 'System' . DIRECTORY_SEPARATOR . 'test.txt'))->toBe('system content');

        expect(FileSystem::exists($extractPath . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR . 'test.txt'))->toBeTrue();
        expect(FileSystem::get($extractPath . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR . 'test.txt'))->toBe('libs content');

        expect(FileSystem::exists($extractPath . DIRECTORY_SEPARATOR . 'README.md'))->toBeFalse();
    });

    test('extract overwrites existing files via atomic replace', function () use ($zipPath, $extractPath) {
        // Pre-create the target file to simulate an existing (potentially locked) file
        $targetDir = $extractPath . DIRECTORY_SEPARATOR . 'System';
        FileSystem::mkdir($targetDir);
        FileSystem::put($targetDir . DIRECTORY_SEPARATOR . 'test.txt', 'old content');

        // Create a ZIP with updated content
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);
        $zip->addEmptyDir('anchor-master/');
        $zip->addFromString('anchor-master/System/test.txt', 'new content');
        $zip->close();

        $service = new HydrationService();
        $results = $service->extract($zipPath, $extractPath, ['System']);

        expect($results['errors'])->toBeEmpty();
        expect($results['count'])->toBe(1);
        expect(FileSystem::get($targetDir . DIRECTORY_SEPARATOR . 'test.txt'))->toBe('new content');
    });
});
