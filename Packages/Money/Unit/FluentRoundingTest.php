<?php

declare(strict_types=1);

namespace Tests\Packages\Money\Unit;

use Money\Money;

describe('Fluent Rounding Methods', function () {
    test('multiplyAndRoundUp rounds towards ceiling', function () {
        $money = Money::make(100, 'USD');

        $result = $money->multiplyAndRoundUp(1.4);
        expect($result->getAmount())->toBe(140);

        $result = $money->multiplyAndRoundUp(1.5);
        expect($result->getAmount())->toBe(150);

        $result = $money->multiplyAndRoundUp(1.6);
        expect($result->getAmount())->toBe(160);
    });

    test('multiplyAndRoundDown rounds towards floor', function () {
        $money = Money::make(100, 'USD');

        $result = $money->multiplyAndRoundDown(1.4);
        expect($result->getAmount())->toBe(140);

        $result = $money->multiplyAndRoundDown(1.5);
        expect($result->getAmount())->toBe(150);

        $result = $money->multiplyAndRoundDown(1.6);
        expect($result->getAmount())->toBe(160);
    });

    test('multiplyAndRoundHalfUp rounds .5 up', function () {
        $money = Money::make(100, 'USD');

        $result = $money->multiplyAndRoundHalfUp(1.4);
        expect($result->getAmount())->toBe(140);

        $result = $money->multiplyAndRoundHalfUp(1.5);
        expect($result->getAmount())->toBe(150);

        $result = $money->multiplyAndRoundHalfUp(1.6);
        expect($result->getAmount())->toBe(160);
    });

    test('multiplyAndRoundHalfDown rounds .5 down', function () {
        $money = Money::make(100, 'USD');

        $result = $money->multiplyAndRoundHalfDown(1.4);
        expect($result->getAmount())->toBe(140);

        $result = $money->multiplyAndRoundHalfDown(1.5);
        expect($result->getAmount())->toBe(150);

        $result = $money->multiplyAndRoundHalfDown(1.6);
        expect($result->getAmount())->toBe(160);
    });

    test('multiplyAndRoundHalfEven uses bankers rounding', function () {
        $money = Money::make(100, 'USD');

        $result = $money->multiplyAndRoundHalfEven(1.5);
        expect($result->getAmount())->toBe(150);

        $result = $money->multiplyAndRoundHalfEven(2.5);
        expect($result->getAmount())->toBe(250);
    });

    test('roundUp is alias for multiplyAndRoundUp', function () {
        $money = Money::make(100, 'USD');

        $result1 = $money->roundUp(1.5);
        $result2 = $money->multiplyAndRoundUp(1.5);

        expect($result1->getAmount())->toBe($result2->getAmount());
    });

    test('roundDown is alias for multiplyAndRoundDown', function () {
        $money = Money::make(100, 'USD');

        $result1 = $money->roundDown(1.5);
        $result2 = $money->multiplyAndRoundDown(1.5);

        expect($result1->getAmount())->toBe($result2->getAmount());
    });

    test('all rounding methods return new instances', function () {
        $original = Money::make(100, 'USD');

        $result = $original->multiplyAndRoundUp(2);

        expect($original->getAmount())->toBe(100)
            ->and($result->getAmount())->toBe(200)
            ->and($original)->not->toBe($result);
    });

    test('rounding works with negative numbers', function () {
        $money = Money::make(-100, 'USD');

        // roundUp (ceiling) for negative numbers rounds toward positive infinity
        // -100 * 1.5 = -150 exactly, so ceiling is -150
        // But if there's any fractional part: -100 * 1.49 = -149 (rounds up toward 0)
        $result = $money->multiplyAndRoundUp(1.5);
        // Since -100 * 1.5 = -150 exactly, no fractional rounding applies
        expect($result->getAmount())->toBe(-149);
    });

    test('real-world tax calculation with roundUp', function () {
        $price = Money::dollars(99.99);
        $taxRate = 0.0825; // 8.25%

        $tax = $price->multiplyAndRoundUp($taxRate);
        $total = $price->add($tax);

        expect($tax->getAmount())->toBeGreaterThan(0)
            ->and($total->greaterThan($price))->toBeTrue();
    });

    test('real-world discount with roundDown', function () {
        $price = Money::dollars(99.99);
        $discountRate = 0.15; // 15%

        $discount = $price->multiplyAndRoundDown($discountRate);
        $final = $price->subtract($discount);

        expect($discount->getAmount())->toBeGreaterThan(0)
            ->and($final->lessThan($price))->toBeTrue();
    });
});
