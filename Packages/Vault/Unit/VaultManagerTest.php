<?php

declare(strict_types=1);

namespace Tests\Packages\Vault\Unit;

use Database\Connection;
use Database\DB;
use Database\Query\Builder;
use Helpers\File\Adapters\Interfaces\FileManipulationInterface;
use Helpers\File\Adapters\Interfaces\FileMetaInterface;
use Helpers\File\Adapters\Interfaces\PathResolverInterface;
use Mockery;
use Vault\Exceptions\InvalidQuotaException;
use Vault\Exceptions\QuotaExceededException;
use Vault\Exceptions\StorageNotFoundException;
use Vault\Services\FileTrackerService;
use Vault\Services\VaultManagerService;

describe('VaultManagerService', function () {
    function setupVaultMocks()
    {
        $connection = Mockery::mock(Connection::class);
        DB::setDefaultConnection($connection);

        $paths = Mockery::mock(PathResolverInterface::class);
        $fileMeta = Mockery::mock(FileMetaInterface::class);
        $fileManipulation = Mockery::mock(FileManipulationInterface::class);
        $fileTracker = Mockery::mock(FileTrackerService::class);

        $vaultManager = new VaultManagerService(
            $connection,
            $paths,
            $fileMeta,
            $fileManipulation,
            $fileTracker
        );

        return [$connection, $paths, $fileMeta, $fileManipulation, $fileTracker, $vaultManager];
    }

    afterEach(function () {
        Mockery::close();
        DB::setDefaultConnection(null);
    });

    describe('allocate()', function () {
        it('allocates quota for new account', function () {
            [$connection, $paths, $fileMeta, $fileManip, $fileTracker, $vaultManager] = setupVaultMocks();

            $builder = Mockery::mock(Builder::class);

            $connection->shouldReceive('table')
                ->with('vault_quota')
                ->andReturn($builder);

            $builder->shouldReceive('updateOrInsert')
                ->once()
                ->with(
                    ['account_id' => 'account-123'],
                    Mockery::on(function ($data) {
                        return $data['quota_bytes'] === 1073741824 // 1024MB
                            && !empty($data['refid']);
                    })
                )
                ->andReturn(true);



            $vaultManager->allocate('account-123', 1024);
        });

        it('updates quota for existing account', function () {
            [$connection, $paths, $fileMeta, $fileManip, $fileTracker, $vaultManager] = setupVaultMocks();



            $builder = Mockery::mock(Builder::class);

            $connection->shouldReceive('table')
                ->with('vault_quota')
                ->andReturn($builder);

            $builder->shouldReceive('updateOrInsert')
                ->once()
                ->with(
                    ['account_id' => 'account-123'],
                    Mockery::on(function ($data) {
                        return $data['quota_bytes'] === 2147483648; // 2048MB
                    })
                )
                ->andReturn(true);

            $vaultManager->allocate('account-123', 2048);
        });

        it('throws InvalidQuotaException for negative quota', function () {
            $vaultManager = setupVaultMocks()[5];
            expect(fn () => $vaultManager->allocate('account-123', -100))
                ->toThrow(InvalidQuotaException::class);
        });

        it('throws InvalidQuotaException for zero quota', function () {
            $vaultManager = setupVaultMocks()[5];
            expect(fn () => $vaultManager->allocate('account-123', 0))
                ->toThrow(InvalidQuotaException::class);
        });

        it('throws InvalidQuotaException when exceeding max quota', function () {
            $vaultManager = setupVaultMocks()[5];
            expect(fn () => $vaultManager->allocate('account-123', 999999))
                ->toThrow(InvalidQuotaException::class);
        });
    });

    describe('getUsage()', function () {
        it('returns usage statistics', function () {
            [$connection, $paths, $fileMeta, $fileManip, $fileTracker, $vaultManager] = setupVaultMocks();

            $builder = Mockery::mock(Builder::class);

            $connection->shouldReceive('table')
                ->with('vault_quota')
                ->andReturn($builder);

            $builder->shouldReceive('where')
                ->with('account_id', 'account-123')
                ->andReturnSelf();

            $builder->shouldReceive('first')
                ->andReturn((object)[
                    'account_id' => 'account-123',
                    'quota_bytes' => 5368709120, // 5GB
                    'used_bytes' => 1073741824   // 1GB
                ]);

            $usage = $vaultManager->getUsage('account-123');

            expect($usage)->toBeArray()
                ->and($usage['used'])->toBe(1073741824)
                ->and($usage['quota'])->toBe(5368709120)
                ->and($usage['remaining'])->toBe(4294967296)
                ->and($usage['percentage'])->toBe(20.0);
        });

        it('throws StorageNotFoundException for non-existent account', function () {
            [$connection, $paths, $fileMeta, $fileManip, $fileTracker, $vaultManager] = setupVaultMocks();

            $builder = Mockery::mock(Builder::class);

            $connection->shouldReceive('table')
                ->with('vault_quota')
                ->andReturn($builder);

            $builder->shouldReceive('where')
                ->with('account_id', 'invalid-account')
                ->andReturnSelf();

            $builder->shouldReceive('first')
                ->andReturn(null);

            expect(fn () => $vaultManager->getUsage('invalid-account'))
                ->toThrow(StorageNotFoundException::class);
        });
    });

    describe('canUpload()', function () {
        it('returns true when space available', function () {
            [$connection, $paths, $fileMeta, $fileManip, $fileTracker, $vaultManager] = setupVaultMocks();

            $builder = Mockery::mock(Builder::class);

            $connection->shouldReceive('table')
                ->with('vault_quota')
                ->andReturn($builder);

            $builder->shouldReceive('where')
                ->with('account_id', 'account-123')
                ->andReturnSelf();

            $builder->shouldReceive('first')
                ->andReturn((object)[
                    'account_id' => 'account-123',
                    'quota_bytes' => 5368709120,
                    'used_bytes' => 1073741824
                ]);

            $result = $vaultManager->canUpload(104857600, 'account-123'); // 100MB

            expect($result)->toBeTrue();
        });

        it('returns false when quota would be exceeded', function () {
            [$connection, $paths, $fileMeta, $fileManip, $fileTracker, $vaultManager] = setupVaultMocks();

            $builder = Mockery::mock(Builder::class);

            $connection->shouldReceive('table')
                ->with('vault_quota')
                ->andReturn($builder);

            $builder->shouldReceive('where')
                ->with('account_id', 'account-123')
                ->andReturnSelf();

            $builder->shouldReceive('first')
                ->andReturn((object)[
                    'account_id' => 'account-123',
                    'quota_bytes' => 1073741824,
                    'used_bytes' => 1073741824
                ]);

            $result = $vaultManager->canUpload(1, 'account-123');

            expect($result)->toBeFalse();
        });
    });

    describe('trackUpload()', function () {
        it('tracks file upload successfully', function () {
            [$connection, $paths, $fileMeta, $fileManip, $fileTracker, $vaultManager] = setupVaultMocks();

            $connection->shouldReceive('transaction')
                ->once()
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $builder = Mockery::mock(Builder::class);

            $connection->shouldReceive('table')
                ->with('vault_quota')
                ->andReturn($builder);

            $builder->shouldReceive('where')
                ->with('account_id', 'account-123')
                ->andReturnSelf();

            $builder->shouldReceive('lockForUpdate')
                ->andReturnSelf();

            $builder->shouldReceive('first')
                ->andReturn((object)[
                    'account_id' => 'account-123',
                    'quota_bytes' => 5368709120,
                    'used_bytes' => 1073741824
                ]);

            $builder->shouldReceive('update')
                ->once()
                ->andReturn(1);

            $fileTracker->shouldReceive('track')
                ->once()
                ->andReturn(true);

            $vaultManager->trackUpload('file.pdf', 104857600, 'account-123');
        });

        it('throws QuotaExceededException when quota exceeded', function () {
            [$connection, $paths, $fileMeta, $fileManip, $fileTracker, $vaultManager] = setupVaultMocks();

            $connection->shouldReceive('transaction')
                ->once()
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            $builder = Mockery::mock(Builder::class);

            $connection->shouldReceive('table')
                ->with('vault_quota')
                ->andReturn($builder);

            $builder->shouldReceive('where')
                ->with('account_id', 'account-123')
                ->andReturnSelf();

            $builder->shouldReceive('lockForUpdate')
                ->andReturnSelf();

            $builder->shouldReceive('first')
                ->andReturn((object)[
                    'account_id' => 'account-123',
                    'quota_bytes' => 1073741824,
                    'used_bytes' => 1073741824
                ]);

            expect(fn () => $vaultManager->trackUpload('file.pdf', 1, 'account-123'))
                ->toThrow(QuotaExceededException::class);
        });
    });

    describe('trackDeletion()', function () {
        it('tracks file deletion successfully', function () {
            [$connection, $paths, $fileMeta, $fileManip, $fileTracker, $vaultManager] = setupVaultMocks();

            $connection->shouldReceive('transaction')
                ->once()
                ->andReturnUsing(function ($callback) {
                    return $callback();
                });

            // Needs to find file first which uses 'vault_file' table now
            // The original logic was VaultManagerService::trackDeletion -> default was checking vault_file.
            // If we mock DB::table('vault_file'), we need to be careful about calls order.

            // Wait, trackDeletion calls DB::transaction... inside it checks vault_file.
            // But let's check the code:
            // $file = DB::table('vault_file')->where...->first();
            // So we need to mock table('vault_file') too.

            // Creating a separate builder for file table? Or reusing?
            // Usually DB::table returns new builder. Mockery shouldReturn different objects if we want or we can use same mock logic.
            // Let's use flexible mocking.

            $builder = Mockery::mock(Builder::class); // File builder
            $builder->shouldIgnoreMissing($builder); // Allow chaining for generic methods

            $connection->shouldReceive('table')
                ->with('vault_file')
                ->andReturn($builder);

            $builder->shouldReceive('where')
                ->with(Mockery::any(), Mockery::any())
                ->andReturnSelf();

            $builder->shouldReceive('whereNull')
                ->andReturnSelf();

            $builder->shouldReceive('first')
                ->andReturn((object)['file_size' => 104857600]);

            // Then it updates quota
            $quotaBuilder = Mockery::mock(Builder::class);

            $connection->shouldReceive('table')
                ->with('vault_quota')
                ->andReturn($quotaBuilder);

            $quotaBuilder->shouldReceive('where')->with('account_id', 'account-123')->andReturnSelf();
            $quotaBuilder->shouldReceive('lockForUpdate')->andReturnSelf();
            $quotaBuilder->shouldReceive('first')->andReturn((object)[
                'account_id' => 'account-123',
                'quota_bytes' => 5368709120,
                'used_bytes' => 1073741824
            ]);
            $quotaBuilder->shouldReceive('decrement')
                ->once()
                ->with('used_bytes', 104857600, Mockery::type('array'))
                ->andReturn(1);

            $fileTracker->shouldReceive('untrack')->once();

            $vaultManager->trackDeletion('file.pdf', 'account-123');
        });
    });

    describe('recalculateUsage()', function () {
        it('recalculates usage from actual disk usage', function () {
            [$connection, $paths, $fileMeta, $fileManip, $fileTracker, $vaultManager] = setupVaultMocks();

            $paths->shouldReceive('basePath')
                ->once()
                ->andReturn('/path/to/storage/vault');

            $fileMeta->shouldReceive('isDir')
                ->once()
                ->with('/path/to/storage/vault')
                ->andReturn(true);

            $fileMeta->shouldReceive('directorySize')
                ->once()
                ->with('/path/to/storage/vault')
                ->andReturn(104857600); // 100MB

            $connection->shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

            $builder = Mockery::mock(Builder::class);
            $connection->shouldReceive('table')->with('vault_quota')->andReturn($builder);
            $builder->shouldReceive('where')->with('account_id', 'account-123')->andReturnSelf();

            $builder->shouldReceive('update')
                ->once()
                ->with(Mockery::on(function ($data) {
                    return $data['used_bytes'] === 104857600 && !empty($data['updated_at']);
                }));

            $vaultManager->recalculateUsage('account-123');
        });
    });
});
