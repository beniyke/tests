<?php

declare(strict_types=1);

use Bridge\ApiAuth\Validators\AuthTokenValidator;
use Bridge\Contracts\TokenableInterface;
use Bridge\TokenManager;

describe('AuthTokenValidator', function () {
    beforeEach(function () {
        $this->tokenManager = Mockery::mock(TokenManager::class);
        $this->validator = new AuthTokenValidator($this->tokenManager);
    });

    afterEach(function () {
        Mockery::close();
    });

    test('validates token and returns tokenable', function () {
        $token = '1|secret-token';
        $user = Mockery::mock(TokenableInterface::class);

        $this->tokenManager->shouldReceive('authenticate')
            ->once()
            ->with($token, Mockery::type('callable'))
            ->andReturn($user);

        $result = $this->validator->validate($token);

        expect($result)->toBe($user);
    });

    test('returns null for invalid token', function () {
        $token = '1|invalid-token';

        $this->tokenManager->shouldReceive('authenticate')
            ->once()
            ->with($token, Mockery::type('callable'))
            ->andReturn(null);

        $result = $this->validator->validate($token);

        expect($result)->toBeNull();
    });

    test('validates token with specific abilities', function () {
        $token = '1|secret-token';
        $user = Mockery::mock(TokenableInterface::class);
        $abilities = ['user:read', 'user:write'];

        $this->tokenManager->shouldReceive('authenticate')
            ->once()
            ->with($token, Mockery::type('callable'))
            ->andReturn($user);

        $this->tokenManager->shouldReceive('checkAbility')
            ->with($token, 'user:read')
            ->andReturn(true);

        $this->tokenManager->shouldReceive('checkAbility')
            ->with($token, 'user:write')
            ->andReturn(true);

        $result = $this->validator->validate($token, $abilities);

        expect($result)->toBe($user);
    });

    test('returns null if token lacks required ability', function () {
        $token = '1|secret-token';
        $user = Mockery::mock(TokenableInterface::class);
        $abilities = ['user:read', 'user:delete'];

        $this->tokenManager->shouldReceive('authenticate')
            ->once()
            ->with($token, Mockery::type('callable'))
            ->andReturn($user);

        $this->tokenManager->shouldReceive('checkAbility')
            ->with($token, 'user:read')
            ->andReturn(true);

        $this->tokenManager->shouldReceive('checkAbility')
            ->with($token, 'user:delete')
            ->andReturn(false);

        $result = $this->validator->validate($token, $abilities);

        expect($result)->toBeNull();
    });

    test('accepts wildcard abilities', function () {
        $token = '1|secret-token';
        $user = Mockery::mock(TokenableInterface::class);

        $this->tokenManager->shouldReceive('authenticate')
            ->once()
            ->with($token, Mockery::type('callable'))
            ->andReturn($user);

        // Should not check abilities when wildcard is present
        $this->tokenManager->shouldNotReceive('checkAbility');

        $result = $this->validator->validate($token, ['*']);

        expect($result)->toBe($user);
    });
});
