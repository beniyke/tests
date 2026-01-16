<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Feature tests for the Client package.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Tests\Packages\Client\Feature;

use Client\Client;
use Client\Enums\ClientStatus;
use Client\Models\Client as ClientModel;
use Testing\Support\DatabaseTestHelper;

beforeEach(function () {
    DatabaseTestHelper::setupTestEnvironment(['Client'], true);
});

afterEach(function () {
    DatabaseTestHelper::resetDefaultConnection();
});

describe('Client Management', function () {
    test('can create a client using fluent API', function () {
        $client = Client::make()
            ->name('Acme Corp')
            ->email('contact@acme.com')
            ->create();

        expect($client)->toBeInstanceOf(ClientModel::class)
            ->and($client->name)->toBe('Acme Corp')
            ->and($client->email)->toBe('contact@acme.com')
            ->and($client->status)->toBe(ClientStatus::Pending);
    });

    test('can activate a client', function () {
        $client = Client::make()
            ->name('Test Client')
            ->email('test@example.com')
            ->create();

        $result = Client::activate($client->id);
        $client->refresh();

        expect($result)->toBeTrue()
            ->and($client->status)->toBe(ClientStatus::Active);
    });

    test('can associate client with a reseller', function () {
        $reseller = DatabaseTestHelper::createMockUser();

        $client = Client::make()
            ->name('Resold Client')
            ->email('resold@example.com')
            ->reseller($reseller->id)
            ->create();

        expect($client->owner_id)->toEqual($reseller->id)
            ->and($client->reseller->id)->toEqual($reseller->id);
    });

    test('can find client by email', function () {
        Client::make()
            ->name('Find Me')
            ->email('find@me.com')
            ->create();

        $client = Client::findByEmail('find@me.com');

        expect($client)->not->toBeNull()
            ->and($client->name)->toBe('Find Me');
    });

    test('can get clients by reseller', function () {
        $reseller = DatabaseTestHelper::createMockUser();

        Client::make()->name('Client 1')->email('c1@example.com')->reseller($reseller->id)->create();
        Client::make()->name('Client 2')->email('c2@example.com')->reseller($reseller->id)->create();
        Client::make()->name('Client 3')->email('c3@example.com')->create(); // Standalone

        $clients = Client::getByReseller($reseller->id);

        expect($clients)->toHaveCount(2);
    });
});
