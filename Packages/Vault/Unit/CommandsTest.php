<?php

declare(strict_types=1);

namespace Tests\Packages\Vault\Unit;

use Helpers\File\Adapters\Interfaces\FileManipulationInterface;
use Helpers\File\Adapters\Interfaces\FileMetaInterface;
use Helpers\File\FileSystem;
use Helpers\File\Paths;
use Mockery;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Vault\Commands\AllocateQuotaCommand;
use Vault\Commands\CheckUsageCommand;
use Vault\Commands\CreateBackupCommand;
use Vault\Commands\WipeStorageCommand;
use Vault\Exceptions\InvalidQuotaException;
use Vault\Services\BackupService;
use Vault\Services\VaultManagerService;

describe('AllocateQuotaCommand', function () {
    beforeEach(function () {
        $this->vaultManager = Mockery::mock(VaultManagerService::class);
        $this->command = new AllocateQuotaCommand($this->vaultManager);

        $application = new Application();
        $application->add($this->command);

        $this->commandTester = new CommandTester($this->command);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('allocates quota successfully', function () {
        $this->vaultManager->shouldReceive('allocate')
            ->once()
            ->with('account-123', 1024)
            ->andReturn(null);

        $this->commandTester->execute([
            'account' => 'account-123',
            'quota' => '1024'
        ]);

        expect($this->commandTester->getStatusCode())->toBe(0);
        expect($this->commandTester->getDisplay())->toContain('Successfully allocated');
    });

    it('handles invalid quota error', function () {
        $this->vaultManager->shouldReceive('allocate')
            ->once()
            ->with('account-123', -100)
            ->andThrow(new InvalidQuotaException('Quota must be positive'));

        $this->commandTester->execute([
            'account' => 'account-123',
            'quota' => '-100'
        ]);

        expect($this->commandTester->getStatusCode())->toBe(1);
        expect($this->commandTester->getDisplay())->toContain('Failed to allocate quota');
    });

    it('handles general errors', function () {
        $this->vaultManager->shouldReceive('allocate')
            ->once()
            ->andThrow(new RuntimeException('Database error'));

        $this->commandTester->execute([
            'account' => 'account-123',
            'quota' => '1024'
        ]);

        expect($this->commandTester->getStatusCode())->toBe(1);
        expect($this->commandTester->getDisplay())->toContain('Failed to allocate quota');
    });
});

describe('CheckUsageCommand', function () {
    beforeEach(function () {
        $this->vaultManager = Mockery::mock(VaultManagerService::class);
        $this->command = new CheckUsageCommand($this->vaultManager);

        $application = new Application();
        $application->add($this->command);

        $this->commandTester = new CommandTester($this->command);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('displays usage statistics', function () {
        $this->vaultManager->shouldReceive('getUsage')
            ->once()
            ->with('account-123')
            ->andReturn([
                'used' => 1073741824,
                'quota' => 5368709120,
                'remaining' => 4294967296,
                'percentage' => 20.0
            ]);

        $this->commandTester->execute([
            'account' => 'account-123'
        ]);

        expect($this->commandTester->getStatusCode())->toBe(0);
        $display = $this->commandTester->getDisplay();
        expect($display)->toContain('account-123')
            ->and($display)->toContain('1024')
            ->and($display)->toContain('5120')
            ->and($display)->toContain('20');
    });

    it('warns when storage nearly full', function () {
        $this->vaultManager->shouldReceive('getUsage')
            ->once()
            ->andReturn([
                'used' => 4831838208,
                'quota' => 5368709120,
                'remaining' => 536870912,
                'percentage' => 90.0
            ]);

        $this->commandTester->execute([
            'account' => 'account-123'
        ]);

        expect($this->commandTester->getStatusCode())->toBe(0);
        expect($this->commandTester->getDisplay())->toContain('nearly full');
    });
});

describe('CreateBackupCommand', function () {
    beforeEach(function () {
        $this->backupService = Mockery::mock(BackupService::class);
        $this->command = new CreateBackupCommand($this->backupService);

        $application = new Application();
        $application->add($this->command);

        $this->commandTester = new CommandTester($this->command);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('creates backup successfully', function () {
        $tempFile = Paths::testPath('storage/vault_test_' . uniqid());
        FileSystem::write($tempFile, 'test');

        $this->backupService->shouldReceive('create')
            ->once()
            ->with('account-123')
            ->andReturn($tempFile);

        $this->commandTester->execute([
            'account' => 'account-123'
        ]);

        expect($this->commandTester->getStatusCode())->toBe(0);
        expect($this->commandTester->getDisplay())->toContain('Backup created successfully');

        FileSystem::delete($tempFile);
    });
});

describe('WipeStorageCommand', function () {
    beforeEach(function () {
        $this->vaultManager = Mockery::mock(VaultManagerService::class);
        $this->backupService = Mockery::mock(BackupService::class);
        $this->fileMeta = Mockery::mock(FileMetaInterface::class);
        $this->fileManipulation = Mockery::mock(FileManipulationInterface::class);

        $this->command = new WipeStorageCommand(
            $this->vaultManager,
            $this->backupService,
            $this->fileMeta,
            $this->fileManipulation
        );

        $application = new Application();
        $application->add($this->command);

        $this->commandTester = new CommandTester($this->command);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('wipes storage with force flag', function () {
        $this->vaultManager->shouldReceive('getStoragePath')
            ->once()
            ->with('account-123')
            ->andReturn('/path/to/storage');

        $this->fileMeta->shouldReceive('isDir')
            ->once()
            ->andReturn(false);

        $this->vaultManager->shouldReceive('recalculateUsage')
            ->once()
            ->with('account-123')
            ->andReturn(0);

        $this->commandTester->execute([
            'account' => 'account-123',
            '--force' => true
        ]);

        expect($this->commandTester->getStatusCode())->toBe(0);
        expect($this->commandTester->getDisplay())->toContain('Storage wiped successfully');
    });

    it('creates backup before wiping when requested', function () {
        $this->backupService->shouldReceive('create')
            ->once()
            ->with('account-123')
            ->andReturn('/path/to/backup.zip');

        $this->vaultManager->shouldReceive('getStoragePath')
            ->once()
            ->andReturn('/path/to/storage');

        $this->fileMeta->shouldReceive('isDir')
            ->once()
            ->andReturn(false);

        $this->vaultManager->shouldReceive('recalculateUsage')
            ->once()
            ->andReturn(0);

        $this->commandTester->execute([
            'account' => 'account-123',
            '--backup' => true,
            '--force' => true
        ]);

        expect($this->commandTester->getStatusCode())->toBe(0);
        expect($this->commandTester->getDisplay())->toContain('Backup created');
    });
});
