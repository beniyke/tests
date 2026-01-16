<?php

declare(strict_types=1);

use Testing\Fakes\QueueFake;

beforeEach(function () {
    $this->queue = new QueueFake();
});

describe('QueueFake - Job Pushing', function () {
    test('can push a job', function () {
        $this->queue->push('SendEmailJob', ['email' => 'user@example.com']);

        expect($this->queue->all())->toHaveCount(1);
    });

    test('can push job using fluent API', function () {
        $this->queue->job('SendEmailJob', ['id' => 1])->queue();

        expect($this->queue->all())->toHaveCount(1);
    });

    test('can push job to specific queue', function () {
        $this->queue->identifier('emails')->job('SendEmailJob', ['id' => 1])->queue();

        $jobs = $this->queue->all();
        expect($jobs[0]['queue'])->toBe('emails');
    });
});

describe('QueueFake - Assertions', function () {
    test('assertPushed passes when job was pushed', function () {
        $this->queue->push('SendEmailJob', ['id' => 1]);

        // Should not throw
        $this->queue->assertPushed('SendEmailJob');
        expect(true)->toBeTrue();
    });

    test('assertPushed with callback', function () {
        $this->queue->push('SendEmailJob', ['email' => 'test@example.com']);

        $this->queue->assertPushed('SendEmailJob', function ($data) {
            return $data['email'] === 'test@example.com';
        });
        expect(true)->toBeTrue();
    });

    test('assertPushedTimes checks count', function () {
        $this->queue->push('SendEmailJob', ['id' => 1]);
        $this->queue->push('SendEmailJob', ['id' => 2]);

        $this->queue->assertPushedTimes('SendEmailJob', 2);
        expect(true)->toBeTrue();
    });

    test('assertNotPushed passes when job was not pushed', function () {
        $this->queue->push('SendEmailJob');

        $this->queue->assertNotPushed('ProcessImageJob');
        expect(true)->toBeTrue();
    });

    test('assertNothingPushed passes when queue is empty', function () {
        $this->queue->assertNothingPushed();
        expect(true)->toBeTrue();
    });

    test('assertPushedOn checks queue name', function () {
        $this->queue->push('SendEmailJob', ['id' => 1], 'high-priority');

        $this->queue->assertPushedOn('high-priority', 'SendEmailJob');
        expect(true)->toBeTrue();
    });
});

describe('QueueFake - Helper Methods', function () {
    test('pushed returns matching jobs', function () {
        $this->queue->push('SendEmailJob', ['id' => 1]);
        $this->queue->push('SendEmailJob', ['id' => 2]);
        $this->queue->push('ProcessImageJob', ['id' => 3]);

        $emailJobs = $this->queue->pushed('SendEmailJob');
        expect($emailJobs)->toHaveCount(2);
    });

    test('clear removes all jobs', function () {
        $this->queue->push('SendEmailJob');
        $this->queue->push('ProcessImageJob');

        $this->queue->clear();

        expect($this->queue->all())->toBeEmpty();
    });
});
