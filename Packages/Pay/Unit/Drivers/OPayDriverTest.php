<?php

declare(strict_types=1);

namespace Tests\Packages\Pay\Unit\Drivers;

use Helpers\Http\Client\Curl;
use Helpers\Http\Client\Response;
use Mockery;
use Money\Money;
use Pay\DataObjects\PaymentData;
use Pay\Drivers\OPayDriver;
use Pay\Enums\Status;

describe('OPayDriver', function () {
    beforeEach(function () {
        $this->publicKey = 'pk_1';
        $this->secretKey = 'sk_1';
        $this->merchantId = 'm_1';
        $this->mockCurl = Mockery::mock(Curl::class);
        $this->driver = new OPayDriver($this->publicKey, $this->secretKey, $this->merchantId, 'sandbox', $this->mockCurl);
    });

    afterEach(function () {
        Mockery::close();
    });

    test('initializes payment correctly', function () {
        $data = new PaymentData(
            amount: Money::amount(100, 'NGN'),
            email: 'opay@test.com',
            reference: 'op_1'
        );

        $mockResponse = Mockery::mock(Response::class);

        $this->mockCurl->shouldReceive('post')
            ->once()
            ->with(Mockery::on(fn ($u) => str_contains($u, 'cashier/initialize')), Mockery::any())
            ->andReturnSelf();
        $this->mockCurl->shouldReceive('withHeader')->once()->with('MerchantId', $this->merchantId)->andReturnSelf();
        $this->mockCurl->shouldReceive('withToken')->once()->with($this->publicKey)->andReturnSelf();
        $this->mockCurl->shouldReceive('asJson')->once()->andReturnSelf();
        $this->mockCurl->shouldReceive('send')->once()->andReturn($mockResponse);

        $mockResponse->shouldReceive('ok')->once()->andReturn(true);
        $mockResponse->shouldReceive('json')->once()->andReturn([
            'data' => [
                'cashierUrl' => 'https://opay.com/pay/1',
                'orderNo' => 'OP_1'
            ]
        ]);

        $response = $this->driver->initialize($data);

        expect($response->authorizationUrl)->toBe('https://opay.com/pay/1');
    });

    test('verifies payment correctly', function () {
        $mockResponse = Mockery::mock(Response::class);

        $this->mockCurl->shouldReceive('post')
            ->once()
            ->with(Mockery::on(fn ($u) => str_contains($u, 'cashier/status')), Mockery::any())
            ->andReturnSelf();
        $this->mockCurl->shouldReceive('withHeader')->with('MerchantId', $this->merchantId)->andReturnSelf();
        $this->mockCurl->shouldReceive('withHeader')->with('Signature', Mockery::any())->andReturnSelf();
        $this->mockCurl->shouldReceive('asJson')->andReturnSelf();
        $this->mockCurl->shouldReceive('send')->andReturn($mockResponse);

        $mockResponse->shouldReceive('ok')->once()->andReturn(true);
        $mockResponse->shouldReceive('json')->once()->andReturn([
            'data' => [
                'status' => 'SUCCESS',
                'reference' => 'op_1',
                'amount' => '100.00',
                'currency' => 'NGN'
            ]
        ]);

        $response = $this->driver->verify('op_1');

        expect($response->status)->toBe(Status::SUCCESS);
    });
});
