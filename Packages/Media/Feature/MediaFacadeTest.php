<?php

declare(strict_types=1);

use Core\Services\ConfigServiceInterface;
use Helpers\File\FileSystem;
use Helpers\File\Paths;
use Media\Exceptions\FileSizeExceededException;
use Media\Exceptions\FileTypeNotAllowedException;
use Media\Media;
use Media\Models\Media as MediaModel;
use Testing\Support\DatabaseTestHelper;

beforeEach(function () {
    DatabaseTestHelper::setupTestEnvironment(['Media'], true);
});

afterEach(function () {
    DatabaseTestHelper::resetDefaultConnection();
});

describe('Media Facade', function () {

    test('url generates correct storage url', function () {
        resolve(ConfigServiceInterface::class)->set('media.path', 'media');

        $media = MediaModel::create([
            'uuid' => 'test-uuid',
            'disk' => 'local',
            'path' => '2026/01',
            'filename' => 'test.jpg',
            'original_filename' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);

        $url = Media::url($media);
        expect($url)->toBe('/storage/media/2026/01/test.jpg');
    });

    test('getPath returns correct absolute path', function () {
        $media = MediaModel::create([
            'uuid' => 'test-uuid',
            'disk' => 'local',
            'path' => '2026/01',
            'filename' => 'test.jpg',
            'original_filename' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);

        $path = Media::getPath($media);
        expect($path)->toContain('storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . '2026/01' . DIRECTORY_SEPARATOR . 'test.jpg');
    });

    test('upload throws FileSizeExceededException if file too large', function () {
        resolve(ConfigServiceInterface::class)->set('media.max_file_size', 10); // 10 bytes

        $tmpFile = Paths::testPath('storage/test_' . uniqid());
        FileSystem::write($tmpFile, str_repeat('A', 20));

        try {
            Media::upload($tmpFile);
        } catch (FileSizeExceededException $e) {
            expect($e->getMessage())->toBe('File size exceeds maximum allowed.');

            return;
        } finally {
            FileSystem::delete($tmpFile);
        }

        $this->fail('Expected FileSizeExceededException was not thrown.');
    });

    test('upload throws FileTypeNotAllowedException if mime type not allowed', function () {
        resolve(ConfigServiceInterface::class)->set('media.allowed_types', ['image/jpeg']);

        $tmpFile = Paths::testPath('storage/test_' . uniqid());
        FileSystem::write($tmpFile, 'not a jpeg');

        try {
            Media::upload($tmpFile);
        } catch (FileTypeNotAllowedException $e) {
            expect($e->getMessage())->toBe('File type not allowed.');

            return;
        } finally {
            FileSystem::delete($tmpFile);
        }

        $this->fail('Expected FileTypeNotAllowedException was not thrown.');
    });
});
