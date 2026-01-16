<?php

declare(strict_types=1);

use Testing\Fakes\CacheFake;

beforeEach(function () {
    $this->cache = new CacheFake();
});

describe('CacheFake - Basic Operations', function () {
    test('can write and read cache', function () {
        $this->cache->write('user-1', ['name' => 'John']);

        $value = $this->cache->read('user-1');
        expect($value)->toBe(['name' => 'John']);
    });

    test('read returns default when key missing', function () {
        $value = $this->cache->read('missing-key', 'default');
        expect($value)->toBe('default');
    });

    test('has returns true for existing key', function () {
        $this->cache->write('exists', 'value');

        expect($this->cache->has('exists'))->toBeTrue();
        expect($this->cache->has('missing'))->toBeFalse();
    });

    test('can delete cache key', function () {
        $this->cache->write('to-delete', 'value');

        $this->cache->delete('to-delete');

        expect($this->cache->has('to-delete'))->toBeFalse();
    });

    test('can clear all cache', function () {
        $this->cache->write('key1', 'value1');
        $this->cache->write('key2', 'value2');

        $this->cache->clear();

        expect($this->cache->keys())->toBeEmpty();
    });
});

describe('CacheFake - TTL', function () {
    test('expired cache returns default', function () {
        // Write with 0 TTL - simulating expired
        $this->cache->write('expired', 'value', -1);

        $value = $this->cache->read('expired', 'default');
        // Since TTL = -1 means expires = time() - 1, it should be expired
        expect($value)->toBe('default');
    });
});

describe('CacheFake - Remember', function () {
    test('remember stores and returns value', function () {
        $value = $this->cache->remember('computed', 60, fn () => 'computed-value');

        expect($value)->toBe('computed-value');
        expect($this->cache->read('computed'))->toBe('computed-value');
    });

    test('remember returns cached value on subsequent calls', function () {
        $callCount = 0;
        $callback = function () use (&$callCount) {
            $callCount++;

            return 'value';
        };

        $this->cache->remember('key', 60, $callback);
        $this->cache->remember('key', 60, $callback);

        expect($callCount)->toBe(1);
    });
});

describe('CacheFake - SubPath', function () {
    test('withPath creates scoped cache', function () {
        $scopedCache = $this->cache->withPath('users');

        $scopedCache->write('profile', ['name' => 'John']);

        expect($scopedCache->has('profile'))->toBeTrue();
    });
});

describe('CacheFake - Assertions', function () {
    test('assertWritten passes when key was written', function () {
        $this->cache->write('user-1', ['name' => 'John']);

        $this->cache->assertWritten('user-1');
        expect(true)->toBeTrue();
    });

    test('assertWritten with value check', function () {
        $this->cache->write('user-1', 'John');

        $this->cache->assertWritten('user-1', 'John');
        expect(true)->toBeTrue();
    });

    test('assertRead passes when key was read', function () {
        $this->cache->write('user-1', 'value');
        $this->cache->read('user-1');

        $this->cache->assertRead('user-1');
        expect(true)->toBeTrue();
    });

    test('assertDeleted passes when key was deleted', function () {
        $this->cache->write('user-1', 'value');
        $this->cache->delete('user-1');

        $this->cache->assertDeleted('user-1');
        expect(true)->toBeTrue();
    });

    test('assertCleared passes when cache was cleared', function () {
        $this->cache->write('key', 'value');
        $this->cache->clear();

        $this->cache->assertCleared();
        expect(true)->toBeTrue();
    });

    test('assertHas checks current state', function () {
        $this->cache->write('user-1', 'John');

        $this->cache->assertHas('user-1');
        $this->cache->assertHas('user-1', 'John');
        expect(true)->toBeTrue();
    });

    test('assertMissing passes when key is not in cache', function () {
        $this->cache->assertMissing('nonexistent');
        expect(true)->toBeTrue();
    });
});
