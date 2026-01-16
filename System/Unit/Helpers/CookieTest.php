<?php

declare(strict_types=1);

use Helpers\DateTimeHelper;
use Helpers\Http\Cookie;

describe('Cookie', function () {

    beforeEach(function () {
        // Clear cookies before each test
        $_COOKIE = [];
    });

    test('get returns cookie value', function () {
        $_COOKIE['test'] = 'value';
        $cookie = new Cookie();

        expect($cookie->get('test'))->toBe('value');
    });

    test('get returns default when cookie does not exist', function () {
        $cookie = new Cookie();

        expect($cookie->get('nonexistent', 'default'))->toBe('default');
    });

    test('has returns true when cookie exists', function () {
        $_COOKIE['test'] = 'value';
        $cookie = new Cookie();

        expect($cookie->has('test'))->toBeTrue();
    });

    test('has returns false when cookie does not exist', function () {
        $cookie = new Cookie();

        expect($cookie->has('nonexistent'))->toBeFalse();
    });

    test('getExpiryTimestamp handles DateTimeInterface', function () {
        $cookie = new Cookie();
        $date = DateTimeHelper::now()->addDays(1);

        $reflection = new ReflectionClass($cookie);
        $method = $reflection->getMethod('getExpiryTimestamp');
        $method->setAccessible(true);

        $timestamp = $method->invoke($cookie, $date);
        expect($timestamp)->toBe($date->getTimestamp());
    });

    test('getExpiryTimestamp handles zero expiry', function () {
        $cookie = new Cookie();

        $reflection = new ReflectionClass($cookie);
        $method = $reflection->getMethod('getExpiryTimestamp');
        $method->setAccessible(true);

        $timestamp = $method->invoke($cookie, 0);
        expect($timestamp)->toBe(0);
    });

    test('getExpiryTimestamp handles relative seconds', function () {
        $cookie = new Cookie();

        $reflection = new ReflectionClass($cookie);
        $method = $reflection->getMethod('getExpiryTimestamp');
        $method->setAccessible(true);

        $timestamp = $method->invoke($cookie, 3600);
        expect($timestamp)->toBeGreaterThan(time());
    });

    test('set throws exception for invalid SameSite', function () {
        $cookie = new Cookie();

        expect(fn () => $cookie->set('test', 'value', 0, '/', null, true, true, 'Invalid'))
            ->toThrow(InvalidArgumentException::class, "SameSite attribute must be 'Lax', 'Strict', or 'None'.");
    });

    test('set throws exception when SameSite is None without Secure', function () {
        $cookie = new Cookie();

        expect(fn () => $cookie->set('test', 'value', 0, '/', null, false, true, 'None'))
            ->toThrow(InvalidArgumentException::class, 'SameSite=None requires Secure=true.');
    });

    test('configureSessionCookie throws exception for invalid SameSite', function () {
        expect(fn () => Cookie::configureSessionCookie(3600, '/', null, true, true, 'Invalid'))
            ->toThrow(InvalidArgumentException::class, "SameSite attribute must be 'Lax', 'Strict', or 'None'.");
    });
});
