<?php

declare(strict_types=1);

namespace Tests\Packages\Bridge\Unit;

use Bridge\PersonalAccessToken;
use Helpers\DateTimeHelper;
use PHPUnit\Framework\TestCase;

class PersonalAccessTokenTest extends TestCase
{
    public function test_can_check_ability_with_wildcard()
    {
        $token = new PersonalAccessToken(
            id: 1,
            tokenableType: 'App\User',
            tokenableId: 1,
            name: 'test-token',
            hashedToken: 'hashed',
            abilities: ['*']
        );

        $this->assertTrue($token->can('read'));
        $this->assertTrue($token->can('write'));
        $this->assertTrue($token->can('delete'));
    }

    public function test_can_check_specific_ability()
    {
        $token = new PersonalAccessToken(
            id: 1,
            tokenableType: 'App\User',
            tokenableId: 1,
            name: 'test-token',
            hashedToken: 'hashed',
            abilities: ['read', 'write']
        );

        $this->assertTrue($token->can('read'));
        $this->assertTrue($token->can('write'));
        $this->assertFalse($token->can('delete'));
    }

    public function test_can_check_empty_abilities_grants_all()
    {
        $token = new PersonalAccessToken(
            id: 1,
            tokenableType: 'App\User',
            tokenableId: 1,
            name: 'test-token',
            hashedToken: 'hashed',
            abilities: []
        );

        $this->assertTrue($token->can('read'));
        $this->assertTrue($token->can('anything'));
    }

    public function test_is_expired_returns_false_when_no_expiry()
    {
        $token = new PersonalAccessToken(
            id: 1,
            tokenableType: 'App\User',
            tokenableId: 1,
            name: 'test-token',
            hashedToken: 'hashed',
            abilities: ['*'],
            expiresAt: null
        );

        $this->assertFalse($token->isExpired());
    }

    public function test_is_expired_returns_true_when_expired()
    {
        $token = new PersonalAccessToken(
            id: 1,
            tokenableType: 'App\User',
            tokenableId: 1,
            name: 'test-token',
            hashedToken: 'hashed',
            abilities: ['*'],
            expiresAt: DateTimeHelper::now()->subHours(1)
        );

        $this->assertTrue($token->isExpired());
    }

    public function test_is_expired_returns_false_when_not_expired()
    {
        $token = new PersonalAccessToken(
            id: 1,
            tokenableType: 'App\User',
            tokenableId: 1,
            name: 'test-token',
            hashedToken: 'hashed',
            abilities: ['*'],
            expiresAt: DateTimeHelper::now()->addHours(1)
        );

        $this->assertFalse($token->isExpired());
    }
}
