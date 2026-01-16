<?php

declare(strict_types=1);

namespace Tests\Packages\Pay\Unit;

use Money\Money;
use Pay\DataObjects\PaymentData;
use PHPUnit\Framework\TestCase;
use ValueError;

class PaymentDataTest extends TestCase
{
    public function test_it_can_be_instantiated_with_money()
    {
        $data = new PaymentData(
            amount: Money::amount(100.0, 'USD'),
            email: 'test@example.com'
        );

        $this->assertInstanceOf(Money::class, $data->amount);
        $this->assertEquals(100.0, $data->amount->getMajorAmount());
        $this->assertEquals('USD', $data->amount->getCurrency()->getCode());
    }

    public function test_from_array_creates_money_object()
    {
        $data = PaymentData::fromArray([
            'amount' => 500.0,
            'email' => 'jane@example.com',
            'currency' => 'EUR',
        ]);

        $this->assertInstanceOf(Money::class, $data->amount);
        $this->assertEquals(500.0, $data->amount->getMajorAmount());
        $this->assertEquals('EUR', $data->amount->getCurrency()->getCode());
    }

    public function test_from_array_defaults_to_ngn()
    {
        $data = PaymentData::fromArray([
            'amount' => 100.0,
            'email' => 'test@example.com'
        ]);

        $this->assertEquals('NGN', $data->amount->getCurrency()->getCode());
    }

    public function test_to_array_returns_primitives()
    {
        $data = new PaymentData(
            amount: Money::amount(250.0, 'GBP'),
            email: 'john@example.com'
        );

        $array = $data->toArray();

        $this->assertSame('GBP', $array['currency']);
        $this->assertSame(250.0, (float)$array['amount']);
    }

    public function test_it_throws_error_for_invalid_currency_in_from_array()
    {
        $this->expectException(ValueError::class);

        PaymentData::fromArray([
            'amount' => 100.0,
            'email' => 'test@example.com',
            'currency' => 'INVALID',
        ]);
    }
}
