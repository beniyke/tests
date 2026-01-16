<?php

declare(strict_types=1);

namespace Tests\Packages\Money\Unit;

use Money\Currency;
use Money\Exceptions\InvalidCurrencyException;

describe('Currency Creation', function () {
    test('can create currency from code', function () {
        $currency = Currency::of('USD');

        expect($currency->getCode())->toBe('USD')
            ->and($currency->getName())->toBe('US Dollar')
            ->and($currency->getSymbol())->toBe('$')
            ->and($currency->getMinorUnit())->toBe(2);
    });

    test('currency code is case insensitive', function () {
        $currency = Currency::of('usd');

        expect($currency->getCode())->toBe('USD');
    });

    test('throws on unknown currency', function () {
        Currency::of('INVALID');
    })->throws(InvalidCurrencyException::class);

    test('can get subunit', function () {
        $usd = Currency::of('USD');
        $jpy = Currency::of('JPY');

        expect($usd->getSubunit())->toBe(100)
            ->and($jpy->getSubunit())->toBe(1); // JPY has 0 minor units
    });
});

describe('Currency Comparison', function () {
    test('can check equality', function () {
        $usd1 = Currency::of('USD');
        $usd2 = Currency::of('USD');
        $eur = Currency::of('EUR');

        expect($usd1->equals($usd2))->toBeTrue()
            ->and($usd1->equals($eur))->toBeFalse();
    });

    test('can check currency code', function () {
        $currency = Currency::of('USD');

        expect($currency->is('USD'))->toBeTrue()
            ->and($currency->is('EUR'))->toBeFalse();
    });
});

describe('Currency Properties', function () {
    test('USD has correct properties', function () {
        $usd = Currency::of('USD');

        expect($usd->getCode())->toBe('USD')
            ->and($usd->getSymbol())->toBe('$')
            ->and($usd->getMinorUnit())->toBe(2)
            ->and($usd->getName())->toBe('US Dollar');
    });

    test('EUR has correct properties', function () {
        $eur = Currency::of('EUR');

        expect($eur->getCode())->toBe('EUR')
            ->and($eur->getSymbol())->toBe('€')
            ->and($eur->getMinorUnit())->toBe(2);
    });

    test('JPY has zero minor units', function () {
        $jpy = Currency::of('JPY');

        expect($jpy->getMinorUnit())->toBe(0)
            ->and($jpy->getSubunit())->toBe(1);
    });
});

describe('Currency String Representation', function () {
    test('can convert to string', function () {
        $currency = Currency::of('USD');

        expect((string) $currency)->toBe('USD');
    });
});
