<?php

declare(strict_types=1);

use Media\Models\Media;
use Testing\Support\DatabaseTestHelper;

beforeEach(function () {
    DatabaseTestHelper::setupTestEnvironment(['Media'], true);
});

afterEach(function () {
    DatabaseTestHelper::resetDefaultConnection();
});

describe('Media Model', function () {

    test('creates media with required fields', function () {
        $media = Media::create([
            'uuid' => 'test-uuid-123',
            'disk' => 'local',
            'path' => '/media/2026/01',
            'filename' => 'image.jpg',
            'original_filename' => 'original.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);

        expect($media)->toBeInstanceOf(Media::class)
            ->and($media->uuid)->toBe('test-uuid-123')
            ->and($media->mime_type)->toBe('image/jpeg');
    });

    test('isImage returns true for image mime types', function (string $mimeType, bool $expected) {
        $media = new Media();
        $media->mime_type = $mimeType;

        expect($media->isImage())->toBe($expected);
    })->with([
        'jpeg' => ['image/jpeg', true],
        'png' => ['image/png', true],
        'gif' => ['image/gif', true],
        'webp' => ['image/webp', true],
        'pdf' => ['application/pdf', false],
        'video' => ['video/mp4', false],
    ]);

    test('isVideo returns true for video mime types', function (string $mimeType, bool $expected) {
        $media = new Media();
        $media->mime_type = $mimeType;

        expect($media->isVideo())->toBe($expected);
    })->with([
        'mp4' => ['video/mp4', true],
        'webm' => ['video/webm', true],
        'image' => ['image/jpeg', false],
        'audio' => ['audio/mpeg', false],
    ]);

    test('isAudio returns true for audio mime types', function (string $mimeType, bool $expected) {
        $media = new Media();
        $media->mime_type = $mimeType;

        expect($media->isAudio())->toBe($expected);
    })->with([
        'mp3' => ['audio/mpeg', true],
        'wav' => ['audio/wav', true],
        'image' => ['image/jpeg', false],
        'video' => ['video/mp4', false],
    ]);

    test('humanSize returns human readable size', function () {
        $media = new Media();

        $media->size = 1024;
        expect($media->humanSize())->toBe('1.00 KB');

        $media->size = 1048576;
        expect($media->humanSize())->toBe('1.00 MB');

        $media->size = 500;
        expect($media->humanSize())->toBe('500 B');
    });
});
