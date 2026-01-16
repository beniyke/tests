<?php

declare(strict_types=1);

namespace Tests\Packages\Pay\Unit\Drivers;

use Helpers\Http\Client\Curl;
use Helpers\Http\Client\Response;
use Mockery;
use Money\Money;
use Pay\DataObjects\PaymentData;
use Pay\Drivers\PayPalDriver;
use Pay\Enums\Status;

describe('PayPalDriver', function () {
    beforeEach(function () {
        $this->clientId = 'client_1';
        $this->clientSecret = 'secret_1';
        $this->mockCurl = Mockery::mock(Curl::class);
        $this->driver = new PayPalDriver($this->clientId, $this->clientSecret, 'sandbox', $this->mockCurl);
    });

    afterEach(function () {
        Mockery::close();
    });

    test('initializes payment correctly', function () {
        $data = new PaymentData(
            amount: Money::amount(50, 'USD'),
            email: 'pp@test.com',
            reference: 'pp_ref',
            callbackUrl: 'https://pp.test/done'
        );

        $mockResponseToken = Mockery::mock(Response::class);
        $mockResponseOrder = Mockery::mock(Response::class);

        // Token request
        $this->mockCurl->shouldReceive('post')
            ->once()
            ->with('https://api-m.sandbox.paypal.com/v1/oauth2/token', 'grant_type=client_credentials')
            ->andReturnSelf();
        $this->mockCurl->shouldReceive('withBasicAuth')->once()->with($this->clientId, $this->clientSecret)->andReturnSelf();
        $this->mockCurl->shouldReceive('asForm')->once()->andReturnSelf();
        $this->mockCurl->shouldReceive('send')->once()->andReturn($mockResponseToken);

        $mockResponseToken->shouldReceive('ok')->once()->andReturn(true);
        $mockResponseToken->shouldReceive('json')->once()->andReturn(['access_token' => 'TOKEN_123']);

        // Order request
        $this->mockCurl->shouldReceive('post')
            ->once()
            ->with('https://api-m.sandbox.paypal.com/v2/checkout/orders', Mockery::any())
            ->andReturnSelf();
        $this->mockCurl->shouldReceive('withToken')->once()->with('TOKEN_123')->andReturnSelf();
        $this->mockCurl->shouldReceive('withHeader')->once()->with('PayPal-Request-Id', 'pp_ref')->andReturnSelf();
        $this->mockCurl->shouldReceive('asJson')->once()->andReturnSelf();
        $this->mockCurl->shouldReceive('send')->once()->andReturn($mockResponseOrder);

        $mockResponseOrder->shouldReceive('ok')->once()->andReturn(true);
        $mockResponseOrder->shouldReceive('json')->once()->andReturn([
            'id' => 'ORD_123',
            'links' => [
                ['rel' => 'approve', 'href' => 'https://paypal.com/approve/123']
            ]
        ]);

        $response = $this->driver->initialize($data);

        expect($response->authorizationUrl)->toBe('https://paypal.com/approve/123');
        expect($response->providerReference)->toBe('ORD_123');
    });

    test('verifies payment correctly', function () {
        $mockResponse = Mockery::mock(Response::class);
        $tokenResponse = Mockery::mock(Response::class);

        // 1. Access Token Call
        $this->mockCurl->shouldReceive('post')
            ->once()
            ->with(Mockery::on(fn ($u) => str_contains($u, 'v1/oauth2/token')), 'grant_type=client_credentials')
            ->andReturnSelf();
        $this->mockCurl->shouldReceive('withBasicAuth')->andReturnSelf();
        $this->mockCurl->shouldReceive('asForm')->andReturnSelf();
        $this->mockCurl->shouldReceive('send')->once()->andReturn($tokenResponse);
        $tokenResponse->shouldReceive('ok')->andReturn(true);
        $tokenResponse->shouldReceive('json')->andReturn(['access_token' => 'access_mock_123']);

        // 2. Capture Call
        $this->mockCurl->shouldReceive('post')
            ->once()
            ->with(Mockery::on(fn ($u) => str_contains($u, 'capture')), [])
            ->andReturnSelf();
        $this->mockCurl->shouldReceive('withToken')->once()->with('access_mock_123')->andReturnSelf();
        $this->mockCurl->shouldReceive('asJson')->once()->andReturnSelf();
        $this->mockCurl->shouldReceive('send')->once()->andReturn($mockResponse);

        $mockResponse->shouldReceive('ok')->once()->andReturn(true);
        $mockResponse->shouldReceive('json')->once()->andReturn([
            'id' => 'ORD_123',
            'status' => 'COMPLETED',
            'purchase_units' => [
                [
                    'payments' => [
                        'captures' => [
                            [
                                'amount' => ['value' => '100.00', 'currency_code' => 'USD'],
                                'create_time' => '2023-01-01T12:00:00Z'
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $response = $this->driver->verify('ORD_123');

        expect($response->status)->toBe(Status::SUCCESS);
    });
});
