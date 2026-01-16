<?php

declare(strict_types=1);

namespace Tests\Packages\Pay\Unit\Drivers;

use Helpers\Http\Client\Curl;
use Helpers\Http\Client\Response;
use Mockery;
use Money\Money;
use Pay\DataObjects\PaymentData;
use Pay\Drivers\FlutterwaveDriver;
use Pay\Enums\Status;

describe('FlutterwaveDriver', function () {
    beforeEach(function () {
        $this->secretKey = 'FLWSECK_TEST-123';
        $this->mockCurl = Mockery::mock(Curl::class);
        $this->driver = new FlutterwaveDriver($this->secretKey, '', $this->mockCurl);
    });

    afterEach(function () {
        Mockery::close();
    });

    test('initializes payment correctly', function () {
        $data = new PaymentData(
            amount: Money::amount(2000, 'NGN'),
            email: 'flw@test.com',
            reference: 'flw_tr_1',
            callbackUrl: 'https://flw.com/cb'
        );

        $mockResponse = Mockery::mock(Response::class);

        $this->mockCurl->shouldReceive('post')
            ->once()
            ->with('https://api.flutterwave.com/v3/payments', Mockery::on(function ($payload) {
                return $payload['amount'] === 2000.0;
            }))
            ->andReturnSelf();

        $this->mockCurl->shouldReceive('withToken')->once()->with($this->secretKey)->andReturnSelf();
        $this->mockCurl->shouldReceive('asJson')->once()->andReturnSelf();
        $this->mockCurl->shouldReceive('send')->once()->andReturn($mockResponse);

        $mockResponse->shouldReceive('ok')->once()->andReturn(true);
        $mockResponse->shouldReceive('json')->once()->andReturn([
            'data' => [
                'link' => 'https://flutterwave.com/pay/123',
                'id' => 12345
            ]
        ]);

        $response = $this->driver->initialize($data);

        expect($response->authorizationUrl)->toBe('https://flutterwave.com/pay/123');
        expect($response->providerReference)->toBe('12345');
    });

    test('verifies payment correctly', function () {
        $mockResponse = Mockery::mock(Response::class);

        $this->mockCurl->shouldReceive('get')
            ->once()
            ->with('https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref=flw_tr_1')
            ->andReturnSelf();

        $this->mockCurl->shouldReceive('withToken')->once()->with($this->secretKey)->andReturnSelf();
        $this->mockCurl->shouldReceive('send')->once()->andReturn($mockResponse);

        $mockResponse->shouldReceive('ok')->once()->andReturn(true);
        $mockResponse->shouldReceive('json')->once()->andReturn([
            'data' => [
                'status' => 'successful',
                'tx_ref' => 'flw_tr_1',
                'amount' => 2000.0,
                'currency' => 'NGN',
                'created_at' => '2023-11-11T11:11:11Z'
            ]
        ]);

        $response = $this->driver->verify('flw_tr_1');

        expect($response->status)->toBe(Status::SUCCESS);
    });
});
