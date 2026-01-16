<?php

declare(strict_types=1);

namespace Tests\Packages\Pay\Unit\Drivers;

use Helpers\Http\Client\Curl;
use Helpers\Http\Client\Response;
use Mockery;
use Money\Money;
use Pay\DataObjects\PaymentData;
use Pay\Drivers\MollieDriver;
use Pay\Enums\Status;

describe('MollieDriver', function () {
    beforeEach(function () {
        $this->apiKey = 'test_123';
        $this->mockCurl = Mockery::mock(Curl::class);
        $this->driver = new MollieDriver($this->apiKey, $this->mockCurl);
    });

    afterEach(function () {
        Mockery::close();
    });

    test('initializes payment correctly', function () {
        $data = new PaymentData(
            amount: Money::amount(10, 'EUR'),
            email: 'mollie@test.com',
            reference: 'mol_1',
            callbackUrl: 'https://mol.com/cb'
        );

        $mockResponse = Mockery::mock(Response::class);

        $this->mockCurl->shouldReceive('post')
            ->once()
            ->with(Mockery::on(fn ($u) => str_contains($u, 'payments')), Mockery::any())
            ->andReturnSelf();
        $this->mockCurl->shouldReceive('withToken')->once()->with($this->apiKey)->andReturnSelf();
        $this->mockCurl->shouldReceive('asJson')->once()->andReturnSelf();
        $this->mockCurl->shouldReceive('send')->once()->andReturn($mockResponse);

        $mockResponse->shouldReceive('ok')->once()->andReturn(true);
        $mockResponse->shouldReceive('json')->once()->andReturn([
            '_links' => ['checkout' => ['href' => 'https://mollie.com/checkout/1']],
            'id' => 'tr_123'
        ]);

        $response = $this->driver->initialize($data);

        expect($response->authorizationUrl)->toBe('https://mollie.com/checkout/1');
        expect($response->providerReference)->toBe('tr_123');
    });

    test('verifies payment correctly', function () {
        $mockResponse = Mockery::mock(Response::class);

        $this->mockCurl->shouldReceive('get')
            ->once()
            ->with(Mockery::on(fn ($u) => str_contains($u, 'payments/tr_123')))
            ->andReturnSelf();
        $this->mockCurl->shouldReceive('withToken')->once()->with($this->apiKey)->andReturnSelf();
        $this->mockCurl->shouldReceive('send')->once()->andReturn($mockResponse);

        $mockResponse->shouldReceive('ok')->once()->andReturn(true);
        $mockResponse->shouldReceive('json')->once()->andReturn([
            'status' => 'paid',
            'id' => 'tr_123',
            'amount' => ['value' => '10.00', 'currency' => 'EUR']
        ]);

        $response = $this->driver->verify('tr_123');

        expect($response->status)->toBe(Status::SUCCESS);
    });
});
