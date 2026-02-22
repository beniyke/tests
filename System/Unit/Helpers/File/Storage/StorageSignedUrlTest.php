<?php

declare(strict_types=1);

use Core\Ioc\Container;
use Core\Services\ConfigServiceInterface;
use Helpers\File\FileSystem;
use Helpers\File\Paths;
use Helpers\File\Storage\Adapters\LocalAdapter;

beforeEach(function () {
    $this->tempDir = Paths::storagePath('testing/signed_' . uniqid());
    FileSystem::mkdir($this->tempDir, 0755, true);

    // Mock Config to avoid using env() and ensure config() helper works
    $this->config = Mockery::mock(ConfigServiceInterface::class);
    // StorageAdapter uses encryption_key
    $this->config->shouldReceive('get')->with('encryption_key', null)->andReturn('base64:TestingKey123456789012345678901234');

    // LocalAdapter uses these for temporaryUrl
    $this->config->shouldReceive('get')->with('filesystems.links.threshold', 1000000000)->andReturn(1000000000);
    $this->config->shouldReceive('get')->with('host', '')->andReturn('http://localhost');
    $this->config->shouldReceive('get')->with('filesystems.links.signed_route', '/storage/signed/view')->andReturn('/storage/signed/view');

    Container::getInstance()->instance(ConfigServiceInterface::class, $this->config);

    $this->adapter = new LocalAdapter(['root' => $this->tempDir]);
});

afterEach(function () {
    Mockery::close();
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

test('generateSignature creates consistent signatures', function () {
    $path = 'private/doc.pdf';
    $expiration = time() + 3600;

    $sig1 = $this->adapter->generateSignature($path, $expiration);
    $sig2 = $this->adapter->generateSignature($path, $expiration);

    expect($sig1)->toBe($sig2);
    expect(strlen($sig1))->toBeGreaterThan(0);
});

test('hasValidSignature validates correctly', function () {
    $path = 'private/image.jpg';
    $expiration = time() + 300;

    $signature = $this->adapter->generateSignature($path, $expiration);

    expect($this->adapter->hasValidSignature($path, $expiration, $signature))->toBeTrue();
    expect($this->adapter->hasValidSignature($path, $expiration, 'invalid_sig'))->toBeFalse();
    expect($this->adapter->hasValidSignature('other.jpg', $expiration, $signature))->toBeFalse();
});

test('temporaryUrl returns signed url', function () {
    $path = 'file.txt';
    $url = $this->adapter->temporaryUrl($path, 3600);

    expect($url)->toContain('storage/signed/view');
    expect($url)->toContain('signature=');
    expect($url)->toContain('expires=');

    // Verify parameters in URL
    parse_str(parse_url($url, PHP_URL_QUERY), $params);

    expect($params)->toHaveKeys(['path', 'expires', 'signature']);
    expect($this->adapter->hasValidSignature($params['path'], (int)$params['expires'], $params['signature']))->toBeTrue();
});
