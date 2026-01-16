<?php

declare(strict_types=1);

use Testing\Concerns\RefreshDatabase;
use Tests\System\Fixtures\Models\QueueFailedJob;
use Tests\System\Fixtures\Models\QueueJob;

uses(RefreshDatabase::class);

describe('Queue - Job Dispatching', function () {
    test('dispatches job to queue', function () {
        $payload = json_encode([
            'class' => 'SendEmailJob',
            'data' => ['email' => 'user@example.com', 'message' => 'Hello'],
        ]);

        $job = QueueJob::create([
            'queue' => 'default',
            'payload' => $payload,
            'attempts' => 0,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        expect((int) $job->id)->toBeGreaterThan(0);
        expect($job->queue)->toBe('default');
        expect((int) $job->attempts)->toBe(0);
    });

    test('dispatches job to specific queue', function () {
        $payload = json_encode(['class' => 'ProcessImageJob', 'data' => ['image_id' => 123]]);

        $job = QueueJob::create([
            'queue' => 'images',
            'payload' => $payload,
            'attempts' => 0,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        expect($job->queue)->toBe('images');
    });

    test('dispatches delayed job', function () {
        $delay = 300; // 5 minutes
        $availableAt = time() + $delay;

        $job = QueueJob::create([
            'queue' => 'default',
            'payload' => json_encode(['class' => 'DelayedJob']),
            'attempts' => 0,
            'available_at' => $availableAt,
            'created_at' => time(),
        ]);

        expect($job->available_at)->toBeGreaterThan(time());
    });
});

describe('Queue - Job Processing', function () {
    test('retrieves next available job', function () {
        // Create multiple jobs
        QueueJob::create([
            'queue' => 'default',
            'payload' => json_encode(['class' => 'Job1']),
            'attempts' => 0,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        QueueJob::create([
            'queue' => 'default',
            'payload' => json_encode(['class' => 'Job2']),
            'attempts' => 0,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        // Get next job
        $nextJob = QueueJob::where('queue', 'default')
            ->whereNull('reserved_at')
            ->where('available_at', '<=', time())
            ->orderBy('id', 'asc')
            ->first();

        expect($nextJob)->not->toBeNull();
        expect($nextJob->payload)->toContain('Job1');
    });

    test('reserves job for processing', function () {
        $job = QueueJob::create([
            'queue' => 'default',
            'payload' => json_encode(['class' => 'TestJob']),
            'attempts' => 0,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        // Reserve the job
        $job->reserved_at = time();
        $job->save();

        $reservedJob = QueueJob::find($job->id);
        expect($reservedJob->reserved_at)->not->toBeNull();
    });

    test('increments job attempts', function () {
        $job = QueueJob::create([
            'queue' => 'default',
            'payload' => json_encode(['class' => 'TestJob']),
            'attempts' => 0,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        // Simulate processing attempt
        $job->attempts += 1;
        $job->save();

        $updatedJob = QueueJob::find($job->id);
        expect((int) $updatedJob->attempts)->toBe(1);
    });

    test('deletes job after successful processing', function () {
        $job = QueueJob::create([
            'queue' => 'default',
            'payload' => json_encode(['class' => 'TestJob']),
            'attempts' => 0,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        // Simulate successful processing
        $job->delete();

        $deletedJob = QueueJob::find($job->id);
        expect($deletedJob)->toBeNull();
    });
});

describe('Queue - Job Failures', function () {
    test('moves failed job to failed jobs table', function () {
        $job = QueueJob::create([
            'queue' => 'default',
            'payload' => json_encode(['class' => 'FailingJob']),
            'attempts' => 3,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        // Simulate failure
        $failedJob = QueueFailedJob::create([
            'job_connection' => 'database',
            'queue' => $job->queue,
            'payload' => $job->payload,
            'exception' => 'Exception: Job failed after 3 attempts',
            'failed_at' => date('Y-m-d H:i:s'),
        ]);

        $job->delete();

        expect($failedJob->id)->toBeGreaterThan(0);
        expect(QueueJob::find($job->id))->toBeNull();
        expect(QueueFailedJob::count())->toBe(1);
    });

    test('retries failed job', function () {
        $job = QueueJob::create([
            'queue' => 'default',
            'payload' => json_encode(['class' => 'RetryableJob']),
            'attempts' => 1,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        // Simulate retry
        $job->attempts += 1;
        $job->reserved_at = null;
        $job->available_at = time() + 60; // Retry after 1 minute
        $job->save();

        $retriedJob = QueueJob::find($job->id);
        expect($retriedJob->attempts)->toBe(2);
        expect($retriedJob->reserved_at)->toBeNull();
    });

    test('respects max retry attempts', function () {
        $maxAttempts = 3;

        $job = QueueJob::create([
            'queue' => 'default',
            'payload' => json_encode(['class' => 'TestJob']),
            'attempts' => $maxAttempts,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        // Check if max attempts reached
        $shouldFail = $job->attempts >= $maxAttempts;
        expect($shouldFail)->toBeTrue();
    });
});

describe('Queue - Job Priorities', function () {
    test('processes jobs in order of creation', function () {
        $job1 = QueueJob::create([
            'queue' => 'default',
            'payload' => json_encode(['class' => 'Job1']),
            'attempts' => 0,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        sleep(1);

        $job2 = QueueJob::create([
            'queue' => 'default',
            'payload' => json_encode(['class' => 'Job2']),
            'attempts' => 0,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        $jobs = QueueJob::where('queue', 'default')
            ->orderBy('created_at', 'asc')
            ->get();

        expect((int) $jobs[0]->id)->toBe((int) $job1->id);
        expect((int) $jobs[1]->id)->toBe((int) $job2->id);
    });

    test('processes available jobs only', function () {
        // Job available now
        QueueJob::create([
            'queue' => 'default',
            'payload' => json_encode(['class' => 'AvailableJob']),
            'attempts' => 0,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        // Job available in future
        QueueJob::create([
            'queue' => 'default',
            'payload' => json_encode(['class' => 'FutureJob']),
            'attempts' => 0,
            'available_at' => time() + 3600,
            'created_at' => time(),
        ]);

        $availableJobs = QueueJob::where('queue', 'default')
            ->where('available_at', '<=', time())
            ->get();

        expect($availableJobs)->toHaveCount(1);
        expect($availableJobs[0]->payload)->toContain('AvailableJob');
    });
});

describe('Queue - Complete Workflow', function () {
    test('full job lifecycle', function () {
        // 1. Dispatch job
        $job = QueueJob::create([
            'queue' => 'default',
            'payload' => json_encode(['class' => 'SendEmailJob', 'data' => ['to' => 'user@example.com']]),
            'attempts' => 0,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        expect(QueueJob::count())->toBe(1);

        // 2. Reserve job
        $job->reserved_at = time();
        $job->save();

        // 3. Process job (increment attempts)
        $job->attempts += 1;
        $job->save();

        expect($job->attempts)->toBe(1);

        // 4. Complete job (delete)
        $job->delete();

        expect(QueueJob::count())->toBe(0);
    });

    test('handles job failure and retry', function () {
        // 1. Create job
        $job = QueueJob::create([
            'queue' => 'default',
            'payload' => json_encode(['class' => 'UnstableJob']),
            'attempts' => 0,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        // 2. First attempt fails
        $job->attempts += 1;
        $job->reserved_at = null;
        $job->available_at = time() + 60;
        $job->save();

        expect($job->attempts)->toBe(1);

        // 3. Second attempt fails
        $job->attempts += 1;
        $job->reserved_at = null;
        $job->available_at = time() + 120;
        $job->save();

        expect($job->attempts)->toBe(2);

        // 4. Third attempt succeeds
        $job->delete();

        expect(QueueJob::find($job->id))->toBeNull();
    });

    test('processes multiple queues', function () {
        // Create jobs in different queues
        QueueJob::create([
            'queue' => 'emails',
            'payload' => json_encode(['class' => 'SendEmailJob']),
            'attempts' => 0,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        QueueJob::create([
            'queue' => 'images',
            'payload' => json_encode(['class' => 'ProcessImageJob']),
            'attempts' => 0,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        $emailJobs = QueueJob::where('queue', 'emails')->count();
        $imageJobs = QueueJob::where('queue', 'images')->count();

        expect($emailJobs)->toBe(1);
        expect($imageJobs)->toBe(1);
    });
});
