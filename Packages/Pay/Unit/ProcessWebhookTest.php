<?php

declare(strict_types=1);

namespace Tests\Packages\Pay\Unit;

use Helpers\Data;
use Mockery;
use Pay\Jobs\ProcessWebhook;
use Pay\Services\WebhookService;
use Queue\Scheduler;

test('it can be instantiated with array data', function () {
    $data = [
        'signature' => 'test_sig',
        'driver' => 'paystack',
        'content' => ['id' => 1]
    ];

    $job = new ProcessWebhook(Data::make($data));

    expect($job)->toBeInstanceOf(ProcessWebhook::class);
});

test('it implements period method', function () {
    $data = ['driver' => 'paystack'];
    $job = new ProcessWebhook(Data::make($data));

    $scheduler = Mockery::mock(Scheduler::class);

    expect($job->period($scheduler))->toBe($scheduler);
});

test('it executes and calls WebhookService', function () {
    $data = [
        'signature' => 'test_sig',
        'driver' => 'paystack',
        'payload' => '{"event":"charge.success"}'
    ];

    $job = new ProcessWebhook(Data::make($data));

    $webhookService = Mockery::mock(WebhookService::class);
    $webhookService->shouldReceive('handle')
        ->once()
        ->with('paystack', '{"event":"charge.success"}', 'test_sig');

    container()->instance(WebhookService::class, $webhookService);

    expect($job->run()->status)->toBe('success');
});
