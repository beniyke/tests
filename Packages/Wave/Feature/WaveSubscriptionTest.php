<?php

declare(strict_types=1);

namespace Tests\Packages\Wave\Feature;

use Money\Money;
use Testing\Concerns\RefreshDatabase;
use Testing\Support\DatabaseTestHelper;
use Tests\TestCase;
use Wallet\Services\WalletManagerService;
use Wave\Enums\InvoiceStatus;
use Wave\Enums\SubscriptionStatus;
use Wave\Models\Invoice;
use Wave\Models\Plan;
use Wave\Models\Subscription;
use Wave\Wave;

uses(RefreshDatabase::class);

beforeEach(function () {
    /** @var TestCase $this */
    DatabaseTestHelper::setupTestEnvironment(['Audit', 'Wallet', 'Pay', 'Wave'], true);
    // Use the standard way to boot the package
    $this->bootPackage('Wave');
    $this->fakeAudit();

    $this->walletManager = resolve(WalletManagerService::class);
});

test('can create plan and subscribe', function () {
    // 1. Create a plan
    $plan = Wave::findPlan('basic') ?: Plan::create([
        'name' => 'Basic Plan',
        'slug' => 'basic',
        'price' => 1000, // $10.00
        'currency' => 'USD',
        'interval' => 'month',
    ]);

    expect($plan)->not->toBeNull();

    // 2. Setup a wallet for the user
    $ownerId = 1;
    $ownerType = 'user';
    $wallet = $this->walletManager->findByOwner($ownerId, $ownerType, 'USD')
        ?: $this->walletManager->create($ownerId, $ownerType, 'USD');

    // Add funds
    $this->walletManager->credit($wallet->id, Money::dollars(50));

    // 3. Subscribe
    $subscription = Wave::subscribe($ownerId, $ownerType, 'basic');

    expect($subscription)->toBeInstanceOf(Subscription::class);
    expect($subscription->status)->toBe(SubscriptionStatus::ACTIVE);

    // 4. Verify invoice was generated and paid
    $invoice = Invoice::query()->where('subscription_id', $subscription->id)->first();
    expect($invoice)->not->toBeNull();
    expect($invoice->status)->toBe(InvoiceStatus::PAID);

    // 5. Verify wallet balance (10 + 15% tax = 11.50)
    $newBalance = $this->walletManager->getBalance($wallet->id);

    // Note: If this fails with 4000 instead of 3850, it means tax isn't being applied.
    expect($newBalance->getAmount())->toBe(3850);
});

test('subscription with trial', function () {
    $plan = Wave::findPlan('trial-plan') ?: Plan::create([
        'name' => 'Trial Plan',
        'slug' => 'trial-plan',
        'price' => 2000,
        'currency' => 'USD',
        'interval' => 'month',
        'trial_days' => 7,
    ]);

    $subscription = Wave::subscribe(2, 'user', 'trial-plan');

    expect($subscription->status)->toBe(SubscriptionStatus::TRIALING);
    expect($subscription->trial_ends_at)->not->toBeNull();

    // Trial subscriptions shouldn't generate paid invoices immediately
    $invoice = Invoice::query()->where('subscription_id', $subscription->id)->first();
    expect($invoice)->toBeNull();
});

afterEach(function () {
    DatabaseTestHelper::dropAllTables();
    DatabaseTestHelper::resetDefaultConnection();
});
