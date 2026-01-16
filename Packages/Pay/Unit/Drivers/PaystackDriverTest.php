<?php

declare(strict_types=1);

namespace Tests\Packages\Pay\Unit\Drivers;

use Helpers\Http\Client\Curl;
use Helpers\Http\Client\Response;
use Mockery;
use Money\Money;
use Pay\DataObjects\PaymentData;
use Pay\Drivers\PaystackDriver;
use Pay\Enums\Status;

describe('PaystackDriver', function () {
    beforeEach(function () {
        $this->secretKey = 'sk_test_123';
        $this->mockCurl = Mockery::mock(Curl::class);
        $this->driver = new PaystackDriver($this->secretKey, $this->mockCurl);
    });

    afterEach(function () {
        Mockery::close();
    });

    test('initializes payment correctly', function () {
        $data = new PaymentData(
            amount: Money::amount(5000, 'NGN'),
            email: 'test@example.com',
            reference: 'tr_123',
            callbackUrl: 'https://example.com/callback'
        );

        $mockResponse = Mockery::mock(Response::class);

        $this->mockCurl->shouldReceive('post')
            ->once()
            ->with('https://api.paystack.co/transaction/initialize', Mockery::on(function ($payload) {
                return $payload['amount'] === 500000;
            }))
            ->andReturnSelf();

        $this->mockCurl->shouldReceive('withToken')->once()->with($this->secretKey)->andReturnSelf();
        $this->mockCurl->shouldReceive('asJson')->once()->andReturnSelf();
        $this->mockCurl->shouldReceive('send')->once()->andReturn($mockResponse);

        $mockResponse->shouldReceive('ok')->once()->andReturn(true);
        $mockResponse->shouldReceive('json')->once()->andReturn([
            'data' => [
                'authorization_url' => 'https://checkout.paystack.com/auth',
                'access_code' => 'ACC_123'
            ]
        ]);

        $response = $this->driver->initialize($data);

        expect($response->authorizationUrl)->toBe('https://checkout.paystack.com/auth');
        expect($response->providerReference)->toBe('ACC_123');
    });

    test('verifies payment correctly', function () {
        $mockResponse = Mockery::mock(Response::class);

        $this->mockCurl->shouldReceive('get')
            ->once()
            ->with('https://api.paystack.co/transaction/verify/tr_123')
            ->andReturnSelf();

        $this->mockCurl->shouldReceive('withToken')->once()->with($this->secretKey)->andReturnSelf();
        $this->mockCurl->shouldReceive('send')->once()->andReturn($mockResponse);

        $mockResponse->shouldReceive('ok')->once()->andReturn(true);
        $mockResponse->shouldReceive('json')->once()->andReturn([
            'data' => [
                'status' => 'success',
                'reference' => 'tr_123',
                'amount' => 500000,
                'currency' => 'NGN',
                'paid_at' => '2023-10-10T10:10:10Z'
            ]
        ]);

        $response = $this->driver->verify('tr_123');

        expect($response->status)->toBe(Status::SUCCESS);
        expect($response->amount->getMajorAmount())->toBe(5000.0);
    });
});
