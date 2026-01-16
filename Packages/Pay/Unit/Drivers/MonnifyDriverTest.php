<?php

declare(strict_types=1);

namespace Tests\Packages\Pay\Unit\Drivers;

use Helpers\Http\Client\Curl;
use Helpers\Http\Client\Response;
use Mockery;
use Money\Money;
use Pay\DataObjects\PaymentData;
use Pay\Drivers\MonnifyDriver;
use Pay\Enums\Status;

describe('MonnifyDriver', function () {
    beforeEach(function () {
        $this->apiKey = 'api_1';
        $this->secretKey = 'sec_1';
        $this->contractCode = 'code_1';
        $this->mockCurl = Mockery::mock(Curl::class);
        $this->driver = new MonnifyDriver($this->apiKey, $this->secretKey, $this->contractCode, true, $this->mockCurl);
    });

    afterEach(function () {
        Mockery::close();
    });

    test('initializes payment correctly', function () {
        $data = new PaymentData(
            amount: Money::amount(1000, 'NGN'),
            email: 'mon@test.com',
            reference: 'mon_1'
        );

        $mockResponse = Mockery::mock(Response::class);

        // 1. Token Call
        $this->mockCurl->shouldReceive('post')
            ->once()
            ->with(Mockery::on(fn ($u) => str_contains($u, 'auth/login')), Mockery::any())
            ->andReturnSelf();
        $this->mockCurl->shouldReceive('withBasicAuth')->once()->with($this->apiKey, $this->secretKey)->andReturnSelf();
        $this->mockCurl->shouldReceive('asJson')->once()->andReturnSelf();
        $tokenResponse = Mockery::mock(Response::class);
        $tokenResponse->shouldReceive('ok')->andReturn(true);
        $tokenResponse->shouldReceive('json')->andReturn(['responseBody' => ['accessToken' => 'mock_token_123']]);
        $this->mockCurl->shouldReceive('send')->once()->andReturn($tokenResponse);

        // 2. Init Call
        $this->mockCurl->shouldReceive('post')
            ->once()
            ->with(Mockery::on(fn ($u) => str_contains($u, 'init-transaction')), Mockery::any())
            ->andReturnSelf();
        $this->mockCurl->shouldReceive('withToken')->once()->with('mock_token_123')->andReturnSelf();
        $this->mockCurl->shouldReceive('asJson')->once()->andReturnSelf();
        $this->mockCurl->shouldReceive('send')->once()->andReturn($mockResponse);

        $mockResponse->shouldReceive('ok')->once()->andReturn(true);
        $mockResponse->shouldReceive('json')->once()->andReturn([
            'responseBody' => [
                'checkoutUrl' => 'https://monnify.com/pay/1',
                'transactionReference' => 'MON_REF_1'
            ]
        ]);

        $response = $this->driver->initialize($data);

        expect($response->authorizationUrl)->toBe('https://monnify.com/pay/1');
    });

    test('verifies payment correctly', function () {
        $mockResponse = Mockery::mock(Response::class);

        // 1. Token Call
        $this->mockCurl->shouldReceive('post')
            ->once()
            ->with(Mockery::on(fn ($u) => str_contains($u, 'auth/login')), Mockery::any())
            ->andReturnSelf();
        $this->mockCurl->shouldReceive('withBasicAuth')->once()->with($this->apiKey, $this->secretKey)->andReturnSelf();
        $this->mockCurl->shouldReceive('asJson')->once()->andReturnSelf();
        $tokenResponse = Mockery::mock(Response::class);
        $tokenResponse->shouldReceive('ok')->andReturn(true);
        $tokenResponse->shouldReceive('json')->andReturn(['responseBody' => ['accessToken' => 'mock_token_123']]);
        $this->mockCurl->shouldReceive('send')->once()->andReturn($tokenResponse);

        // 2. Verify Call
        $this->mockCurl->shouldReceive('get')
            ->once()
            ->with(Mockery::on(fn ($u) => str_contains($u, 'query')))
            ->andReturnSelf();
        $this->mockCurl->shouldReceive('withToken')->once()->with('mock_token_123')->andReturnSelf();
        $this->mockCurl->shouldReceive('send')->once()->andReturn($mockResponse);

        $mockResponse->shouldReceive('ok')->once()->andReturn(true);
        $mockResponse->shouldReceive('json')->once()->andReturn([
            'responseBody' => [
                'paymentStatus' => 'PAID',
                'paymentReference' => 'ref_1',
                'amountPaid' => 1000,
                'currencyCode' => 'NGN',
                'completedOn' => '2023-01-01 10:00:00'
            ]
        ]);

        $response = $this->driver->verify('ref_1');

        expect($response->status)->toBe(Status::SUCCESS);
    });
});
