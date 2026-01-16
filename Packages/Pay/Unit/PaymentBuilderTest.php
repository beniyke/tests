<?php

declare(strict_types=1);

namespace Tests\Packages\Pay\Unit;

use Core\Services\ConfigServiceInterface;
use Mockery;
use Pay\Contracts\PaymentGatewayInterface;
use Pay\DataObjects\PaymentResponse;
use Pay\Enums\Status;
use Pay\PayManager;
use Pay\PaymentBuilder;
use ReflectionClass;

describe('PaymentBuilder', function () {
    beforeEach(function () {
        $this->config = Mockery::mock(ConfigServiceInterface::class);
        $this->config->shouldReceive('get')->with('pay')->andReturn([
            'default' => 'paystack',
            'currency' => 'NGN',
            'drivers' => [
                'paystack' => ['secret_key' => 'sk_test_123']
            ]
        ]);

        $this->manager = new PayManager($this->config);
    });

    afterEach(function () {
        Mockery::close();
    });

    test('it can set properties fluently', function () {
        $builder = new PaymentBuilder($this->manager);

        $builder->amount(100)
            ->email('test@example.com')
            ->reference('ref_123')
            ->callbackUrl('https://example.com/callback')
            ->currency('USD')
            ->metadata(['foo' => 'bar']);

        $reflection = new ReflectionClass($builder);

        $getProperty = function ($name) use ($reflection, $builder) {
            $prop = $reflection->getProperty($name);
            $prop->setAccessible(true);

            return $prop->getValue($builder);
        };

        expect($getProperty('amount'))->toBe(100);
        expect($getProperty('email'))->toBe('test@example.com');
        expect($getProperty('reference'))->toBe('ref_123');
        expect($getProperty('callbackUrl'))->toBe('https://example.com/callback');
        expect($getProperty('currency'))->toBe('USD');
        expect($getProperty('metadata'))->toBe(['foo' => 'bar']);
    });

    test('it can set driver fluently', function () {
        $builder = new PaymentBuilder($this->manager);
        $builder->driver('stripe');

        $reflection = new ReflectionClass($builder);
        $prop = $reflection->getProperty('driver');
        $prop->setAccessible(true);

        expect($prop->getValue($builder))->toBe('stripe');
    });

    test('initialize() calls manager and returns response', function () {
        $mockManager = Mockery::mock(PayManager::class);
        $mockDriver = Mockery::mock(PaymentGatewayInterface::class);
        $mockResponse = new PaymentResponse(
            reference: 'tr_123',
            status: Status::PENDING,
            authorizationUrl: 'https://auth.url'
        );

        $mockManager->shouldReceive('getDefaultCurrency')->andReturn('NGN');
        $mockManager->shouldReceive('driver')->with('stripe')->andReturn($mockDriver);
        $mockDriver->shouldReceive('initialize')->once()->with(Mockery::on(function ($data) {
            return $data->email === 'a@b.com' && $data->amount->getMajorAmount() == 50.0;
        }))->andReturn($mockResponse);

        $builder = new PaymentBuilder($mockManager);
        $result = $builder->amount(50)->email('a@b.com')->driver('stripe')->initialize();

        expect($result)->toBe($mockResponse);
    });
});
