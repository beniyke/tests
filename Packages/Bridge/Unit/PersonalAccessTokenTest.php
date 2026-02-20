<?php

declare(strict_types=1);

use Bridge\PersonalAccessToken;
use Helpers\DateTimeHelper;

describe('PersonalAccessToken', function () {
    test('can check ability with wildcard', function () {
        $token = new PersonalAccessToken(
            id: 1,
            tokenableType: 'App\User',
            tokenableId: 1,
            name: 'test-token',
            hashedToken: 'hashed',
            abilities: ['*']
        );

        expect($token->can('read'))->toBeTrue();
        expect($token->can('write'))->toBeTrue();
        expect($token->can('delete'))->toBeTrue();
    });

    test('can check specific ability', function () {
        $token = new PersonalAccessToken(
            id: 1,
            tokenableType: 'App\User',
            tokenableId: 1,
            name: 'test-token',
            hashedToken: 'hashed',
            abilities: ['read', 'write']
        );

        expect($token->can('read'))->toBeTrue();
        expect($token->can('write'))->toBeTrue();
        expect($token->can('delete'))->toBeFalse();
    });

    test('can check empty abilities grants all', function () {
        $token = new PersonalAccessToken(
            id: 1,
            tokenableType: 'App\User',
            tokenableId: 1,
            name: 'test-token',
            hashedToken: 'hashed',
            abilities: []
        );

        expect($token->can('read'))->toBeTrue();
        expect($token->can('anything'))->toBeTrue();
    });

    test('is expired returns false when no expiry', function () {
        $token = new PersonalAccessToken(
            id: 1,
            tokenableType: 'App\User',
            tokenableId: 1,
            name: 'test-token',
            hashedToken: 'hashed',
            abilities: ['*'],
            expiresAt: null
        );

        expect($token->isExpired())->toBeFalse();
    });

    test('is expired returns true when expired', function () {
        $token = new PersonalAccessToken(
            id: 1,
            tokenableType: 'App\User',
            tokenableId: 1,
            name: 'test-token',
            hashedToken: 'hashed',
            abilities: ['*'],
            expiresAt: DateTimeHelper::now()->subHours(1)
        );

        expect($token->isExpired())->toBeTrue();
    });

    test('is expired returns false when not expired', function () {
        $token = new PersonalAccessToken(
            id: 1,
            tokenableType: 'App\User',
            tokenableId: 1,
            name: 'test-token',
            hashedToken: 'hashed',
            abilities: ['*'],
            expiresAt: DateTimeHelper::now()->addHours(1)
        );

        expect($token->isExpired())->toBeFalse();
    });
});
