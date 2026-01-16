<?php

declare(strict_types=1);

namespace Tests\Packages\Bridge\Unit;

use Bridge\Contracts\TokenableInterface;
use Bridge\PersonalAccessToken;
use Bridge\Repositories\DatabaseTokenRepository;
use DateTimeImmutable;
use Mockery;
use Testing\Support\DatabaseTestHelper;

beforeEach(function () {
    // Setup test environment with Bridge package migrations
    DatabaseTestHelper::setupTestEnvironment(['Bridge']);

    $this->repository = new DatabaseTokenRepository();
});

afterEach(function () {
    DatabaseTestHelper::dropAllTables();
    DatabaseTestHelper::resetDefaultConnection();
    Mockery::close();
});

describe('DatabaseTokenRepository', function () {
    test('creates token with valid data', function () {
        $tokenable = createMockTokenable(1, 'App\User');

        $token = $this->repository->createToken(
            $tokenable,
            'test-token',
            'hashed-secret',
            ['read', 'write']
        );

        expect($token)->toBeInstanceOf(PersonalAccessToken::class)
            ->and($token->name)->toBe('test-token')
            ->and($token->hashedToken)->toBe('hashed-secret')
            ->and($token->abilities)->toBe(['read', 'write'])
            ->and($token->tokenableType)->toBe('App\User')
            ->and($token->tokenableId)->toBe(1);
    });

    test('creates token with expiration', function () {
        $tokenable = createMockTokenable(1, 'App\User');
        $expiresAt = new DateTimeImmutable('+1 hour');

        $token = $this->repository->createToken(
            $tokenable,
            'test-token',
            'hashed-secret',
            ['*'],
            $expiresAt
        );

        expect($token->expiresAt)->not->toBeNull()
            ->and($token->expiresAt->format('Y-m-d H:i:s'))
            ->toBe($expiresAt->format('Y-m-d H:i:s'));
    });

    test('finds token by id', function () {
        $tokenable = createMockTokenable(1, 'App\User');

        $created = $this->repository->createToken(
            $tokenable,
            'test-token',
            'hashed-secret',
            ['read']
        );

        $found = $this->repository->findToken($created->id);

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($created->id)
            ->and($found->name)->toBe('test-token')
            ->and($found->abilities)->toBe(['read']);
    });

    test('returns null for non-existent token', function () {
        $found = $this->repository->findToken(999);

        expect($found)->toBeNull();
    });

    test('deletes token', function () {
        $tokenable = createMockTokenable(1, 'App\User');

        $token = $this->repository->createToken(
            $tokenable,
            'test-token',
            'hashed-secret',
            ['*']
        );

        $deleted = $this->repository->deleteToken($token->id);

        expect($deleted)->toBeTrue()
            ->and($this->repository->findToken($token->id))->toBeNull();
    });

    test('returns false when deleting non-existent token', function () {
        $deleted = $this->repository->deleteToken(999);

        expect($deleted)->toBeFalse();
    });

    test('finds tokens by tokenable', function () {
        $tokenable = createMockTokenable(1, 'App\User');

        $this->repository->createToken($tokenable, 'token-1', 'hash-1', ['read']);
        $this->repository->createToken($tokenable, 'token-2', 'hash-2', ['write']);
        $this->repository->createToken($tokenable, 'token-3', 'hash-3', ['*']);

        $tokens = $this->repository->findTokensByTokenable($tokenable);

        expect($tokens)->toHaveCount(3)
            ->and($tokens[0]->name)->toBe('token-1')
            ->and($tokens[1]->name)->toBe('token-2')
            ->and($tokens[2]->name)->toBe('token-3');
    });

    test('returns empty array for different user', function () {
        $tokenable1 = createMockTokenable(1, 'App\User');
        $tokenable2 = createMockTokenable(2, 'App\User');

        $this->repository->createToken($tokenable1, 'token-1', 'hash-1', ['*']);

        $tokens = $this->repository->findTokensByTokenable($tokenable2);

        expect($tokens)->toHaveCount(0);
    });

    test('revokes all tokens for tokenable', function () {
        $tokenable = createMockTokenable(1, 'App\User');

        $this->repository->createToken($tokenable, 'token-1', 'hash-1', ['*']);
        $this->repository->createToken($tokenable, 'token-2', 'hash-2', ['*']);
        $this->repository->createToken($tokenable, 'token-3', 'hash-3', ['*']);

        $deleted = $this->repository->revokeAllTokens($tokenable);

        expect($deleted)->toBe(3)
            ->and($this->repository->findTokensByTokenable($tokenable))->toHaveCount(0);
    });
});

/**
 * Helper function to create a mock tokenable.
 */
function createMockTokenable(int|string $id, string $type): TokenableInterface
{
    $mock = Mockery::mock(TokenableInterface::class);
    $mock->shouldReceive('getTokenableId')->andReturn($id);
    $mock->shouldReceive('getTokenableType')->andReturn($type);

    return $mock;
}
