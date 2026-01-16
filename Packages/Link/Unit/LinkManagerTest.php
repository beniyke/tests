<?php

declare(strict_types=1);

namespace Tests\Packages\Link\Unit;

use Core\Services\ConfigServiceInterface;
use Database\Connection;
use Database\DB;
use Database\Query\Builder;
use Helpers\DateTimeHelper;
use Link\Exceptions\InvalidLinkException;
use Link\Models\Link;
use Link\Services\LinkManagerService;
use Mockery;

describe('LinkManagerService', function () {
    function setupLinkMocks(): array
    {
        $connection = Mockery::mock(Connection::class);
        $connection->allows('table')->with('audit_log')->andReturn(Mockery::mock(Builder::class)->allows('setModelClass')->andReturnSelf()->getMock());
        DB::setDefaultConnection($connection);

        if (class_exists('Audit\\Services\\AuditManagerService')) {
            $logBuilder = Mockery::mock('Audit\\Services\\Builders\\LogBuilder');
            $logBuilder->shouldReceive('event')->andReturnSelf();
            $logBuilder->shouldReceive('on')->andReturnSelf();
            $logBuilder->shouldReceive('with')->andReturnSelf();
            $logBuilder->shouldReceive('by')->andReturnSelf();
            $logBuilder->shouldReceive('log');

            $auditManager = Mockery::mock('Audit\\Services\\AuditManagerService');
            $auditManager->shouldReceive('make')->andReturn($logBuilder);
            container()->instance('Audit\\Services\\AuditManagerService', $auditManager);
        }

        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')
            ->with('link.default_expiry_hours', 24)
            ->andReturn(24);
        $config->shouldReceive('get')
            ->with('link.token_length', 64)
            ->andReturn(64);
        $config->shouldReceive('get')
            ->with('link.signing_key')
            ->andReturn(null);
        $config->shouldReceive('get')
            ->with('app.key', 'anchor-secret')
            ->andReturn('test-key');
        $config->shouldReceive('get')
            ->with('link.retention_days', 30)
            ->andReturn(30);

        $manager = new LinkManagerService($config);

        return [$connection, $config, $manager];
    }

    afterEach(function () {
        Mockery::close();
        DB::setDefaultConnection(null);
    });

    describe('generateToken()', function () {
        it('generates a token of correct length', function () {
            [,, $manager] = setupLinkMocks();

            $token = $manager->generateToken();

            expect($token)->toBeString()
                ->and(strlen($token))->toBeGreaterThanOrEqual(64);
        });

        it('generates unique tokens', function () {
            [,, $manager] = setupLinkMocks();

            $token1 = $manager->generateToken();
            $token2 = $manager->generateToken();

            expect($token1)->not->toBe($token2);
        });
    });

    describe('hashToken()', function () {
        it('produces consistent hash for same input', function () {
            [,, $manager] = setupLinkMocks();

            $token = 'test-token-123';
            $hash1 = $manager->hashToken($token);
            $hash2 = $manager->hashToken($token);

            expect($hash1)->toBe($hash2);
        });

        it('produces different hashes for different inputs', function () {
            [,, $manager] = setupLinkMocks();

            $hash1 = $manager->hashToken('token-a');
            $hash2 = $manager->hashToken('token-b');

            expect($hash1)->not->toBe($hash2);
        });

        it('produces SHA256 hash', function () {
            [,, $manager] = setupLinkMocks();

            $hash = $manager->hashToken('test');

            expect(strlen($hash))->toBe(64); // SHA256 = 64 hex chars
        });
    });

    describe('create()', function () {
        it('creates a link with default expiry', function () {
            [$connection,, $manager] = setupLinkMocks();

            $builder = Mockery::mock(Builder::class);
            $builder->allows('setModelClass')->andReturnSelf();

            $connection->shouldReceive('table')
                ->with('link')
                ->andReturn($builder);

            $builder->shouldReceive('insertGetId')
                ->once()
                ->andReturn(1);

            $builder->shouldReceive('where')
                ->andReturnSelf();

            $builder->shouldReceive('first')
                ->andReturn((object) [
                    'id' => 1,
                    'refid' => 'test_refid',
                    'token' => 'hashed_token',
                    'scopes' => '["view"]',
                    'use_count' => 0,
                    'expires_at' => DateTimeHelper::now()->addHours(24)->toDateTimeString(),
                ]);

            $link = $manager->create([
                'linkable_type' => 'App\\Models\\Document',
                'linkable_id' => 123,
            ]);

            expect($link)->toBeInstanceOf(Link::class)
                ->and($link->plain_token)->toBeString();
        });

        it('creates a link with custom expiry hours', function () {
            [$connection,, $manager] = setupLinkMocks();

            $builder = Mockery::mock(Builder::class);
            $builder->allows('setModelClass')->andReturnSelf();
            $connection->shouldReceive('table')
                ->with('link')
                ->andReturn($builder);

            $builder->shouldReceive('insertGetId')
                ->once()
                ->andReturn(1);

            $builder->shouldReceive('where')
                ->andReturnSelf();

            $builder->shouldReceive('first')
                ->andReturn((object) [
                    'id' => 1,
                    'refid' => 'test_refid',
                    'token' => 'hashed_token',
                    'scopes' => '["view","download"]',
                    'use_count' => 0,
                    'max_uses' => 5,
                    'expires_at' => DateTimeHelper::now()->addHours(48)->toDateTimeString(),
                ]);

            $link = $manager->create([
                'linkable_type' => 'App\\Models\\Document',
                'linkable_id' => 123,
                'valid_for_hours' => 48,
                'scopes' => ['view', 'download'],
                'max_uses' => 5,
            ]);

            expect($link)->toBeInstanceOf(Link::class);
        });
    });

    describe('validate()', function () {
        it('throws InvalidLinkException for non-existent token', function () {
            [$connection,, $manager] = setupLinkMocks();

            $builder = Mockery::mock(Builder::class);
            $builder->allows('setModelClass')->andReturnSelf();
            $connection->shouldReceive('table')
                ->with('link')
                ->andReturn($builder);

            $builder->shouldReceive('where')
                ->andReturnSelf();

            $builder->shouldReceive('first')
                ->andReturn(null);

            expect(fn () => $manager->validate('invalid-token'))
                ->toThrow(InvalidLinkException::class);
        });
    });

    describe('isValid()', function () {
        it('returns false for invalid token', function () {
            [$connection,, $manager] = setupLinkMocks();

            $builder = Mockery::mock(Builder::class);
            $builder->allows('setModelClass')->andReturnSelf();
            $connection->shouldReceive('table')
                ->with('link')
                ->andReturn($builder);

            $builder->shouldReceive('where')
                ->andReturnSelf();

            $builder->shouldReceive('first')
                ->andReturn(null);

            $result = $manager->isValid('invalid-token');

            expect($result)->toBeFalse();
        });
    });

    describe('verifySignature()', function () {
        it('verifies correct signature', function () {
            [,, $manager] = setupLinkMocks();

            $token = 'test-token';
            $expires = 1735840000;

            // Generate signature
            $signature = hash_hmac('sha256', $token . '|' . $expires, 'test-key');

            $result = $manager->verifySignature($token, $expires, $signature);

            expect($result)->toBeTrue();
        });

        it('rejects incorrect signature', function () {
            [,, $manager] = setupLinkMocks();

            $result = $manager->verifySignature('token', 123456, 'bad-signature');

            expect($result)->toBeFalse();
        });
    });
});
