<?php

declare(strict_types=1);

namespace Tests\Packages\Bridge\Feature;

use Bridge\Repositories\DatabaseTokenRepository;
use Bridge\TokenManager;
use Tests\Packages\Bridge\Support\TestUser;

describe('ApiToken Feature', function () {

    beforeEach(function () {
        $this->runAppMigrations();
        $this->bootPackage('Bridge', runMigrations: true);
        $this->repository = new DatabaseTokenRepository();
        $this->tokenManager = new TokenManager($this->repository);
    });

    test('complete token lifecycle', function () {
        $user = TestUser::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'gender' => 'male',
            'refid' => 'REF123',
            'password' => 'password',
        ]);

        $plainTextToken = $this->tokenManager->createToken(
            $user,
            'mobile-app',
            ['read', 'write'],
            3600
        );

        expect($plainTextToken)->toContain('|');
        [$tokenId, $secret] = explode('|', $plainTextToken);
        expect($tokenId)->toBeNumeric()
            ->and($secret)->not->toBeEmpty();

        // 3. Authenticate with the token
        $authenticatedUser = $this->tokenManager->authenticate($plainTextToken, function ($type, $id) {
            return TestUser::find($id);
        });

        expect($authenticatedUser)->not->toBeNull()
            ->and((int) $authenticatedUser->id)->toBe((int) $user->id)
            ->and($authenticatedUser->name)->toBe('John Doe');

        // 4. Check abilities
        $canRead = $this->tokenManager->checkAbility($plainTextToken, 'read');
        $canWrite = $this->tokenManager->checkAbility($plainTextToken, 'write');
        $canDelete = $this->tokenManager->checkAbility($plainTextToken, 'delete');

        expect($canRead)->toBeTrue()
            ->and($canWrite)->toBeTrue()
            ->and($canDelete)->toBeFalse();

        // 5. Revoke the token
        $token = $this->repository->findToken((int) $tokenId);
        $this->repository->deleteToken($token->id);

        // 6. Verify authentication fails after revocation
        $authenticatedAfterRevoke = $this->tokenManager->authenticate($plainTextToken, function ($type, $id) {
            return TestUser::find($id);
        });

        expect($authenticatedAfterRevoke)->toBeNull();
    });

    test('user can have multiple tokens', function () {
        $user = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'gender' => 'female',
            'refid' => 'REF456',
            'password' => 'password',
        ]);

        // Create multiple tokens with different abilities
        $mobileToken = $this->tokenManager->createToken($user, 'mobile-app', ['read']);
        $webToken = $this->tokenManager->createToken($user, 'web-app', ['read', 'write']);
        $adminToken = $this->tokenManager->createToken($user, 'admin-panel', ['*']);

        // Verify all tokens work
        expect($this->tokenManager->authenticate($mobileToken, fn ($t, $id) => TestUser::find($id)))->not->toBeNull()
            ->and($this->tokenManager->authenticate($webToken, fn ($t, $id) => TestUser::find($id)))->not->toBeNull()
            ->and($this->tokenManager->authenticate($adminToken, fn ($t, $id) => TestUser::find($id)))->not->toBeNull();

        // Verify abilities are different
        expect($this->tokenManager->checkAbility($mobileToken, 'read'))->toBeTrue()
            ->and($this->tokenManager->checkAbility($mobileToken, 'write'))->toBeFalse();

        expect($this->tokenManager->checkAbility($webToken, 'read'))->toBeTrue()
            ->and($this->tokenManager->checkAbility($webToken, 'write'))->toBeTrue();

        expect($this->tokenManager->checkAbility($adminToken, 'read'))->toBeTrue()
            ->and($this->tokenManager->checkAbility($adminToken, 'write'))->toBeTrue()
            ->and($this->tokenManager->checkAbility($adminToken, 'delete'))->toBeTrue();
    });

    test('expired token is automatically deleted', function () {
        $user = TestUser::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'gender' => 'male',
            'refid' => 'REF789',
            'password' => 'password',
        ]);

        // Create token that expires immediately (in the past)
        $token = $this->tokenManager->createToken($user, 'expired-token', ['*'], -1);

        // Try to authenticate - should fail and delete the token
        $authenticated = $this->tokenManager->authenticate($token, fn ($t, $id) => TestUser::find($id));

        expect($authenticated)->toBeNull();

        // Verify token was deleted
        [$tokenId] = explode('|', $token);
        expect($this->repository->findToken((int) $tokenId))->toBeNull();
    });
});
