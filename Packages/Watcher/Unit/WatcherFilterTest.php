<?php

declare(strict_types=1);

namespace Tests\Packages\Watcher\Unit;

use Mockery;
use Watcher\Config\WatcherConfig;
use Watcher\Filters\WatcherFilter;

beforeEach(function () {
    $this->config = Mockery::mock(WatcherConfig::class);
    $this->filter = new WatcherFilter($this->config);
});

afterEach(function () {
    Mockery::close();
});

describe('WatcherFilter', function () {
    test('shouldIgnore returns true for ignored paths', function () {
        $this->config->shouldReceive('getIgnoredPaths')->andReturn(['/health', '/metrics']);

        expect($this->filter->shouldIgnore('request', ['uri' => '/health']))->toBeTrue();
        expect($this->filter->shouldIgnore('request', ['uri' => '/metrics']))->toBeTrue();
        expect($this->filter->shouldIgnore('request', ['uri' => '/api/users']))->toBeFalse();
    });

    test('shouldIgnore returns true for ignored queries', function () {
        $this->config->shouldReceive('getIgnoredQueries')->andReturn(['SELECT 1']);

        expect($this->filter->shouldIgnore('query', ['sql' => 'SELECT 1']))->toBeTrue();
        expect($this->filter->shouldIgnore('query', ['sql' => 'SELECT * FROM users']))->toBeFalse();
    });

    test('filter redacts sensitive fields', function () {
        $this->config->shouldReceive('getRedactFields')->andReturn(['password', 'token']);

        $data = [
            'username' => 'john',
            'password' => 'secret123',
            'meta' => [
                'token' => 'abc-def',
                'other' => 'value',
            ],
        ];

        $filtered = $this->filter->filter('request', $data);

        expect($filtered['username'])->toBe('john');
        expect($filtered['password'])->toBe('[REDACTED]');
        expect($filtered['meta']['token'])->toBe('[REDACTED]');
        expect($filtered['meta']['other'])->toBe('value');
    });
});
