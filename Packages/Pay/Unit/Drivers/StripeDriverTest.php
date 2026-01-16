<?php

declare(strict_types=1);

namespace Tests\Packages\Pay\Unit\Drivers;

use Helpers\Http\Client\Curl;
use Helpers\Http\Client\Response;
use Mockery;
use Money\Money;
use Pay\DataObjects\PaymentData;
use Pay\Drivers\StripeDriver;
use Pay\Enums\Status;

describe('StripeDriver', function () {
    beforeEach(function () {
        $this->secretKey = 'sk_test_stripe';
        $this->mockCurl = Mockery::mock(Curl::class);
        $this->driver = new StripeDriver($this->secretKey, '', $this->mockCurl);
    });

    afterEach(function () {
        Mockery::close();
    });

    test('initializes payment correctly', function () {
        $data = new PaymentData(
            amount: Money::amount(100, 'USD'),
            email: 'user@example.com',
            reference: 'order_1',
            callbackUrl: 'https://example.com/done'
        );

        $mockResponse = Mockery::mock(Response::class);

        $this->mockCurl->shouldReceive('post')
            ->once()
            ->with('https://api.stripe.com/v1/checkout/sessions', Mockery::on(function ($payload) {
                return $payload['line_items'][0]['price_data']['unit_amount'] === 10000;
            }))
            ->andReturnSelf();

        $this->mockCurl->shouldReceive('withToken')->once()->with($this->secretKey)->andReturnSelf();
        $this->mockCurl->shouldReceive('withHeader')->once()->with('Idempotency-Key', 'order_1')->andReturnSelf();
        $this->mockCurl->shouldReceive('asForm')->once()->andReturnSelf();
        $this->mockCurl->shouldReceive('send')->once()->andReturn($mockResponse);

        $mockResponse->shouldReceive('ok')->once()->andReturn(true);
        $mockResponse->shouldReceive('json')->once()->andReturn([
            'url' => 'https://checkout.stripe.com/pay/cs_123',
            'id' => 'cs_123'
        ]);

        $response = $this->driver->initialize($data);

        expect($response->authorizationUrl)->toBe('https://checkout.stripe.com/pay/cs_123');
        expect($response->providerReference)->toBe('cs_123');
    });

    test('verifies payment correctly', function () {
        $mockResponse = Mockery::mock(Response::class);

        $this->mockCurl->shouldReceive('get')
            ->once()
            ->with('https://api.stripe.com/v1/checkout/sessions/cs_123')
            ->andReturnSelf();

        $this->mockCurl->shouldReceive('withToken')->once()->with($this->secretKey)->andReturnSelf();
        $this->mockCurl->shouldReceive('asForm')->once()->andReturnSelf();
        $this->mockCurl->shouldReceive('send')->once()->andReturn($mockResponse);

        $mockResponse->shouldReceive('ok')->once()->andReturn(true);
        $mockResponse->shouldReceive('json')->once()->andReturn([
            'payment_status' => 'paid',
            'amount_total' => 10000,
            'currency' => 'usd',
            'id' => 'cs_123'
        ]);

        $response = $this->driver->verify('cs_123');

        expect($response->status)->toBe(Status::SUCCESS);
        expect($response->amount->getMajorAmount())->toBe(100.0);
    });
});
