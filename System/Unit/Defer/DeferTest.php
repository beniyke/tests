<?php

declare(strict_types=1);

use Defer\Deferrer;
use Defer\DeferrerInterface;

describe('Defer System', function () {
    test('defer function exists', function () {
        expect(function_exists('defer'))->toBeTrue();
    });

    test('defer_as function exists', function () {
        expect(function_exists('defer_as'))->toBeTrue();
    });

    test('deferrer function exists', function () {
        expect(function_exists('deferrer'))->toBeTrue();
    });

    test('Deferrer class exists', function () {
        expect(class_exists(Deferrer::class))->toBeTrue();
    });

    test('Deferrer implements DeferrerInterface', function () {
        $deferrer = new Deferrer();
        expect($deferrer)->toBeInstanceOf(DeferrerInterface::class);
    });

    test('Deferrer can set name', function () {
        $deferrer = new Deferrer();
        $result = $deferrer->name('test_group');

        expect($result)->toBeInstanceOf(DeferrerInterface::class);
    });

    test('Deferrer can push callbacks', function () {
        $deferrer = new Deferrer();

        $deferrer->push(function () {
            return 'test';
        });

        expect($deferrer->hasPayload())->toBeTrue();
    });

    test('Deferrer can retrieve payloads', function () {
        $deferrer = new Deferrer();

        $deferrer->push(function () {
            return 'test';
        });

        $payloads = $deferrer->getPayloads();

        expect($payloads)->toBeArray();
        expect(count($payloads))->toBe(1);
    });

    test('Deferrer can clear payloads', function () {
        $deferrer = new Deferrer();

        $deferrer->push(function () {});
        expect($deferrer->hasPayload())->toBeTrue();

        $deferrer->clearPayloads();
        expect($deferrer->hasPayload())->toBeFalse();
    });

    test('Deferrer supports multiple callbacks', function () {
        $deferrer = new Deferrer();

        $deferrer->push(function () {});
        $deferrer->push(function () {});

        expect(count($deferrer->getPayloads()))->toBe(2);
    });
});
