<?php

declare(strict_types=1);

namespace Tests\Packages\Pay\Unit\Drivers;

use Helpers\Http\Client\Curl;
use Helpers\Http\Client\Response;
use Mockery;
use Money\Money;
use Pay\DataObjects\PaymentData;
use Pay\Drivers\SquareDriver;
use Pay\Enums\Status;

describe('SquareDriver', function () {
    beforeEach(function () {
        $this->accessToken = 'EAAA-TEST';
        $this->locationId = 'LOC_1';
        $this->mockCurl = Mockery::mock(Curl::class);
        $this->driver = new SquareDriver($this->accessToken, $this->locationId, '', true, $this->mockCurl);
    });

    afterEach(function () {
        Mockery::close();
    });

    test('initializes payment correctly', function () {
        $data = new PaymentData(
            amount: Money::amount(15.50, 'USD'),
            email: 'sq@test.com',
            reference: 'sq_1'
        );

        $mockResponse = Mockery::mock(Response::class);

        $this->mockCurl->shouldReceive('post')
            ->once()
            ->with(Mockery::on(fn ($u) => str_contains($u, 'payment-links')), Mockery::on(function ($payload) {
                return $payload['order']['line_items'][0]['base_price_money']['amount'] === 1550;
            }))
            ->andReturnSelf();

        $this->mockCurl->shouldReceive('withToken')->once()->with($this->accessToken)->andReturnSelf();
        $this->mockCurl->shouldReceive('asJson')->once()->andReturnSelf();
        $this->mockCurl->shouldReceive('send')->once()->andReturn($mockResponse);

        $mockResponse->shouldReceive('ok')->once()->andReturn(true);
        $mockResponse->shouldReceive('json')->once()->andReturn([
            'payment_link' => [
                'url' => 'https://square.link/u/123',
                'id' => 'PL_1',
                'order_id' => 'ORD_SQ_1'
            ]
        ]);

        $response = $this->driver->initialize($data);

        expect($response->authorizationUrl)->toBe('https://square.link/u/123');
        expect($response->providerReference)->toBe('PL_1');
    });

    test('verifies payment correctly', function () {
        $mockResponse = Mockery::mock(Response::class);

        $this->mockCurl->shouldReceive('get')
            ->once()
            ->with(Mockery::on(fn ($u) => str_contains($u, 'orders/ORD_SQ_1')))
            ->andReturnSelf();

        $this->mockCurl->shouldReceive('withToken')->once()->with($this->accessToken)->andReturnSelf();
        $this->mockCurl->shouldReceive('send')->once()->andReturn($mockResponse);

        $mockResponse->shouldReceive('ok')->once()->andReturn(true);
        $mockResponse->shouldReceive('json')->once()->andReturn([
            'order' => [
                'id' => 'ORD_SQ_1',
                'state' => 'COMPLETED',
                'total_money' => ['amount' => 1550, 'currency' => 'USD'],
                'closed_at' => '2023-01-01T12:00:00Z'
            ]
        ]);

        $response = $this->driver->verify('ORD_SQ_1');

        expect($response->status)->toBe(Status::SUCCESS);
    });
});
