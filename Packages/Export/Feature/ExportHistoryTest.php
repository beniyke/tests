<?php

declare(strict_types=1);

use App\Models\User;
use Export\Enums\ExportFormat;
use Export\Enums\ExportStatus;
use Export\Models\ExportHistory;
use Testing\Support\DatabaseTestHelper;

beforeEach(function () {
    DatabaseTestHelper::setupTestEnvironment(['Export'], true);
});

afterEach(function () {
    DatabaseTestHelper::resetDefaultConnection();
});

describe('ExportHistory Model', function () {

    test('creates export history with required fields', function () {
        $user = User::create(['name' => 'Tester', 'email' => 'exp@example.com', 'password' => 'secret', 'gender' => 'male', 'refid' => 'EXPUSR1']);
        $history = ExportHistory::create([
            'refid' => 'EXP001',
            'user_id' => $user->id,
            'exporter_class' => 'App\\Exports\\UserExport',
            'format' => ExportFormat::CSV,
            'filename' => 'users.csv',
            'disk' => 'local',
            'status' => ExportStatus::PENDING,
        ]);

        expect($history)->toBeInstanceOf(ExportHistory::class)
            ->and($history->refid)->toBe('EXP001')
            ->and($history->format)->toBe(ExportFormat::CSV)
            ->and($history->status)->toBe(ExportStatus::PENDING);
    });

    test('isPending returns correct value', function () {
        $history = new ExportHistory();

        $history->status = ExportStatus::PENDING;
        expect($history->isPending())->toBeTrue();

        $history->status = ExportStatus::COMPLETED;
        expect($history->isPending())->toBeFalse();
    });

    test('isCompleted returns correct value', function () {
        $history = new ExportHistory();

        $history->status = ExportStatus::COMPLETED;
        expect($history->isCompleted())->toBeTrue();

        $history->status = ExportStatus::PENDING;
        expect($history->isCompleted())->toBeFalse();
    });

    test('isFailed returns correct value', function () {
        $history = new ExportHistory();

        $history->status = ExportStatus::FAILED;
        expect($history->isFailed())->toBeTrue();

        $history->status = ExportStatus::PENDING;
        expect($history->isFailed())->toBeFalse();
    });
});

describe('ExportFormat Enum', function () {

    test('has all expected cases', function () {
        $cases = ExportFormat::cases();

        expect($cases)->toHaveCount(4)
            ->and(array_column($cases, 'value'))->toContain('csv', 'xlsx', 'pdf', 'json');
    });
});

describe('ExportStatus Enum', function () {

    test('has all expected cases', function () {
        $cases = ExportStatus::cases();

        expect($cases)->toHaveCount(4)
            ->and(array_column($cases, 'value'))->toContain(
                'pending',
                'processing',
                'completed',
                'failed'
            );
    });
});
