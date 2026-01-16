<?php

declare(strict_types=1);

namespace Tests\Packages\Money\Unit;

use Money\Currency;
use Money\Exceptions\CurrencyMismatchException;
use Money\Exceptions\InvalidAmountException;
use Money\Exceptions\InvalidCurrencyException;
use Money\Money;
use Money\RoundingMode;

describe('Money Creation', function () {
    test('can create money with make()', function () {
        $money = Money::make(10000, 'USD');

        expect($money->getAmount())->toBe(10000)
            ->and($money->getCurrency()->getCode())->toBe('USD');
    });

    test('can create money with create()', function () {
        $money = Money::create(5000, 'EUR');

        expect($money->getAmount())->toBe(5000);
    });

    test('can create money with from()', function () {
        $money = Money::from(2000, 'GBP');

        expect($money->getAmount())->toBe(2000);
    });

    test('can create money with cents()', function () {
        $money = Money::cents(1500, 'USD');

        expect($money->getAmount())->toBe(1500);
    });

    test('can create money from major units', function () {
        $money = Money::amount(100.50, 'USD');

        expect($money->getAmount())->toBe(10050);
    });

    test('can create money with dollars()', function () {
        $money = Money::dollars(50.25);

        expect($money->getAmount())->toBe(5025)
            ->and($money->getCurrency()->getCode())->toBe('USD');
    });

    test('can create money with euros()', function () {
        $money = Money::euros(75.50);

        expect($money->getAmount())->toBe(7550)
            ->and($money->getCurrency()->getCode())->toBe('EUR');
    });

    test('can create money with pounds()', function () {
        $money = Money::pounds(100);

        expect($money->getAmount())->toBe(10000)
            ->and($money->getCurrency()->getCode())->toBe('GBP');
    });

    test('can create zero money', function () {
        $zero = Money::zero('USD');

        expect($zero->isZero())->toBeTrue()
            ->and($zero->getAmount())->toBe(0);
    });

    test('can create from array', function () {
        $money = Money::fromArray(['amount' => 5000, 'currency' => 'USD']);

        expect($money->getAmount())->toBe(5000);
    });

    test('throws on invalid currency', function () {
        Money::make(100, 'INVALID');
    })->throws(InvalidCurrencyException::class);

    test('throws on invalid amount string', function () {
        Money::make('invalid', 'USD');
    })->throws(InvalidAmountException::class);
});

describe('Money Arithmetic', function () {
    test('can add money', function () {
        $a = Money::make(100, 'USD');
        $b = Money::make(200, 'USD');
        $result = $a->add($b);

        expect($result->getAmount())->toBe(300);
    });

    test('can add multiple money objects', function () {
        $a = Money::make(100, 'USD');
        $b = Money::make(200, 'USD');
        $c = Money::make(300, 'USD');
        $result = $a->add($b, $c);

        expect($result->getAmount())->toBe(600);
    });

    test('can subtract money', function () {
        $a = Money::make(500, 'USD');
        $b = Money::make(200, 'USD');
        $result = $a->subtract($b);

        expect($result->getAmount())->toBe(300);
    });

    test('can multiply money', function () {
        $money = Money::make(100, 'USD');
        $result = $money->multiply(3);

        expect($result->getAmount())->toBe(300);
    });

    test('can multiply by decimal', function () {
        $money = Money::make(100, 'USD');
        $result = $money->multiply(1.5);

        expect($result->getAmount())->toBe(150);
    });

    test('can divide money', function () {
        $money = Money::make(300, 'USD');
        $result = $money->divide(3);

        expect($result->getAmount())->toBe(100);
    });

    test('throws on division by zero', function () {
        $money = Money::make(100, 'USD');
        $money->divide(0);
    })->throws(InvalidAmountException::class);

    test('can get modulo', function () {
        $money = Money::make(100, 'USD');
        $divisor = Money::make(30, 'USD');
        $result = $money->mod($divisor);

        expect($result->getAmount())->toBe(10);
    });

    test('can get absolute value', function () {
        $money = Money::make(-100, 'USD');
        $result = $money->absolute();

        expect($result->getAmount())->toBe(100);
    });

    test('can negate money', function () {
        $money = Money::make(100, 'USD');
        $result = $money->negative();

        expect($result->getAmount())->toBe(-100);
    });

    test('throws on currency mismatch in add', function () {
        $usd = Money::make(100, 'USD');
        $eur = Money::make(100, 'EUR');
        $usd->add($eur);
    })->throws(CurrencyMismatchException::class);

    test('throws on currency mismatch in subtract', function () {
        $usd = Money::make(100, 'USD');
        $eur = Money::make(100, 'EUR');
        $usd->subtract($eur);
    })->throws(CurrencyMismatchException::class);
});

