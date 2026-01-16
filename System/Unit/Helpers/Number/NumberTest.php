<?php

declare(strict_types=1);

use Helpers\Number\Number;

describe('Number Helper', function () {
    test('trailingZeros pads numbers below limit', function () {
        expect(Number::trailingZeros(5, 3, 100))->toBe('005');
        expect(Number::trailingZeros(42, 4, 1000))->toBe('0042');
    });

    test('trailingZeros returns number as-is when above limit', function () {
        expect(Number::trailingZeros(150, 3, 100))->toBe('150');
    });

    test('tosize converts bytes to human readable format', function () {
        expect(Number::tosize(1024))->toBe('1 kb');
        expect(Number::tosize(1048576))->toBe('1 mb');
        expect(Number::tosize(1073741824))->toBe('1 gb');
    });

    test('toAlphabet converts numbers to letters', function () {
        expect(Number::toAlphabet(1))->toBe('A');
        expect(Number::toAlphabet(26))->toBe('Z');
        expect(Number::toAlphabet(27))->toBe(''); // Out of range
    });

    test('ordinal adds correct suffix', function () {
        expect(Number::ordinal(1))->toBe('1st');
        expect(Number::ordinal(2))->toBe('2nd');
        expect(Number::ordinal(3))->toBe('3rd');
        expect(Number::ordinal(4))->toBe('4th');
        expect(Number::ordinal(11))->toBe('11th');
        expect(Number::ordinal(21))->toBe('21st');
    });

    test('toWords converts numbers to words', function () {
        expect(Number::toWords(0))->toContain('zero');
        expect(Number::toWords(5))->toContain('five');
        expect(Number::toWords(100))->toContain('hundred');
        expect(Number::toWords(1000))->toContain('thousand');
        expect(Number::toWords(123))->toContain('one hundred and twenty three');
    });

    test('toDecimal divides by 100', function () {
        expect(Number::toDecimal(500))->toBe(5);
        expect(Number::toDecimal(1234))->toBe(12.34);
    });

    test('toInteger multiplies by 100', function () {
        expect(Number::toInteger(5))->toBe(500);
        expect(Number::toInteger(12.34))->toBe(1234);
    });

    test('pretify formats large numbers', function () {
        expect(Number::pretify(999))->toBe('999');
        expect(Number::pretify(1000))->toBe('1K');
        expect(Number::pretify(1500))->toBe('2K'); // Rounds to 2K with 0 decimal places
        expect(Number::pretify(1000000))->toBe('1M');
    });

    test('toRoman converts to roman numerals', function () {
        expect(Number::toRoman(1))->toBe('I');
        expect(Number::toRoman(4))->toBe('IV');
        expect(Number::toRoman(9))->toBe('IX');
        expect(Number::toRoman(58))->toBe('LVIII');
        expect(Number::toRoman(1994))->toBe('MCMXCIV');
    });

    test('fromRoman converts roman numerals to integer', function () {
        expect(Number::fromRoman('I'))->toBe(1);
        expect(Number::fromRoman('IV'))->toBe(4);
        expect(Number::fromRoman('IX'))->toBe(9);
        expect(Number::fromRoman('LVIII'))->toBe(58);
        expect(Number::fromRoman('MCMXCIV'))->toBe(1994);
    });

    test('clamp restricts number to range', function () {
        expect(Number::clamp(10, 0, 100))->toBe(10);
        expect(Number::clamp(-5, 0, 100))->toBe(0);
        expect(Number::clamp(150, 0, 100))->toBe(100);
    });

    test('percentage calculates ratio', function () {
        expect(Number::percentage(50, 100))->toBe(50.0);
        expect(Number::percentage(30, 60))->toBe(50.0);
        expect(Number::percentage(0, 100))->toBe(0.0);
    });

    test('toPercentage formats as percent string', function () {
        expect(Number::toPercentage(50))->toBe('50%');
        expect(Number::toPercentage(50.5, 1))->toBe('50.5%');
    });

    test('abbreviate aliases pretify', function () {
        expect(Number::abbreviate(1000))->toBe('1K');
        expect(Number::abbreviate(1000000))->toBe('1M');
    });

    test('format adds thousand separators', function () {
        expect(Number::format(1000))->toBe('1,000');
        expect(Number::format(1000000))->toBe('1,000,000');
        expect(Number::format(1234.56, 2))->toBe('1,234.56');
    });
});
