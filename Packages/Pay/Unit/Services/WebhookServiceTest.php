<?php

declare(strict_types=1);

namespace Tests\Packages\Pay\Unit\Services;

use DateTimeImmutable;
use Exception;
use Helpers\File\Contracts\LoggerInterface;
use Mockery;
use Money\Money;
use Pay\Contracts\PaymentGatewayInterface;
use Pay\DataObjects\VerificationResponse;
use Pay\Enums\Status;
use Pay\Models\PaymentTransaction;
use Pay\PayManager;
use Pay\Services\WebhookService;

beforeEach(function () {
    $this->payManager = Mockery::mock(PayManager::class);
    $this->logger = Mockery::mock(LoggerInterface::class);
    $this->gateway = Mockery::mock(PaymentGatewayInterface::class);

    // Create partial mock for service to stub findTransaction
    $this->service = Mockery::mock(WebhookService::class, [$this->payManager, $this->logger])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
});

afterEach(function () {
    Mockery::close();
});

it('handles successful webhook', function () {
    $payload = '{"id": "evt_123"}';
    $signature = "sig_123";
    $driver = "stripe";
    $reference = "txn_ref_123";

    // Mock Manager resolving driver
    $this->payManager->shouldReceive('driver')
        ->with($driver)
        ->andReturn($this->gateway);

    // Mock Validation Success
    $this->gateway->shouldReceive('validateWebhook')
        ->with($payload, $signature)
        ->andReturn(true);

    // Mock Processing Success
    $verificationResponse = new VerificationResponse(
        reference: $reference,
        status: Status::SUCCESS,
        amount: Money::make(1000, 'USD'),
        paidAt: new DateTimeImmutable(),
        metadata: []
    );

    $this->gateway->shouldReceive('processWebhook')
        ->with(['id' => 'evt_123'])
        ->andReturn($verificationResponse);

    // Mock Transaction Instance with reference property
    $transactionInstance = Mockery::mock(PaymentTransaction::class)->makePartial();
    $transactionInstance->shouldAllowMockingProtectedMethods();
    $transactionInstance->shouldReceive('castAttributeOnGet')->andReturnUsing(function ($key, $value) {
        return $value;
    });
    $transactionInstance->shouldReceive('castAttributeOnSet')->andReturnArg(1);
    $transactionInstance->shouldReceive('update')->andReturn(true);
    $transactionInstance->attributes = ['reference' => $reference];

    // Stub findTransaction
    $this->service->shouldReceive('findTransaction')
        ->with($reference)
        ->andReturn($transactionInstance);

    // Logger should log success and event firing
    $this->logger->shouldReceive('info')
        ->with("Payment Successful event fired for: {$reference}")
        ->once();

    $this->logger->shouldReceive('info')
        ->with("Webhook processed for transaction: {$reference}")
        ->once();

    $this->logger->shouldNotReceive('error');

    $this->service->handle($driver, $payload, $signature);

    expect(true)->toBeTrue();
});

it('handles validation failure', function () {
    $payload = 'raw_payload';
    $signature = "bad_sig";
    $driver = "stripe";

    $this->payManager->shouldReceive('driver')->with($driver)->andReturn($this->gateway);

    $this->gateway->shouldReceive('validateWebhook')
        ->with($payload, $signature)
        ->andReturn(false); // Validation fails

    $this->logger->shouldReceive('error')
        ->with("Webhook validation failed for driver: {$driver}")
        ->once();

    $this->service->handle($driver, $payload, $signature);

    expect(true)->toBeTrue();
});

it('handles processing exception', function () {
    $payload = 'raw_payload';
    $signature = "sig";
    $driver = "stripe";

    $this->payManager->shouldReceive('driver')->with($driver)->andReturn($this->gateway);
    $this->gateway->shouldReceive('validateWebhook')->andReturn(true);

    // Fail at processing
    $this->gateway->shouldReceive('processWebhook')
        ->andThrow(new Exception("Processing error"));

    $this->logger->shouldReceive('error')
        ->with(Mockery::pattern('/Webhook Error \(stripe\): Processing error/'))
        ->once();

    $this->service->handle($driver, $payload, $signature);

    expect(true)->toBeTrue();
});