describe('Money Percentage Operations', function () {
    test('can calculate percentage', function () {
        $money = Money::make(10000, 'USD');
        $result = $money->percentage(15);

        expect($result->getAmount())->toBe(1500);
    });

    test('can add percentage', function () {
        $money = Money::make(10000, 'USD');
        $result = $money->addPercentage(20);

        expect($result->getAmount())->toBe(12000);
    });

    test('can subtract percentage', function () {
        $money = Money::make(10000, 'USD');
        $result = $money->subtractPercentage(10);

        expect($result->getAmount())->toBe(9000);
    });

    test('can get ratio', function () {
        $a = Money::make(200, 'USD');
        $b = Money::make(100, 'USD');
        $ratio = $a->ratioOf($b);

        expect($ratio)->toBe(200.0);
    });

    test('ratio of zero returns zero', function () {
        $money = Money::make(100, 'USD');
        $zero = Money::zero('USD');
        $ratio = $money->ratioOf($zero);

        expect($ratio)->toBe(0.0);
    });
});

describe('Money Allocation', function () {
    test('can allocate by ratios', function () {
        $money = Money::make(100, 'USD');
        $parts = $money->allocate([1, 1, 1]);

        expect($parts)->toHaveCount(3)
            ->and($parts[0]->getAmount())->toBe(34)
            ->and($parts[1]->getAmount())->toBe(33)
            ->and($parts[2]->getAmount())->toBe(33);
    });

    test('can allocate equally', function () {
        $money = Money::make(100, 'USD');
        $parts = $money->allocateTo(4);

        expect($parts)->toHaveCount(4)
            ->and($parts[0]->getAmount())->toBe(25)
            ->and($parts[1]->getAmount())->toBe(25)
            ->and($parts[2]->getAmount())->toBe(25)
            ->and($parts[3]->getAmount())->toBe(25);
    });

    test('distributes remainder fairly', function () {
        $money = Money::make(10, 'USD');
        $parts = $money->allocate([1, 1, 1]);

        $total = array_sum(array_map(fn ($m) => $m->getAmount(), $parts));
        expect($total)->toBe(10);
    });
});

describe('Money Comparison', function () {
    test('can check equality', function () {
        $a = Money::make(100, 'USD');
        $b = Money::make(100, 'USD');

        expect($a->equals($b))->toBeTrue();
    });

    test('can check greater than', function () {
        $a = Money::make(200, 'USD');
        $b = Money::make(100, 'USD');

        expect($a->greaterThan($b))->toBeTrue()
            ->and($b->greaterThan($a))->toBeFalse();
    });

    test('can check less than', function () {
        $a = Money::make(100, 'USD');
        $b = Money::make(200, 'USD');

        expect($a->lessThan($b))->toBeTrue()
            ->and($b->lessThan($a))->toBeFalse();
    });

    test('can check greater than or equal', function () {
        $a = Money::make(100, 'USD');
        $b = Money::make(100, 'USD');
        $c = Money::make(50, 'USD');

        expect($a->greaterThanOrEqual($b))->toBeTrue()
            ->and($a->greaterThanOrEqual($c))->toBeTrue();
    });

    test('can check less than or equal', function () {
        $a = Money::make(100, 'USD');
        $b = Money::make(100, 'USD');
        $c = Money::make(200, 'USD');

        expect($a->lessThanOrEqual($b))->toBeTrue()
            ->and($a->lessThanOrEqual($c))->toBeTrue();
    });

    test('can compare money', function () {
        $a = Money::make(100, 'USD');
        $b = Money::make(200, 'USD');
        $c = Money::make(100, 'USD');

        expect($a->compare($b))->toBe(-1)
            ->and($b->compare($a))->toBe(1)
            ->and($a->compare($c))->toBe(0);
    });
});

describe('Money State Checks', function () {
    test('can check if zero', function () {
        $zero = Money::zero('USD');
        $nonZero = Money::make(100, 'USD');

        expect($zero->isZero())->toBeTrue()
            ->and($nonZero->isZero())->toBeFalse();
    });

    test('can check if positive', function () {
        $positive = Money::make(100, 'USD');
        $negative = Money::make(-100, 'USD');
        $zero = Money::zero('USD');

        expect($positive->isPositive())->toBeTrue()
            ->and($negative->isPositive())->toBeFalse()
            ->and($zero->isPositive())->toBeFalse();
    });

    test('can check if negative', function () {
        $negative = Money::make(-100, 'USD');
        $positive = Money::make(100, 'USD');

        expect($negative->isNegative())->toBeTrue()
            ->and($positive->isNegative())->toBeFalse();
    });

    test('can check same currency', function () {
        $usd1 = Money::make(100, 'USD');
        $usd2 = Money::make(200, 'USD');
        $eur = Money::make(100, 'EUR');

        expect($usd1->isSameCurrency($usd2))->toBeTrue()
            ->and($usd1->isSameCurrency($eur))->toBeFalse();
    });

    test('can check if divisible by', function () {
        $money = Money::make(100, 'USD');

        expect($money->isDivisibleBy(10))->toBeTrue()
            ->and($money->isDivisibleBy(3))->toBeFalse();
    });
});

