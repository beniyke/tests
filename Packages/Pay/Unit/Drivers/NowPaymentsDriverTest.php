<?php

declare(strict_types=1);

namespace Tests\Packages\Pay\Unit\Drivers;

use Helpers\Http\Client\Curl;
use Helpers\Http\Client\Response;
use Mockery;
use Money\Money;
use Pay\DataObjects\PaymentData;
use Pay\Drivers\NowPaymentsDriver;
use Pay\Enums\Status;

describe('NowPaymentsDriver', function () {
    beforeEach(function () {
        $this->apiKey = 'test_key';
        $this->mockCurl = Mockery::mock(Curl::class);
        $this->driver = new NowPaymentsDriver($this->apiKey, '', true, $this->mockCurl);
    });

    afterEach(function () {
        Mockery::close();
    });

    test('initializes payment correctly', function () {
        $data = new PaymentData(
            amount: Money::amount(100, 'USD'),
            email: 'now@test.com',
            reference: 'now_1',
            callbackUrl: 'https://now.com/cb'
        );

        $mockResponse = Mockery::mock(Response::class);

        $this->mockCurl->shouldReceive('post')
            ->once()
            ->with(Mockery::on(fn ($u) => str_contains($u, 'payment')), Mockery::any())
            ->andReturnSelf();
        $this->mockCurl->shouldReceive('withHeader')->once()->with('x-api-key', $this->apiKey)->andReturnSelf();
        $this->mockCurl->shouldReceive('asJson')->once()->andReturnSelf();
        $this->mockCurl->shouldReceive('send')->once()->andReturn($mockResponse);

        $mockResponse->shouldReceive('ok')->once()->andReturn(true);
        $mockResponse->shouldReceive('json')->once()->andReturn([
            'invoice_url' => 'https://nowpayments.io/pay/1',
            'id' => 'NOW_123'
        ]);

        $response = $this->driver->initialize($data);

        expect($response->authorizationUrl)->toBe('https://nowpayments.io/pay/1');
        expect($response->providerReference)->toBe('NOW_123');
    });

    test('verifies payment correctly', function () {
        $mockResponse = Mockery::mock(Response::class);

        $this->mockCurl->shouldReceive('get')
            ->once()
            ->with(Mockery::on(fn ($u) => str_contains($u, 'payment/NOW_123')))
            ->andReturnSelf();
        $this->mockCurl->shouldReceive('withHeader')->once()->with('x-api-key', $this->apiKey)->andReturnSelf();
        $this->mockCurl->shouldReceive('send')->once()->andReturn($mockResponse);

        $mockResponse->shouldReceive('ok')->once()->andReturn(true);
        $mockResponse->shouldReceive('json')->once()->andReturn([
            'payment_status' => 'finished',
            'id' => 'NOW_123',
            'order_id' => 'now_1',
            'pay_amount' => 100,
            'price_amount' => 100,
            'price_currency' => 'usd',
            'purchase_id' => 'P_1',
            'updated_at' => '2023-01-01T12:00:00Z'
        ]);

        $response = $this->driver->verify('NOW_123');

        expect($response->status)->toBe(Status::SUCCESS);
    });
});
