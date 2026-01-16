<?php

declare(strict_types=1);

namespace Tests\System\Unit\Helpers;

use Helpers\String\UuidGenerator;

describe('UuidGenerator', function () {

    test('v1 generates valid UUID', function () {
        $uuid = UuidGenerator::v1();
        expect(UuidGenerator::isValid($uuid))->toBeTrue();
        expect(UuidGenerator::getVersion($uuid))->toBe(1);
    });

    test('v1 maintains monotonicity', function () {
        $uuid1 = UuidGenerator::v1();
        $uuid2 = UuidGenerator::v1();

        // Extract timestamps
        $t1 = hexdec(substr($uuid1, 14, 4).substr($uuid1, 9, 4).substr($uuid1, 0, 8));
        $t2 = hexdec(substr($uuid2, 14, 4).substr($uuid2, 9, 4).substr($uuid2, 0, 8));

        // Remove version bit from high timestamp
        $t1 &= 0x0FFFFFFFFFFFFFFF;
        $t2 &= 0x0FFFFFFFFFFFFFFF;

        expect($t2)->toBeGreaterThanOrEqual($t1);
    });

    test('v4 generates valid UUID', function () {
        $uuid = UuidGenerator::v4();
        expect(UuidGenerator::isValid($uuid))->toBeTrue();
        expect(UuidGenerator::getVersion($uuid))->toBe(4);

        // Check variant (10xx)
        $variant = hexdec(substr($uuid, 19, 2));
        expect(($variant & 0xC0) === 0x80)->toBeTrue();
    });

    test('v7 generates valid UUID', function () {
        $uuid = UuidGenerator::v7();
        expect(UuidGenerator::isValid($uuid))->toBeTrue();
        expect(UuidGenerator::getVersion($uuid))->toBe(7);
    });

    test('v7 is time ordered', function () {
        $uuid1 = UuidGenerator::v7();
        usleep(1000); // Wait 1ms
        $uuid2 = UuidGenerator::v7();

        expect(strcmp($uuid1, $uuid2))->toBeLessThan(0);
    });

    test('v8 generates deterministic UUID', function () {
        $data = 'test-data';
        $ns = UuidGenerator::NS_URL;

        $uuid1 = UuidGenerator::v8($data, $ns);
        $uuid2 = UuidGenerator::v8($data, $ns);

        expect($uuid1)->toBe($uuid2);
        expect(UuidGenerator::isValid($uuid1))->toBeTrue();
        expect(UuidGenerator::getVersion($uuid1))->toBe(8);
    });

    test('nameBased generates valid UUID', function () {
        $uuid = UuidGenerator::nameBased('test');
        expect(UuidGenerator::isValid($uuid))->toBeTrue();
        expect(UuidGenerator::getVersion($uuid))->toBe(8);
    });

    test('isValid validates correctly', function () {
        expect(UuidGenerator::isValid(UuidGenerator::v4()))->toBeTrue();
        expect(UuidGenerator::isValid('invalid-uuid'))->toBeFalse();
        expect(UuidGenerator::isValid('00000000-0000-0000-0000-000000000000'))->toBeTrue();
    });

    test('isNil checks correctly', function () {
        expect(UuidGenerator::isNil(UuidGenerator::NIL))->toBeTrue();
        expect(UuidGenerator::isNil(UuidGenerator::v4()))->toBeFalse();
    });

    test('constants are correct', function () {
        expect(UuidGenerator::NIL)->toBe('00000000-0000-0000-0000-000000000000');
        expect(UuidGenerator::MAX)->toBe('ffffffff-ffff-ffff-ffff-ffffffffffff');
    });
});
