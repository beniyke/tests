<?php

declare(strict_types=1);

use Helpers\Http\Client\Curl;
use Helpers\Http\Client\Response;
use Package\Services\HydrationService;

describe('HydrationService', function () {
    test('getLatestRelease falls back to tags on 404', function () {
        $curl = mock(Curl::class);
        $curl->shouldReceive('withHeader')->andReturn($curl);

        // Mock 404 for releases/latest
        $releaseResponse = new Response([
            'status' => true,
            'http_code' => 404,
            'message' => 'Not Found',
            'body' => null,
            'headers' => ['Status-Line' => 'HTTP/1.1 404 Not Found'],
        ]);

        // Mock 200 for tags
        $tagsResponse = new Response([
            'status' => true,
            'http_code' => 200,
            'message' => 'Success',
            'body' => json_encode([
                ['name' => 'v2.1.0', 'zipball_url' => 'https://example.com/v2.1.0.zip']
            ]),
            'headers' => ['Status-Line' => 'HTTP/1.1 200 OK'],
        ]);

        $curl->shouldReceive('get')->with('https://api.github.com/repos/beniyke/anchor/releases/latest')->andReturn($curl);
        $curl->shouldReceive('get')->with('https://api.github.com/repos/beniyke/anchor/tags')->andReturn($curl);
        $curl->shouldReceive('send')->andReturn($releaseResponse, $tagsResponse);

        $service = new HydrationService($curl);
        $release = $service->getLatestRelease();

        expect($release['tag_name'])->toBe('v2.1.0');
        expect($release['is_fallback'])->toBeTrue();
    });

    test('getLatestRelease throws exception on other errors', function () {
        $curl = mock(Curl::class);
        $curl->shouldReceive('withHeader')->andReturn($curl);

        $errorResponse = new Response([
            'status' => true,
            'http_code' => 500,
            'message' => 'Internal Server Error',
            'body' => null,
            'headers' => ['Status-Line' => 'HTTP/1.1 500 Internal Server Error'],
        ]);

        $curl->shouldReceive('get')->andReturn($curl);
        $curl->shouldReceive('send')->andReturn($errorResponse);

        $service = new HydrationService($curl);

        expect(fn () => $service->getLatestRelease())->toThrow(RuntimeException::class, '500: Internal Server Error');
    });

    test('downloadZip uses curl download with timeout', function () {
        $curl = mock(Curl::class);
        $curl->shouldReceive('withHeader')->andReturn($curl);
        $curl->shouldReceive('timeout')->with(300000)->andReturn($curl);
        $curl->shouldReceive('download')->with('https://example.com/file.zip', '/path/to/save.zip')->andReturn(true);

        $service = new HydrationService($curl);
        $result = $service->downloadZip('https://example.com/file.zip', '/path/to/save.zip');

        expect($result)->toBeTrue();
    });
});
