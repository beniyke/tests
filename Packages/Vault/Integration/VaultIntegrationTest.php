<?php

declare(strict_types=1);

namespace Tests\Packages\Vault\Integration;

use Core\Ioc\Container;
use Database\DB;
use Testing\Support\DatabaseTestHelper;
use Vault\Exceptions\QuotaExceededException;
use Vault\Providers\VaultServiceProvider;
use Vault\Services\BackupService;
use Vault\Services\FileTrackerService;
use Vault\Services\VaultManagerService;

describe('Vault Integration', function () {
    beforeEach(function () {
        // Clear container cache for test isolation
        Container::getInstance()->forgetCachedInstance(VaultManagerService::class);
        Container::getInstance()->forgetCachedInstance(BackupService::class);

        // Register VaultServiceProvider
        $this->container = Container::getInstance();
        $this->container->registerProvider(VaultServiceProvider::class);

        // Setup Test Environment (Schema + Migrations)
        DatabaseTestHelper::setupTestEnvironment(['Vault'], true);
    });

    it('complete upload workflow', function () {
        $vaultManager = resolve(VaultManagerService::class);
        $accountId = 'test-account-' . uniqid();

        // 1. Allocate quota
        $vaultManager->allocate($accountId, 100); // 100MB

        // 2. Check usage (should be 0)
        $usage = $vaultManager->getUsage($accountId);
        expect($usage['used'])->toBe(0)
            ->and($usage['quota'])->toBe(104857600)
            ->and($usage['percentage'])->toBe(0.0);

        // 3. Track upload
        $vaultManager->trackUpload($accountId, 'test.pdf', 10485760); // 10MB

        // 4. Check usage again
        $usage = $vaultManager->getUsage($accountId);
        expect($usage['used'])->toBe(10485760)
            ->and($usage['percentage'])->toBe(10.0);

        // 5. Check if can upload more
        expect($vaultManager->canUpload($accountId, 52428800))->toBeTrue(); // 50MB - OK
        expect($vaultManager->canUpload($accountId, 104857600))->toBeFalse(); // 100MB - Exceeds

        // 6. Track deletion
        $vaultManager->trackDeletion($accountId, 'test.pdf');

        // 7. Usage should be back to 0
        $usage = $vaultManager->getUsage($accountId);
        expect($usage['used'])->toBe(0);
    });

    it('quota enforcement prevents over-allocation', function () {
        $vaultManager = resolve(VaultManagerService::class);
        $accountId = 'test-account-' . uniqid();

        $vaultManager->allocate($accountId, 50); // 50MB

        $vaultManager->trackUpload($accountId, 'file1.pdf', 52428800); // 50MB

        expect(fn () => $vaultManager->trackUpload($accountId, 'file2.pdf', 1))
            ->toThrow(QuotaExceededException::class);
    });

    it('backup and restore workflow', function () {
        $backupService = resolve(BackupService::class);
        $vaultManager = resolve(VaultManagerService::class);
        $accountId = 'test-account-' . uniqid();

        // Setup account with files
        $vaultManager->allocate($accountId, 100);
        $storagePath = $vaultManager->getStoragePath($accountId);

        // Create test file
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }
        file_put_contents($storagePath . '/test.txt', 'Test content');

        // Create backup
        $backupPath = $backupService->create($accountId);
        expect(file_exists($backupPath))->toBeTrue();

        // Verify backup was tracked
        $backups = $backupService->list($accountId);
        expect($backups)->toHaveCount(1);

        // Cleanup
        if (file_exists($backupPath)) {
            unlink($backupPath);
        }
        if (file_exists($storagePath . '/test.txt')) {
            unlink($storagePath . '/test.txt');
        }
    });

    it('recalculate usage fixes discrepancies', function () {
        $vaultManager = resolve(VaultManagerService::class);
        $accountId = 'test-account-' . uniqid();

        $vaultManager->allocate($accountId, 100);

        // Manually corrupt the usage data
        DB::table('vault_quota')
            ->where('account_id', $accountId)
            ->update(['used_bytes' => 999999]);

        // Recalculate should fix it
        $actualBytes = $vaultManager->recalculateUsage($accountId);

        $usage = $vaultManager->getUsage($accountId);
        expect($usage['used'])->toBe($actualBytes);
    });

    it('file tracking with deduplication', function () {
        $fileTracker = resolve(FileTrackerService::class);
        $accountId = 'test-account-' . uniqid();

        // Track same file twice
        $fileTracker->track($accountId, 'file1.pdf', 1024, 'hash123');
        $fileTracker->track($accountId, 'file2.pdf', 1024, 'hash123');

        // Find duplicates
        $duplicates = $fileTracker->findDuplicates('hash123');
        expect($duplicates)->toHaveCount(2);

        // Get file count
        $count = $fileTracker->getFileCount($accountId);
        expect($count)->toBe(2);
    });
});
