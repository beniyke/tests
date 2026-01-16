<?php

declare(strict_types=1);

use Helpers\File\FileSizeHelper;

describe('FileSizeHelper', function () {
    describe('toBytes', function () {
        test('converts megabytes to bytes', function () {
            expect(FileSizeHelper::toBytes('2mb'))->toBe(2097152);
            expect(FileSizeHelper::toBytes('1MB'))->toBe(1048576);
            expect(FileSizeHelper::toBytes('0.5mb'))->toBe(524288);
        });

        test('converts kilobytes to bytes', function () {
            expect(FileSizeHelper::toBytes('500kb'))->toBe(512000);
            expect(FileSizeHelper::toBytes('1KB'))->toBe(1024);
            expect(FileSizeHelper::toBytes('10kb'))->toBe(10240);
        });

        test('converts gigabytes to bytes', function () {
            expect(FileSizeHelper::toBytes('1gb'))->toBe(1073741824);
            expect(FileSizeHelper::toBytes('2GB'))->toBe(2147483648);
            expect(FileSizeHelper::toBytes('0.5gb'))->toBe(536870912);
        });

        test('converts terabytes to bytes', function () {
            expect(FileSizeHelper::toBytes('1tb'))->toBe(1099511627776);
            expect(FileSizeHelper::toBytes('2TB'))->toBe(2199023255552);
        });

        test('handles bytes unit', function () {
            expect(FileSizeHelper::toBytes('1024b'))->toBe(1024);
            expect(FileSizeHelper::toBytes('100B'))->toBe(100);
            expect(FileSizeHelper::toBytes('1024'))->toBe(1024);
        });

        test('is case insensitive', function () {
            expect(FileSizeHelper::toBytes('2MB'))->toBe(2097152);
            expect(FileSizeHelper::toBytes('2mb'))->toBe(2097152);
            expect(FileSizeHelper::toBytes('2Mb'))->toBe(2097152);
            expect(FileSizeHelper::toBytes('2mB'))->toBe(2097152);
        });

        test('handles whitespace', function () {
            expect(FileSizeHelper::toBytes('2 mb'))->toBe(2097152);
            expect(FileSizeHelper::toBytes('  500  kb  '))->toBe(512000);
            expect(FileSizeHelper::toBytes('1 GB'))->toBe(1073741824);
        });

        test('accepts numeric values for backward compatibility', function () {
            expect(FileSizeHelper::toBytes(2097152))->toBe(2097152);
            expect(FileSizeHelper::toBytes(1024))->toBe(1024);
            expect(FileSizeHelper::toBytes(0))->toBe(0);
        });

        test('handles zero bytes', function () {
            expect(FileSizeHelper::toBytes('0mb'))->toBe(0);
            expect(FileSizeHelper::toBytes('0kb'))->toBe(0);
            expect(FileSizeHelper::toBytes(0))->toBe(0);
        });

        test('handles decimal values', function () {
            expect(FileSizeHelper::toBytes('1.5mb'))->toBe(1572864);
            expect(FileSizeHelper::toBytes('2.5kb'))->toBe(2560);
            expect(FileSizeHelper::toBytes('0.25gb'))->toBe(268435456);
        });

        test('supports alternative unit names', function () {
            expect(FileSizeHelper::toBytes('1k'))->toBe(1024);
            expect(FileSizeHelper::toBytes('1m'))->toBe(1048576);
            expect(FileSizeHelper::toBytes('1g'))->toBe(1073741824);
            expect(FileSizeHelper::toBytes('1t'))->toBe(1099511627776);
        });

        test('throws exception for invalid format', function () {
            expect(fn () => FileSizeHelper::toBytes('invalid'))
                ->toThrow(InvalidArgumentException::class);

            expect(fn () => FileSizeHelper::toBytes('abc123'))
                ->toThrow(InvalidArgumentException::class);

            expect(fn () => FileSizeHelper::toBytes(''))
                ->toThrow(InvalidArgumentException::class);
        });

        test('throws exception for unknown unit', function () {
            expect(fn () => FileSizeHelper::toBytes('2xyz'))
                ->toThrow(InvalidArgumentException::class, 'Unknown file size unit');
        });

        test('throws exception for negative values', function () {
            expect(fn () => FileSizeHelper::toBytes('-2mb'))
                ->toThrow(InvalidArgumentException::class, 'cannot be negative');

            expect(fn () => FileSizeHelper::toBytes(-1024))
                ->toThrow(InvalidArgumentException::class, 'cannot be negative');
        });
    });

    describe('fromBytes', function () {
        test('converts bytes to human-readable format', function () {
            expect(FileSizeHelper::fromBytes(0))->toBe('0 B');
            expect(FileSizeHelper::fromBytes(1024))->toBe('1 KB');
            expect(FileSizeHelper::fromBytes(1048576))->toBe('1 MB');
            expect(FileSizeHelper::fromBytes(1073741824))->toBe('1 GB');
            expect(FileSizeHelper::fromBytes(1099511627776))->toBe('1 TB');
        });

        test('handles precision parameter', function () {
            expect(FileSizeHelper::fromBytes(1536, 0))->toBe('2 KB');
            expect(FileSizeHelper::fromBytes(1536, 1))->toBe('1.5 KB');
            expect(FileSizeHelper::fromBytes(1536, 2))->toBe('1.5 KB');
        });

        test('formats complex byte values', function () {
            expect(FileSizeHelper::fromBytes(2097152))->toBe('2 MB');
            expect(FileSizeHelper::fromBytes(512000))->toBe('500 KB');
            expect(FileSizeHelper::fromBytes(1572864))->toBe('1.5 MB');
        });

        test('throws exception for negative bytes', function () {
            expect(fn () => FileSizeHelper::fromBytes(-1024))
                ->toThrow(InvalidArgumentException::class, 'cannot be negative');
        });
    });

    describe('round-trip conversion', function () {
        test('toBytes and fromBytes are consistent', function () {
            $original = '2 MB';
            $bytes = FileSizeHelper::toBytes($original);
            $converted = FileSizeHelper::fromBytes($bytes);
            expect($converted)->toBe('2 MB');
        });

        test('handles various sizes', function () {
            $sizes = ['1 KB', '500 KB', '2 MB', '1.5 GB'];

            foreach ($sizes as $size) {
                $bytes = FileSizeHelper::toBytes($size);
                $converted = FileSizeHelper::fromBytes($bytes);
                expect($converted)->toBe($size);
            }
        });
    });
});
