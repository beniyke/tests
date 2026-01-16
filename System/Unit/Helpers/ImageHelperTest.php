<?php

declare(strict_types=1);

use Helpers\File\FileSystem;
use Helpers\File\ImageHelper;
use Helpers\File\Paths;

// Helper function to create a minimal test image
function createTestImage(string $path): void
{
    // Create a 100x100 red image
    $img = imagecreatetruecolor(100, 100);
    $red = imagecolorallocate($img, 255, 0, 0);
    imagefill($img, 0, 0, $red);
    imagepng($img, $path);
    imagedestroy($img);
}

describe('ImageHelper', function () {
    beforeEach(function () {
        $this->testDir = Paths::storagePath('test_images_'.uniqid());
        FileSystem::mkdir($this->testDir, 0777, true);

        // Create a simple test image (100x100 red pixel PNG)
        $this->testImage = $this->testDir.'/test.png';
        createTestImage($this->testImage);

        $this->helper = new ImageHelper();
    });

    afterEach(function () {
        // Clean up test directory
        if (FileSystem::exists($this->testDir)) {
            FileSystem::delete($this->testDir);
        }
    });

    test('image loads file successfully', function () {
        $result = $this->helper->image($this->testImage);

        expect($result)->toBeInstanceOf(ImageHelper::class);
    });

    test('image throws exception for non-existent file', function () {
        expect(fn () => $this->helper->image('/nonexistent/file.png'))
            ->toThrow(Exception::class, 'does not exist');
    });

    test('imageWidth returns correct width', function () {
        $this->helper->image($this->testImage);

        expect($this->helper->imageWidth())->toBe(100);
    });

    test('imageHeight returns correct height', function () {
        $this->helper->image($this->testImage);

        expect($this->helper->imageHeight())->toBe(100);
    });

    test('width sets target width', function () {
        $result = $this->helper->width(50);

        expect($result)->toBeInstanceOf(ImageHelper::class);
    });

    test('height sets target height', function () {
        $result = $this->helper->height(50);

        expect($result)->toBeInstanceOf(ImageHelper::class);
    });

    test('resize resizes image', function () {
        $this->helper->image($this->testImage)
            ->width(50)
            ->height(50)
            ->resize();

        expect($this->helper->imageWidth())->toBe(50);
        expect($this->helper->imageHeight())->toBe(50);
    });

    test('fit maintains aspect ratio', function () {
        $this->helper->image($this->testImage)
            ->width(50)
            ->height(50)
            ->fit();

        expect($this->helper->imageWidth())->toBe(50);
        expect($this->helper->imageHeight())->toBe(50);
    });

    test('crop crops image', function () {
        $this->helper->image($this->testImage)
            ->width(50)
            ->height(50)
            ->crop('center');

        expect($this->helper->imageWidth())->toBe(50);
        expect($this->helper->imageHeight())->toBe(50);
    });

    test('rotate rotates image', function () {
        $result = $this->helper->image($this->testImage)->rotate('90');

        expect($result)->toBeInstanceOf(ImageHelper::class);
    });

    test('flip flips image horizontally', function () {
        $result = $this->helper->image($this->testImage)->flip('h');

        expect($result)->toBeInstanceOf(ImageHelper::class);
    });

    test('flip flips image vertically', function () {
        $result = $this->helper->image($this->testImage)->flip('v');

        expect($result)->toBeInstanceOf(ImageHelper::class);
    });

    test('flip flips image both ways', function () {
        $result = $this->helper->image($this->testImage)->flip('both');

        expect($result)->toBeInstanceOf(ImageHelper::class);
    });

    test('orientation auto-orients image', function () {
        $result = $this->helper->image($this->testImage)->orientation('auto');

        expect($result)->toBeInstanceOf(ImageHelper::class);
    });

    test('orientation rotates to specific angle', function () {
        $result = $this->helper->image($this->testImage)->orientation('90');

        expect($result)->toBeInstanceOf(ImageHelper::class);
    });

    test('opacity adjusts transparency', function () {
        $result = $this->helper->image($this->testImage)->opacity(50);

        expect($result)->toBeInstanceOf(ImageHelper::class);
    });

    test('brightness adjusts brightness', function () {
        $result = $this->helper->image($this->testImage)->brightness('20');

        expect($result)->toBeInstanceOf(ImageHelper::class);
    });

    test('contrast adjusts contrast', function () {
        $result = $this->helper->image($this->testImage)->contrast('10');

        expect($result)->toBeInstanceOf(ImageHelper::class);
    });

    test('gamma adjusts gamma', function () {
        $result = $this->helper->image($this->testImage)->gamma(1.5);

        expect($result)->toBeInstanceOf(ImageHelper::class);
    });

    test('sharpen sharpens image', function () {
        $result = $this->helper->image($this->testImage)->sharpen(10);

        expect($result)->toBeInstanceOf(ImageHelper::class);
    });

    test('blur blurs image', function () {
        $result = $this->helper->image($this->testImage)->blur(5);

        expect($result)->toBeInstanceOf(ImageHelper::class);
    });

    test('pixelate pixelates image', function () {
        $result = $this->helper->image($this->testImage)->pixelate('10');

        expect($result)->toBeInstanceOf(ImageHelper::class);
    });

    test('invert inverts colors', function () {
        $result = $this->helper->image($this->testImage)->invert();

        expect($result)->toBeInstanceOf(ImageHelper::class);
    });

    test('filter applies greyscale', function () {
        $result = $this->helper->image($this->testImage)->filter('greyscale');

        expect($result)->toBeInstanceOf(ImageHelper::class);
    });

    test('filter applies sepia', function () {
        $result = $this->helper->image($this->testImage)->filter('sepia');

        expect($result)->toBeInstanceOf(ImageHelper::class);
    });

    test('encode encodes to format', function () {
        $result = $this->helper->image($this->testImage)->encode('jpg', 90);

        expect($result)->toBeInstanceOf(ImageHelper::class);
    });

    test('save saves image to file', function () {
        $outputPath = $this->testDir.'/output.png';

        $result = $this->helper->image($this->testImage)
            ->save($this->testDir, 'output.png', 90);

        expect($result)->toBeTrue();
        expect(file_exists($outputPath))->toBeTrue();
    });

    test('fluent interface chains operations', function () {
        $outputPath = $this->testDir.'/chained.png';

        $result = $this->helper->image($this->testImage)
            ->width(50)
            ->height(50)
            ->resize()
            ->brightness('10')
            ->contrast('5')
            ->save($this->testDir, 'chained.png');

        expect($result)->toBeTrue();
        expect(file_exists($outputPath))->toBeTrue();
    });
});