describe('Money Getters', function () {
    test('can get minor amount', function () {
        $money = Money::make(10050, 'USD');

        expect($money->getMinorAmount())->toBe(10050);
    });

    test('can get major amount', function () {
        $money = Money::make(10050, 'USD');

        expect($money->getMajorAmount())->toBe(100.50);
    });

    test('can get currency', function () {
        $money = Money::make(100, 'USD');
        $currency = $money->getCurrency();

        expect($currency)->toBeInstanceOf(Currency::class)
            ->and($currency->getCode())->toBe('USD');
    });
});

describe('Money Formatting', function () {
    test('can format simple', function () {
        $money = Money::make(123456, 'USD');

        expect($money->formatSimple())->toBe('$1,234.56');
    });

    test('can format with decimals', function () {
        $money = Money::make(123456, 'USD');

        expect($money->formatByDecimal(2))->toBe('$1,234.56');
    });

    test('formats zero correctly', function () {
        $zero = Money::zero('USD');

        expect($zero->formatSimple())->toBe('$0.00');
    });

    test('formats negative correctly', function () {
        $negative = Money::make(-10050, 'USD');

        expect($negative->formatSimple())->toContain('-');
    });

    test('can format without symbol', function () {
        $money = Money::make(123456, 'USD');

        expect($money->formatWithoutSymbol())->toBe('1,234.56');
    });
});

describe('Money Serialization', function () {
    test('can convert to array', function () {
        $money = Money::make(10000, 'USD');
        $array = $money->toArray();

        expect($array)->toHaveKey('amount')
            ->and($array)->toHaveKey('currency')
            ->and($array)->toHaveKey('formatted')
            ->and($array['amount'])->toBe(10000)
            ->and($array['currency'])->toBe('USD');
    });

    test('can json serialize', function () {
        $money = Money::make(10000, 'USD');
        $json = json_encode($money);

        expect($json)->toBeString()
            ->and($json)->toContain('10000')
            ->and($json)->toContain('USD');
    });

    test('can convert to string', function () {
        $money = Money::make(10000, 'USD');

        expect($money->toString())->toBeString()
            ->and((string) $money)->toBeString();
    });

    test('can get database value', function () {
        $money = Money::make(10000, 'USD');

        expect($money->toDatabaseValue())->toBe(10000);
    });
});

describe('Money Aggregation', function () {
    test('can sum money', function () {
        $moneys = [
            Money::make(100, 'USD'),
            Money::make(200, 'USD'),
            Money::make(300, 'USD'),
        ];

        $total = Money::sum($moneys);

        expect($total->getAmount())->toBe(600);
    });

    test('can average money', function () {
        $moneys = [
            Money::make(100, 'USD'),
            Money::make(200, 'USD'),
            Money::make(300, 'USD'),
        ];

        $avg = Money::average($moneys);

        expect($avg->getAmount())->toBe(200);
    });

    test('can get min money', function () {
        $min = Money::min(
            Money::make(300, 'USD'),
            Money::make(100, 'USD'),
            Money::make(200, 'USD')
        );

        expect($min->getAmount())->toBe(100);
    });

    test('can get max money', function () {
        $max = Money::max(
            Money::make(100, 'USD'),
            Money::make(300, 'USD'),
            Money::make(200, 'USD')
        );

        expect($max->getAmount())->toBe(300);
    });
});

describe('Money Immutability', function () {
    test('operations return new instances', function () {
        $original = Money::make(100, 'USD');
        $result = $original->add(Money::make(50, 'USD'));

        expect($original->getAmount())->toBe(100)
            ->and($result->getAmount())->toBe(150)
            ->and($original)->not->toBe($result);
    });

    test('multiply returns new instance', function () {
        $original = Money::make(100, 'USD');
        $result = $original->multiply(2);

        expect($original->getAmount())->toBe(100)
            ->and($result->getAmount())->toBe(200);
    });
});

describe('Money Edge Cases', function () {
    test('handles very large numbers', function () {
        $large = Money::make('999999999999999', 'USD');
        $result = $large->add(Money::make(1, 'USD'));

        // Note: For very large numbers beyond PHP_INT_MAX, use string comparison
        expect((string) $result->getAmount())->toBe('1000000000000000');
    });

    test('handles precision in multiplication', function () {
        $money = Money::make(333, 'USD');
        $result = $money->multiply(3);

        expect($result->getAmount())->toBe(999);
    });

    test('handles rounding modes', function () {
        $money = Money::make(100, 'USD');
        $result = $money->multiply(1.5, RoundingMode::HALF_UP);

        expect($result->getAmount())->toBe(150);
    });
});
