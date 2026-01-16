<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Feature tests for the Ally package.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Tests\Packages\Ally\Feature;

use Ally\Ally;
use Ally\Models\Reseller;
use Testing\Support\DatabaseTestHelper;

beforeEach(function () {
    DatabaseTestHelper::setupTestEnvironment(['Ally', 'Wallet'], true);
});

afterEach(function () {
    DatabaseTestHelper::resetDefaultConnection();
});

describe('Reseller Management', function () {
    test('can register a reseller using fluent API', function () {
        $user = DatabaseTestHelper::createMockUser();

        $reseller = Ally::make()
            ->user($user->id)
            ->company('Global Partners')
            ->tier('gold')
            ->create();

        expect($reseller)->toBeInstanceOf(Reseller::class)
            ->and($reseller->company_name)->toBe('Global Partners')
            ->and($reseller->tier->value)->toBe('gold')
            ->and($reseller->user_id)->toEqual($user->id);
    });

    test('automatically creates a credit wallet for new resellers', function () {
        $user = DatabaseTestHelper::createMockUser();

        $reseller = Ally::make()
            ->user($user->id)
            ->create();

        $reseller->refresh();
        $wallet = \Wallet\Models\Wallet::query()
            ->where('owner_id', $reseller->id)
            ->where('owner_type', Reseller::class)
            ->first();

        expect($wallet)->not->toBeNull()
            ->and($wallet->balance)->toBe(0);
    });

    test('can find reseller by user id', function () {
        $user = DatabaseTestHelper::createMockUser();
        Ally::make()->user($user->id)->create();

        $reseller = Ally::findByUser($user->id);

        expect($reseller)->not->toBeNull()
            ->and($reseller->user_id)->toEqual($user->id);
    });

    test('can add distribution credits to a reseller', function () {
        $user = DatabaseTestHelper::createMockUser();
        $reseller = Ally::make()->user($user->id)->create();

        Ally::addCredits($reseller->id, 5000); // 5000 units/cents

        $reseller->refresh();

        $wallet = \Wallet\Models\Wallet::query()
            ->where('owner_id', $reseller->id)
            ->where('owner_type', Reseller::class)
            ->first();

        expect($wallet->balance)->toBe(5000);
    });
});
