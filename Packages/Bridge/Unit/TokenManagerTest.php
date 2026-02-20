<?php

declare(strict_types=1);

use Bridge\Contracts\TokenRepositoryInterface;
use Bridge\PersonalAccessToken;
use Bridge\TokenManager;
use Helpers\DateTimeHelper;
use Security\Auth\Contracts\Authenticatable;
use Security\Auth\Contracts\Tokenable;

beforeEach(function () {
    $this->repository = mock(TokenRepositoryInterface::class);
    $this->manager = new TokenManager($this->repository);
});

if (! function_exists('createMockTokenable')) {
    function createMockTokenable(int|string $id, string $type)
    {
        $mock = Mockery::mock(Authenticatable::class . ', ' . Tokenable::class);
        $mock->shouldReceive('getTokenableId')->andReturn($id)->byDefault();
        $mock->shouldReceive('getTokenableType')->andReturn($type)->byDefault();
        $mock->shouldReceive('getAuthId')->andReturn($id)->byDefault();
        $mock->shouldReceive('withAccessToken')->andReturnSelf()->byDefault();

        return $mock;
    }
}

test('create token generates valid token format', function () {
    $tokenable = createMockTokenable(1, 'App\User');

    $this->repository->shouldReceive('createToken')
        ->once()
        ->andReturn(new PersonalAccessToken(
            id: 1,
            tokenableType: 'App\User',
            tokenableId: 1,
            name: 'test-token',
            hashedToken: 'hashed',
            abilities: ['*']
        ));

    $token = $this->manager->createToken($tokenable, 'test-token');

    // Token format should be: {id}|{secret}
    expect($token)->toContain('|');
    $parts = explode('|', $token);
    expect($parts)->toHaveCount(2);
    expect($parts[0])->toBe('1');
    expect($parts[1])->not->toBeEmpty();
});

test('authenticate with valid token', function () {
    $secretToken = 'test-secret-token';
    $hashedToken = hash('sha256', $secretToken);
    $plainTextToken = '1|' . $secretToken;

    $accessToken = new PersonalAccessToken(
        id: 1,
        tokenableType: 'App\User',
        tokenableId: 1,
        name: 'test-token',
        hashedToken: $hashedToken,
        abilities: ['*']
    );

    $this->repository->shouldReceive('findToken')
        ->once()
        ->with(1)
        ->andReturn($accessToken);

    $mockUser = createMockTokenable(1, 'App\User');

    $result = $this->manager->authenticate($plainTextToken, function ($type, $id) use ($mockUser) {
        return $mockUser;
    });

    expect($result)->toBe($mockUser);
});

test('authenticate with invalid token format', function () {
    $result = $this->manager->authenticate('invalid-token', fn () => null);

    expect($result)->toBeNull();
});

test('authenticate with non existent token', function () {
    $this->repository->shouldReceive('findToken')
        ->once()
        ->with(999)
        ->andReturn(null);

    $result = $this->manager->authenticate('999|secret', fn () => null);

    expect($result)->toBeNull();
});

test('authenticate with expired token', function () {
    $secretToken = 'test-secret-token';
    $hashedToken = hash('sha256', $secretToken);
    $plainTextToken = '1|' . $secretToken;

    $accessToken = new PersonalAccessToken(
        id: 1,
        tokenableType: 'App\User',
        tokenableId: 1,
        name: 'test-token',
        hashedToken: $hashedToken,
        abilities: ['*'],
        expiresAt: DateTimeHelper::now()->subHours(1)
    );

    $this->repository->shouldReceive('findToken')
        ->once()
        ->with(1)
        ->andReturn($accessToken);

    $this->repository->shouldReceive('deleteToken')
        ->once()
        ->with(1);

    $result = $this->manager->authenticate($plainTextToken, fn () => null);

    expect($result)->toBeNull();
});

test('authenticate with wrong secret', function () {
    $correctSecret = 'correct-secret';
    $wrongSecret = 'wrong-secret';
    $hashedToken = hash('sha256', $correctSecret);
    $plainTextToken = '1|' . $wrongSecret;

    $accessToken = new PersonalAccessToken(
        id: 1,
        tokenableType: 'App\User',
        tokenableId: 1,
        name: 'test-token',
        hashedToken: $hashedToken,
        abilities: ['*']
    );

    $this->repository->shouldReceive('findToken')
        ->once()
        ->with(1)
        ->andReturn($accessToken);

    $result = $this->manager->authenticate($plainTextToken, fn () => null);

    expect($result)->toBeNull();
});

test('check ability with valid token and ability', function () {
    $secretToken = 'test-secret-token';
    $hashedToken = hash('sha256', $secretToken);
    $plainTextToken = '1|' . $secretToken;

    $accessToken = new PersonalAccessToken(
        id: 1,
        tokenableType: 'App\User',
        tokenableId: 1,
        name: 'test-token',
        hashedToken: $hashedToken,
        abilities: ['read', 'write']
    );

    $this->repository->shouldReceive('findToken')
        ->once()
        ->with(1)
        ->andReturn($accessToken);

    $result = $this->manager->checkAbility($plainTextToken, 'read');

    expect($result)->toBeTrue();
});

test('check ability with missing ability', function () {
    $secretToken = 'test-secret-token';
    $hashedToken = hash('sha256', $secretToken);
    $plainTextToken = '1|' . $secretToken;

    $accessToken = new PersonalAccessToken(
        id: 1,
        tokenableType: 'App\User',
        tokenableId: 1,
        name: 'test-token',
        hashedToken: $hashedToken,
        abilities: ['read']
    );

    $this->repository->shouldReceive('findToken')
        ->once()
        ->with(1)
        ->andReturn($accessToken);

    $result = $this->manager->checkAbility($plainTextToken, 'delete');

    expect($result)->toBeFalse();
});
