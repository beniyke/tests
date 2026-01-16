<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Feature tests for the Audit package.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

use Audit\Audit;
use Audit\Models\AuditLog;
use Helpers\DateTimeHelper;
use Testing\Support\DatabaseTestHelper;

beforeEach(function () {
    DatabaseTestHelper::setupTestEnvironment(['Audit'], true);
});

afterEach(function () {
    DatabaseTestHelper::resetDefaultConnection();
});

describe('Manual Audit Logging', function () {
    test('can log a manual event', function () {
        $log = Audit::log('custom.event', [
            'metadata' => ['action' => 'test'],
        ]);

        expect($log)->toBeInstanceOf(AuditLog::class)
            ->and($log->event)->toBe('custom.event')
            ->and($log->metadata)->toBe(['action' => 'test']);
    });

    test('can log event with old and new values', function () {
        $log = Audit::log('data.changed', [
            'old_values' => ['status' => 'pending'],
            'new_values' => ['status' => 'approved'],
        ]);

        $changes = $log->getChanges();

        expect($changes)->toHaveKey('status')
            ->and($changes['status']['old'])->toBe('pending')
            ->and($changes['status']['new'])->toBe('approved');
    });

    test('generates unique refid for each log', function () {
        $log1 = Audit::log('event1');
        $log2 = Audit::log('event2');

        expect($log1->refid)->not->toBe($log2->refid);
    });
});

describe('Log Retrieval', function () {
    test('can get recent logs', function () {
        Audit::log('event1');
        Audit::log('event2');
        Audit::log('event3');

        $recent = Audit::recent(2);

        expect($recent)->toHaveCount(2);
    });

    test('can filter logs by event', function () {
        Audit::log('login');
        Audit::log('logout');
        Audit::log('login');

        $loginLogs = AuditLog::where('event', 'login')->get();

        expect($loginLogs)->toHaveCount(2);
    });
});

describe('Checksum Verification', function () {
    test('generates checksum for logs', function () {
        $log = Audit::log('secure.event');

        expect($log->checksum)->not->toBeNull()
            ->and(strlen($log->checksum))->toBe(64); // SHA-256 produces 64 hex chars
    });

    test('verifies valid checksum', function () {
        $log = Audit::log('verified.event');

        $isValid = Audit::verify($log);

        expect($isValid)->toBeTrue();
    });

    test('detects tampered logs', function () {
        $log = Audit::log('secure.event');

        // Tamper with the log
        $log->event = 'tampered.event';

        $isValid = Audit::verify($log);

        expect($isValid)->toBeFalse();
    });
});

describe('Cleanup', function () {
    test('can cleanup old logs', function () {
        // Create logs with old dates
        AuditLog::create([
            'refid' => 'old1',
            'event' => 'old.event',
            'created_at' => DateTimeHelper::now()->subDays(100),
        ]);

        AuditLog::create([
            'refid' => 'old2',
            'event' => 'old.event',
            'created_at' => DateTimeHelper::now()->subDays(100),
        ]);

        AuditLog::create([
            'refid' => 'recent1',
            'event' => 'recent.event',
            'created_at' => DateTimeHelper::now(),
        ]);

        $deleted = Audit::cleanup(30);

        expect($deleted)->toBe(2);
        expect(AuditLog::count())->toBe(1);
    });
});

describe('Export', function () {
    test('can export logs to CSV', function () {
        Audit::log('export.test1');
        Audit::log('export.test2');

        $csv = Audit::export([], 'csv');

        expect($csv)->toContain('export.test1')
            ->and($csv)->toContain('export.test2')
            ->and($csv)->toContain('id,refid,user_id,event');
    });

    test('can export logs to JSON', function () {
        Audit::log('json.test');

        $json = Audit::export([], 'json');
        $data = json_decode($json, true);

        expect($data)->toBeArray()
            ->and($data[0]['event'])->toBe('json.test');
    });
});

describe('AuditLog Model', function () {
    test('isCreate returns true for created events', function () {
        $log = Audit::log('created');

        expect($log->isCreate())->toBeTrue()
            ->and($log->isUpdate())->toBeFalse()
            ->and($log->isDelete())->toBeFalse();
    });

    test('isUpdate returns true for updated events', function () {
        $log = Audit::log('updated');

        expect($log->isUpdate())->toBeTrue();
    });

    test('isDelete returns true for deleted events', function () {
        $log = Audit::log('deleted');

        expect($log->isDelete())->toBeTrue();
    });
});
