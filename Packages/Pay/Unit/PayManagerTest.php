<?php

declare(strict_types=1);

namespace Tests\Packages\Pay\Unit;

use Core\Services\ConfigServiceInterface;
use Mockery;
use Pay\Drivers\PaystackDriver;
use Pay\Drivers\StripeDriver;
use Pay\PayManager;

describe('PayManager', function () {
    test('resolves default driver (paystack)', function () {
        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('pay')->andReturn([
            'default' => 'paystack',
            'drivers' => [
                'paystack' => ['secret_key' => 'sk_test_123']
            ]
        ]);

        $manager = new PayManager($config);
        expect($manager->driver())->toBeInstanceOf(PaystackDriver::class);
    });

    test('resolves stripe driver explicitly', function () {
        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('pay')->andReturn([
            'default' => 'paystack',
            'drivers' => [
                'stripe' => ['secret_key' => 'sk_test_456']
            ]
        ]);

        $manager = new PayManager($config);
        expect($manager->driver('stripe'))->toBeInstanceOf(StripeDriver::class);
    });

    test('resolves monnify driver', function () {
        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('pay')->andReturn([
            'default' => 'paystack',
            'drivers' => [
                'monnify' => [
                    'api_key' => 'test',
                    'secret_key' => 'test',
                    'contract_code' => 'test'
                ]
            ]
        ]);

        $manager = new PayManager($config);
        expect($manager->driver('monnify'))->toBeInstanceOf(\Pay\Drivers\MonnifyDriver::class);
    });

    test('resolves square driver', function () {
        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('pay')->andReturn([
            'default' => 'paystack',
            'drivers' => [
                'square' => ['access_token' => 'test', 'location_id' => 'test']
            ]
        ]);

        $manager = new PayManager($config);
        expect($manager->driver('square'))->toBeInstanceOf(\Pay\Drivers\SquareDriver::class);
    });

    test('resolves opay driver', function () {
        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('pay')->andReturn([
            'default' => 'paystack',
            'drivers' => [
                'opay' => ['public_key' => 'test', 'merchant_id' => 'test']
            ]
        ]);

        $manager = new PayManager($config);
        expect($manager->driver('opay'))->toBeInstanceOf(\Pay\Drivers\OPayDriver::class);
    });

    test('resolves mollie driver', function () {
        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('pay')->andReturn([
            'default' => 'paystack',
            'drivers' => [
                'mollie' => ['api_key' => 'test']
            ]
        ]);

        $manager = new PayManager($config);
        expect($manager->driver('mollie'))->toBeInstanceOf(\Pay\Drivers\MollieDriver::class);
    });

    test('resolves nowpayments driver', function () {
        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('pay')->andReturn([
            'default' => 'paystack',
            'drivers' => [
                'nowpayments' => ['api_key' => 'test']
            ]
        ]);

        $manager = new PayManager($config);
        expect($manager->driver('nowpayments'))->toBeInstanceOf(\Pay\Drivers\NowPaymentsDriver::class);
    });

    test('throws exception for unknown driver', function () {
        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')->with('pay')->andReturn([]);

        $manager = new PayManager($config);

        expect(fn () => $manager->driver('unknown'))
            ->toThrow(\Pay\Exceptions\PaymentException::class);
    });
});
