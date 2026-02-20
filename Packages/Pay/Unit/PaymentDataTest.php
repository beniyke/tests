<?php

declare(strict_types=1);

use Money\Money;
use Pay\DataObjects\PaymentData;

test('it can be instantiated with money', function () {
    $data = new PaymentData(
        amount: Money::amount(100.0, 'USD'),
        email: 'test@example.com'
    );

    expect($data->amount)->toBeInstanceOf(Money::class);
    expect($data->amount->getMajorAmount())->toBe(100.0);
    expect($data->amount->getCurrency()->getCode())->toBe('USD');
});

test('from array creates money object', function () {
    $data = PaymentData::fromArray([
        'amount' => 500.0,
        'email' => 'jane@example.com',
        'currency' => 'EUR',
    ]);

    expect($data->amount)->toBeInstanceOf(Money::class);
    expect($data->amount->getMajorAmount())->toBe(500.0);
    expect($data->amount->getCurrency()->getCode())->toBe('EUR');
});

test('from array defaults to ngn', function () {
    $data = PaymentData::fromArray([
        'amount' => 100.0,
        'email' => 'test@example.com'
    ]);

    expect($data->amount->getCurrency()->getCode())->toBe('NGN');
});

test('to array returns primitives', function () {
    $data = new PaymentData(
        amount: Money::amount(250.0, 'GBP'),
        email: 'john@example.com'
    );

    $array = $data->toArray();

    expect($array['currency'])->toBe('GBP');
    expect((float)$array['amount'])->toBe(250.0);
});

test('it throws error for invalid currency in from array', function () {
    PaymentData::fromArray([
        'amount' => 100.0,
        'email' => 'test@example.com',
        'currency' => 'INVALID',
    ]);
})->throws(ValueError::class);
