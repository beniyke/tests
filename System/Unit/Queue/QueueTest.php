<?php

declare(strict_types=1);

use Helpers\Array\Collections;
use Helpers\Data;
use Queue\QueueManager;
use Queue\Scheduler;
use Tests\System\Helpers\ConfigStub;
use Tests\System\Support\Tasks\FailTask;
use Tests\System\Support\Tasks\SuccessTask;

describe('Queue System', function () {

    beforeEach(function () {
        // Use a local stub for ConfigServiceInterface to avoid global container pollution
        $this->config = new ConfigStub(['timezone' => 'UTC']);

        // Initialize scheduler and queue manager with real dependencies
        $this->scheduler = new Scheduler($this->config);
        $this->queueManager = new QueueManager($this->config, $this->scheduler);
    });

    // Test that a BaseTask returns success status when execute() returns true
    test('BaseTask execution success', function () {
        $task = new SuccessTask(new Data(new Collections(['key' => 'value'])));

        $response = $task->run();
        expect($response->status)->toBe('success')
            ->and($response->message)->toBe('Task succeeded');
    });

    // Test that a BaseTask returns failed status when execute() returns false
    test('BaseTask execution failure', function () {
        $task = new FailTask(new Data(new Collections(['key' => 'value'])));

        $response = $task->run();
        expect($response->status)->toBe('failed')
            ->and($response->message)->toBe('Task failed');
    });

    // Test that QueueManager validates class existence before queueing
    test('QueueManager throws exception for non-existent class', function () {
        $this->queueManager->job('NonExistentClass', []);
    })->throws(RuntimeException::class, 'Class does not exist');

    // Test that QueueManager validates job class implements Queueable interface
    test('QueueManager throws exception for invalid job class', function () {
        $this->queueManager->job(stdClass::class, []);
    })->throws(RuntimeException::class, 'Job class must implement Queueable');
});
