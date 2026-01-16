<?php

declare(strict_types=1);

use Core\Ioc\Container;
use Helpers\File\FileSystem;
use Helpers\File\Paths;
use Helpers\Html\Assets;
use Helpers\Http\Request;

beforeEach(function () {
    $this->request = Mockery::mock(Request::class);
    $this->request->shouldReceive('baseurl')
        ->andReturnUsing(fn ($path) => 'http://localhost/' . ltrim($path, '/'));

    Container::getInstance()->bind(Request::class, fn () => $this->request);

    $this->assets = new Assets();
});

afterEach(function () {
    Mockery::close();
});

describe('Assets', function () {

    describe('URL Generation', function () {
        test('returns empty string for null file', function () {
            $result = $this->assets->url(null);
            expect($result)->toBe('');
        });

        test('returns empty string for empty file', function () {
            $result = $this->assets->url('');
            expect($result)->toBe('');
        });

        test('returns URL as-is if already valid URL', function () {
            $url = 'https://cdn.example.com/style.css';
            $result = $this->assets->url($url);
            expect($result)->toBe($url);
        });

        test('returns http URL as-is', function () {
            $url = 'http://example.com/script.js';
            $result = $this->assets->url($url);
            expect($result)->toBe($url);
        });

        test('generates URL for asset file', function () {
            $result = $this->assets->url('css/app.css');
            expect($result)->toContain('public/assets/css/app.css');
        });

        test('strips leading slash from file path', function () {
            $result = $this->assets->url('/css/app.css');
            expect($result)->toContain('public/assets/css/app.css');
            expect($result)->not->toContain('public/assets//css');
        });
    });

    describe('Cache Busting', function () {
        test('appends timestamp for existing file', function () {
            $testFile = Paths::publicPath('assets/test-file.css');
            $dir = dirname($testFile);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($testFile, '/* test */');

            $result = $this->assets->url('test-file.css');

            // Should contain a query parameter with timestamp (e.g., test-file.css?1678886400)
            expect($result)->toMatch('/test-file\.css\?\d+/');
            FileSystem::delete($testFile);
        });

        test('does not append timestamp for non-existent file', function () {
            $result = $this->assets->url('non-existent-file-xyz.css');
            expect($result)->not->toContain('?');
            expect($result)->toContain('public/assets/non-existent-file-xyz.css');
        });

        test('timestamp changes when file is modified', function () {
            $testFile = Paths::publicPath('assets/timestamp-test.css');
            $dir = dirname($testFile);

            if (! FileSystem::exists($dir)) {
                FileSystem::mkdir($dir, 0755, true);
            }

            FileSystem::put($testFile, '/* version 1 */');
            $firstUrl = $this->assets->url('timestamp-test.css');

            // Extract timestamp from first URL
            preg_match('/\?(\d+)$/', $firstUrl, $matches);
            $firstTimestamp = $matches[1] ?? null;

            // Wait a moment and modify the file
            sleep(1);
            FileSystem::put($testFile, '/* version 2 */');

            // Create new Assets instance to avoid any local instance caching
            $assets2 = new Assets();
            $secondUrl = $assets2->url('timestamp-test.css');

            // Extract timestamp from second URL
            preg_match('/\?(\d+)$/', $secondUrl, $matches);
            $secondTimestamp = $matches[1] ?? null;

            // Timestamps should be different and the second should be later
            expect($firstTimestamp)->not->toBeNull();
            expect($secondTimestamp)->not->toBeNull();
            expect($secondTimestamp)->toBeGreaterThan($firstTimestamp); // Changed to '>' as sleep(1) guarantees a new timestamp
            FileSystem::delete($testFile);
        });
    });

    describe('Path Normalization', function () {
        test('handles multiple leading slashes', function () {
            $result = $this->assets->url('///css/app.css');
            expect($result)->toContain('public/assets/css/app.css');
            expect($result)->not->toContain('public/assets///');
        });

        test('handles nested paths', function () {
            $result = $this->assets->url('vendor/bootstrap/css/bootstrap.min.css');
            expect($result)->toContain('public/assets/vendor/bootstrap/css/bootstrap.min.css');
        });

        test('handles paths with dots', function () {
            $result = $this->assets->url('css/app.v2.min.css');
            expect($result)->toContain('public/assets/css/app.v2.min.css');
        });
    });

    describe('Different File Types', function () {
        test('handles CSS files', function () {
            $result = $this->assets->url('css/style.css');
            expect($result)->toContain('public/assets/css/style.css');
        });

        test('handles JavaScript files', function () {
            $result = $this->assets->url('js/app.js');
            expect($result)->toContain('public/assets/js/app.js');
        });

        test('handles image files', function () {
            $result = $this->assets->url('images/logo.png');
            expect($result)->toContain('public/assets/images/logo.png');
        });

        test('handles font files', function () {
            $result = $this->assets->url('fonts/roboto.woff2');
            expect($result)->toContain('public/assets/fonts/roboto.woff2');
        });

        test('handles files without extension', function () {
            $result = $this->assets->url('LICENSE');
            expect($result)->toContain('public/assets/LICENSE');
        });
    });

    describe('Integration with url() Helper', function () {
        test('generates full URL', function () {
            $result = $this->assets->url('css/app.css');
            expect($result)->toMatch('/^https?:\/\//');
        });
    });

    describe('Edge Cases', function () {
        test('handles whitespace in file path', function () {
            $result = $this->assets->url(' css/app.css ');
            expect($result)->toContain('public/assets/css/app.css');
        });

        test('handles special characters in filename', function () {
            $result = $this->assets->url('css/app-v1.2.3_final.css');
            expect($result)->toContain('public/assets/css/app-v1.2.3_final.css');
        });

        test('handles query parameters in already-valid URLs', function () {
            $url = 'https://cdn.example.com/style.css?v=123';
            $result = $this->assets->url($url);
            expect($result)->toBe($url);
        });

        test('handles protocol-relative URLs', function () {
            $url = '//cdn.example.com/style.css';
            $result = $this->assets->url($url);
            expect($result)->toBe($url);
        });
    });
});
