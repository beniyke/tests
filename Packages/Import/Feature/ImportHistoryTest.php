<?php

declare(strict_types=1);

use App\Models\User;
use Import\Enums\ImportStatus;
use Import\Models\ImportHistory;
use Testing\Support\DatabaseTestHelper;

beforeEach(function () {
    DatabaseTestHelper::setupTestEnvironment(['Import'], true);
});

afterEach(function () {
    DatabaseTestHelper::resetDefaultConnection();
});

describe('ImportHistory Model', function () {

    test('creates import history with required fields', function () {
        $user = User::create(['name' => 'Tester', 'email' => 'imp@example.com', 'password' => 'secret', 'gender' => 'male', 'refid' => 'IMPUSR1']);
        $history = ImportHistory::create([
            'refid' => 'IMP001',
            'user_id' => $user->id,
            'importer_class' => 'App\\Imports\\UserImport',
            'filename' => 'users.csv',
            'original_filename' => 'users.csv',
            'disk' => 'local',
            'path' => '/imports/users.csv',
            'status' => ImportStatus::PENDING,
        ]);

        expect($history)->toBeInstanceOf(ImportHistory::class)
            ->and($history->refid)->toBe('IMP001')
            ->and($history->status)->toBe(ImportStatus::PENDING);
    });

    test('isPending returns correct value', function () {
        $history = new ImportHistory();

        $history->status = ImportStatus::PENDING;
        expect($history->isPending())->toBeTrue();

        $history->status = ImportStatus::COMPLETED;
        expect($history->isPending())->toBeFalse();
    });

    test('isCompleted returns correct value', function () {
        $history = new ImportHistory();

        $history->status = ImportStatus::COMPLETED;
        expect($history->isCompleted())->toBeTrue();

        $history->status = ImportStatus::PENDING;
        expect($history->isCompleted())->toBeFalse();
    });
});

describe('ImportStatus Enum', function () {

    test('has all expected cases', function () {
        $cases = ImportStatus::cases();

        expect($cases)->toHaveCount(5)
            ->and(array_column($cases, 'value'))->toContain(
                'pending',
                'processing',
                'completed',
                'failed',
                'partial'
            );
    });
});
