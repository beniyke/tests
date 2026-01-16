<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Feature tests for the Forge package.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Tests\Packages\Forge\Feature;

use Client\Client;
use Forge\Enums\LicenceStatus;
use Forge\Forge;
use Testing\Support\DatabaseTestHelper;
use Wave\Wave;

beforeEach(function () {
    DatabaseTestHelper::setupTestEnvironment(['Client', 'Forge', 'Wave'], true);
    $this->fakeMail();
});

afterEach(function () {
    DatabaseTestHelper::resetDefaultConnection();
});

describe('License Management', function () {
    test('can generate a license key automatically', function () {
        $product = Wave::createProduct([
            'name' => 'Pro App',
            'price' => 1000,
            'currency' => 'USD',
        ]);

        $licence = Forge::make()
            ->product($product->id)
            ->duration(30)
            ->create();

        expect($licence->key)->not->toBeNull()
            ->and(strlen($licence->key))->toBeGreaterThan(10)
            ->and($licence->status)->toBe(LicenceStatus::Pending);
    });

    test('can activate a license for a client', function () {
        $product = Wave::createProduct(['name' => 'Pro App', 'price' => 1000, 'currency' => 'USD']);
        $client = Client::make()->name('Test Client')->email('test@example.com')->create();

        $licence = Forge::make()
            ->product($product->id)
            ->duration(365)
            ->create();

        $result = Forge::activate($licence->id, $client->id);
        $licence->refresh();

        expect($result)->toBeTrue()
            ->and($licence->status)->toBe(LicenceStatus::Active)
            ->and($licence->client_id)->toEqual($client->id)
            ->and($licence->activated_at)->not->toBeNull()
            ->and($licence->expires_at)->not->toBeNull();

        // Check if expiration is roughly 365 days away
        expect(abs($licence->expires_at->diffInDays($licence->activated_at)))->toEqual(365);
    });

    test('can verify an active license', function () {
        $product = Wave::createProduct(['name' => 'Pro App', 'price' => 1000, 'currency' => 'USD']);
        $client = Client::make()->name('Test Client')->email('test@example.com')->create();

        $licence = Forge::make()
            ->product($product->id)
            ->duration(30)
            ->create();

        Forge::activate($licence->id, $client->id);

        $verified = Forge::verify($licence->key);

        expect($verified)->not->toBeNull()
            ->and($verified->id)->toBe($licence->id);
    });

    test('cannot verify a pending or revoked license', function () {
        $product = Wave::createProduct(['name' => 'Pro App', 'price' => 1000, 'currency' => 'USD']);

        $licence = Forge::make()->product($product->id)->create();
        expect(Forge::verify($licence->key))->toBeNull();

        Forge::revoke($licence->id);
        expect(Forge::verify($licence->key))->toBeNull();
    });
});
