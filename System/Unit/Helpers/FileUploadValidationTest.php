<?php

declare(strict_types=1);

namespace Helpers\File;

// Mock functions for testing
function is_uploaded_file(string $filename): bool
{
    return file_exists($filename);
}

function move_uploaded_file(string $from, string $to): bool
{
    return rename($from, $to);
}

namespace Tests\System\Unit\Helpers;

use Helpers\File\FileUploadValidator;
use Helpers\Http\FileHandler;
use Helpers\Validation\Validator;

// Helper to create a dummy file for testing
function createDummyFile(string $content = 'test content', string $extension = 'txt'): string
{
    $filename = sys_get_temp_dir().'/test_file_'.uniqid().'.'.$extension;
    file_put_contents($filename, $content);

    return $filename;
}

// Helper to create a dummy image file
function createDummyImage(): string
{
    $filename = sys_get_temp_dir().'/test_image_'.uniqid().'.png';
    // Create a minimal valid PNG file
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    file_put_contents($filename, $png);

    return $filename;
}

describe('FileUploadValidation', function () {

    afterEach(function () {
        // Clean up any test files in temp dir
        $files = glob(sys_get_temp_dir().'/test_*');
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    });

    describe('Diagnostic', function () {
        test('finfo detects mime type correctly', function () {
            $file = createDummyFile('test content', 'txt');
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file);
            finfo_close($finfo);

            // fwrite(STDERR, "Diagnostic MIME: $mime\n");
            expect($mime)->toBe('text/plain');
        });
    });

    describe('FileUploadValidator', function () {
        test('validates valid file', function () {
            $file = createDummyFile();
            $validator = new FileUploadValidator([], ['txt'], 1024);

            $upload = [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'tmp_name' => $file,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($file),
            ];

            expect($validator->validate($upload))->toBeTrue();
        });

        test('fails on invalid extension', function () {
            $file = createDummyFile();
            $validator = new FileUploadValidator([], ['jpg'], 1024);

            $upload = [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'tmp_name' => $file,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($file),
            ];

            expect(fn () => $validator->validate($upload))->toThrow(\RuntimeException::class);
        });

        test('fails on file size limit', function () {
            $file = createDummyFile(str_repeat('a', 2048)); // 2KB
            $validator = new FileUploadValidator([], [], 1024); // 1KB limit

            $upload = [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'tmp_name' => $file,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($file),
            ];

            expect(fn () => $validator->validate($upload))->toThrow(\RuntimeException::class);
        });

        test('validates mime type', function () {
            $file = createDummyImage();
            $validator = new FileUploadValidator(['image/png'], [], 1024);

            $upload = [
                'name' => 'test.png',
                'type' => 'image/png',
                'tmp_name' => $file,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($file),
            ];

            expect($validator->validate($upload))->toBeTrue();
        });
    });

    describe('FileHandler Integration', function () {
        test('validate() method works', function () {
            $file = createDummyImage();

            $upload = [
                'name' => 'test.png',
                'type' => 'image/png',
                'tmp_name' => $file,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($file),
            ];

            $handler = new FileHandler($upload);

            // Valid case
            expect($handler->validate(['type' => 'image']))->toBeTrue();

            // Invalid case (wrong type)
            expect($handler->validate(['type' => 'document']))->toBeFalse();
            expect($handler->getValidationError())->not->toBeNull();
        });

        test('validateWith() method works', function () {
            $file = createDummyFile();
            $validator = new FileUploadValidator([], ['txt']);

            $upload = [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'tmp_name' => $file,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($file),
            ];

            $handler = new FileHandler($upload);

            expect($handler->validateWith($validator))->toBeTrue();
        });

        test('moveSecurely() moves file', function () {
            $file = createDummyFile();
            $destDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'uploads';
            if (! is_dir($destDir)) {
                mkdir($destDir);
            }

            $upload = [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'tmp_name' => $file,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($file),
            ];

            $handler = new FileHandler($upload);

            $path = $handler->moveSecurely($destDir.DIRECTORY_SEPARATOR.'test.txt', ['extensions' => ['txt'], 'generateSafeName' => false], false);

            expect(file_exists($path))->toBeTrue();
            expect($path)->toBe($destDir.DIRECTORY_SEPARATOR.'test.txt');

            // Cleanup
            @unlink($path);
            @rmdir($destDir);
        });
    });

    describe('Human-Readable File Size Support', function () {
        test('accepts human-readable maxSize formats', function () {
            $file = createDummyFile(str_repeat('a', 1024)); // 1KB file

            $upload = [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'tmp_name' => $file,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($file),
            ];

            $handler = new FileHandler($upload);

            // Should pass with '2kb' limit
            expect($handler->validate(['maxSize' => '2kb']))->toBeTrue();

            // Should pass with '1mb' limit
            expect($handler->validate(['maxSize' => '1mb']))->toBeTrue();
        });

        test('rejects files exceeding human-readable maxSize', function () {
            $file = createDummyFile(str_repeat('a', 2048)); // 2KB file

            $upload = [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'tmp_name' => $file,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($file),
            ];

            $handler = new FileHandler($upload);

            // Should fail with '1kb' limit
            expect($handler->validate(['maxSize' => '1kb']))->toBeFalse();
            expect($handler->getValidationError())->toContain('size');
        });

        test('works with image type and human-readable size', function () {
            $file = createDummyImage(); // Small PNG

            $upload = [
                'name' => 'test.png',
                'type' => 'image/png',
                'tmp_name' => $file,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($file),
            ];

            $handler = new FileHandler($upload);

            expect($handler->validate([
                'type' => 'image',
                'maxSize' => '1mb',
            ]))->toBeTrue();
        });

        test('maintains backward compatibility with numeric bytes', function () {
            $file = createDummyFile(str_repeat('a', 1024)); // 1KB file

            $upload = [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'tmp_name' => $file,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($file),
            ];

            $handler = new FileHandler($upload);

            // Should still work with numeric bytes
            expect($handler->validate(['maxSize' => 2048]))->toBeTrue();
            expect($handler->validate(['maxSize' => 512]))->toBeFalse();
        });

        test('supports various size units', function () {
            $file = createDummyFile(str_repeat('a', 512)); // 512 bytes

            $upload = [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'tmp_name' => $file,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($file),
            ];

            $handler = new FileHandler($upload);

            expect($handler->validate(['maxSize' => '1kb']))->toBeTrue();
            expect($handler->validate(['maxSize' => '1KB']))->toBeTrue();
            expect($handler->validate(['maxSize' => '1mb']))->toBeTrue();
            expect($handler->validate(['maxSize' => '1MB']))->toBeTrue();
        });
    });

    describe('ValidationTrait Integration', function () {
        test('secure_file rule works in Validator', function () {
            $file = createDummyImage();

            $upload = [
                'name' => 'test.png',
                'type' => 'image/png',
                'tmp_name' => $file,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($file),
            ];

            $handler = new FileHandler($upload);

            $validator = new Validator();
            $validator->rules([
                'avatar' => [
                    'secure_file' => [
                        'type' => 'image',
                    ],
                ],
            ])->validate(['avatar' => $handler]);

            expect($validator->has_error())->toBeFalse();
        });

        test('secure_file rule fails on invalid file', function () {
            $file = createDummyFile(); // Text file

            $upload = [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'tmp_name' => $file,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($file),
            ];

            $handler = new FileHandler($upload);

            $validator = new Validator();
            $validator->rules([
                'avatar' => [
                    'secure_file' => [
                        'type' => 'image', // Expecting image
                    ],
                ],
            ])->parameters(['avatar' => 'Avatar'])
                ->validate(['avatar' => $handler]);

            expect($validator->has_error())->toBeTrue();
            expect($validator->errors()['avatar'][0])->toContain('MIME type');
        });
    });

    describe('Helper Functions', function () {
        test('validate_upload helper works', function () {
            $file = createDummyImage();

            $upload = [
                'name' => 'test.png',
                'type' => 'image/png',
                'tmp_name' => $file,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($file),
            ];

            expect(validate_upload($upload, ['type' => 'image']))->toBeTrue();
            expect(validate_upload($upload, ['type' => 'document']))->toBeFalse();
        });

        test('upload_image helper works', function () {
            $file = createDummyImage();
            $destDir = sys_get_temp_dir().'/uploads_helper';
            if (! is_dir($destDir)) {
                mkdir($destDir);
            }

            $upload = [
                'name' => 'test.png',
                'type' => 'image/png',
                'tmp_name' => $file,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($file),
            ];

            // We need to move the file back to a temp location because upload_image will move it
            // But createDummyImage already created it.
            // Actually upload_image calls moveUploadedFile which calls move_uploaded_file (mocked to rename)
            // So it should work fine.

            $path = upload_image($upload, $destDir.'/image.png');

            expect(file_exists($path))->toBeTrue();

            // Cleanup
            @unlink($path);
            @rmdir($destDir);
        });
    });
});
