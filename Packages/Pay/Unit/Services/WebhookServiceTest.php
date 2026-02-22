<?php

declare(strict_types=1);

namespace Tests\Packages\Pay\Unit\Services;

use DateTimeImmutable;
use Exception;
use Mockery;
use Money\Money;
use Pay\Contracts\PaymentGatewayInterface;
use Pay\DataObjects\VerificationResponse;
use Pay\Enums\Status;
use Pay\Models\PaymentTransaction;
use Pay\PayManager;
use Pay\Services\WebhookService;
use Testing\Concerns\InteractsWithFakes;

beforeEach(function () {
    $this->payManager = Mockery::mock(PayManager::class);
    $this->logger = $this->fakeLog();
    $this->gateway = Mockery::mock(PaymentGatewayInterface::class);

    // Create partial mock for service to stub findTransaction
    $this->service = Mockery::mock(WebhookService::class, [$this->payManager, $this->logger])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
});

uses(InteractsWithFakes::class);

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

    $this->service->handle($driver, $payload, $signature);

    $this->logger->assertLogged('info', fn ($msg) => $msg === "Payment Successful event fired for: {$reference}");
    $this->logger->assertLogged('info', fn ($msg) => $msg === "Webhook processed for transaction: {$reference}");
    $this->logger->assertNotLogged('error');

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

    $this->service->handle($driver, $payload, $signature);

    $this->logger->assertLogged('error', fn ($msg) => $msg === "Webhook validation failed for driver: {$driver}");

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

    $this->service->handle($driver, $payload, $signature);

    $this->logger->assertLogged('error', fn ($msg) => str_contains($msg, "Webhook Error (stripe): Processing error"));

    expect(true)->toBeTrue();
});
