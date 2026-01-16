<?php

declare(strict_types=1);

namespace Tests\Packages\Bridge\Unit;

use Bridge\Contracts\TokenableInterface;
use Bridge\Contracts\TokenRepositoryInterface;
use Bridge\PersonalAccessToken;
use Bridge\TokenManager;
use Helpers\DateTimeHelper;
use PHPUnit\Framework\TestCase;

class TokenManagerTest extends TestCase
{
    private TokenRepositoryInterface $repository;

    private TokenManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(TokenRepositoryInterface::class);
        $this->manager = new TokenManager($this->repository);
    }

    public function test_create_token_generates_valid_token_format()
    {
        $tokenable = $this->createMockTokenable(1, 'App\User');

        $this->repository->expects($this->once())
            ->method('createToken')
            ->willReturn(new PersonalAccessToken(
                id: 1,
                tokenableType: 'App\User',
                tokenableId: 1,
                name: 'test-token',
                hashedToken: 'hashed',
                abilities: ['*']
            ));

        $token = $this->manager->createToken($tokenable, 'test-token');

        // Token format should be: {id}|{secret}
        $this->assertStringContainsString('|', $token);
        $parts = explode('|', $token);
        $this->assertCount(2, $parts);
        $this->assertEquals('1', $parts[0]);
        $this->assertNotEmpty($parts[1]);
    }

    public function test_authenticate_with_valid_token()
    {
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

        $this->repository->expects($this->once())
            ->method('findToken')
            ->with(1)
            ->willReturn($accessToken);

        $mockUser = $this->createMockTokenable(1, 'App\User');

        $result = $this->manager->authenticate($plainTextToken, function ($type, $id) use ($mockUser) {
            return $mockUser;
        });

        $this->assertSame($mockUser, $result);
    }

    public function test_authenticate_with_invalid_token_format()
    {
        $result = $this->manager->authenticate('invalid-token', fn () => null);

        $this->assertNull($result);
    }

    public function test_authenticate_with_non_existent_token()
    {
        $this->repository->expects($this->once())
            ->method('findToken')
            ->with(999)
            ->willReturn(null);

        $result = $this->manager->authenticate('999|secret', fn () => null);

        $this->assertNull($result);
    }

    public function test_authenticate_with_expired_token()
    {
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

        $this->repository->expects($this->once())
            ->method('findToken')
            ->with(1)
            ->willReturn($accessToken);

        $this->repository->expects($this->once())
            ->method('deleteToken')
            ->with(1);

        $result = $this->manager->authenticate($plainTextToken, fn () => null);

        $this->assertNull($result);
    }

    public function test_authenticate_with_wrong_secret()
    {
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

        $this->repository->expects($this->once())
            ->method('findToken')
            ->with(1)
            ->willReturn($accessToken);

        $result = $this->manager->authenticate($plainTextToken, fn () => null);

        $this->assertNull($result);
    }

    public function test_check_ability_with_valid_token_and_ability()
    {
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

        $this->repository->expects($this->once())
            ->method('findToken')
            ->with(1)
            ->willReturn($accessToken);

        $result = $this->manager->checkAbility($plainTextToken, 'read');

        $this->assertTrue($result);
    }

    public function test_check_ability_with_missing_ability()
    {
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

        $this->repository->expects($this->once())
            ->method('findToken')
            ->with(1)
            ->willReturn($accessToken);

        $result = $this->manager->checkAbility($plainTextToken, 'delete');

        $this->assertFalse($result);
    }

    private function createMockTokenable(int|string $id, string $type): TokenableInterface
    {
        $mock = $this->createMock(TokenableInterface::class);
        $mock->method('getTokenableId')->willReturn($id);
        $mock->method('getTokenableType')->willReturn($type);

        return $mock;
    }
}
