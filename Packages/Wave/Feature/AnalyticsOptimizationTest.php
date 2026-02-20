<?php

declare(strict_types=1);

namespace Tests\Packages\Wave\Feature;

use Helpers\DateTimeHelper;
use Testing\Concerns\RefreshDatabase;
use Tests\TestCase;
use Wave\Enums\AnalyticsMetric;
use Wave\Models\Invoice;
use Wave\Models\Plan;
use Wave\Models\Subscription;
use Wave\Wave;

uses(RefreshDatabase::class);

beforeEach(function () {
    /** @var TestCase $this */
    $this->refreshDatabase();
    $this->bootPackage('Wave', null, true);
    config()->set('wave.tax.enabled', false);
});

it('calculates optimized MRR correctly', function () {
    // 5 Monthly Plans @ $10. Total 5000 cents.
    // We create them using one plan to trigger aggregation logic on that plan_id.
    $plan = Wave::plan()->make()->name('Aggregated')->slug('agg')->price(1000)->monthly()->trialDays(0)->save();

    // Create 5 subs
    for ($i = 1; $i <= 5; $i++) {
        Wave::newSubscription()->for((object)['id' => $i], 'user')->plan($plan->id)->start();
    }

    // MRR should be 5 * 10 = 50.
    expect(Wave::analytics()->mrr()->getAmount())->toBe(5000);
});

it('generates revenue history for charts', function () {
    // 1. Paid today
    Wave::invoices()->make()->for((object)['id' => 1], 'user')->amount(1000)->paid()->create();

    // 2. Paid yesterday
    $inv = Wave::invoices()->make()->for((object)['id' => 1], 'user')->amount(2000)->paid()->create();
    $inv->update(['paid_at' => DateTimeHelper::now()->subDays(1)]);

    // 3. Paid 2 days ago
    $inv2 = Wave::invoices()->make()->for((object)['id' => 1], 'user')->amount(500)->paid()->create();
    $inv2->update(['paid_at' => DateTimeHelper::now()->subDays(2)]);
    $revenueHistory = Wave::analytics()->getHistory(AnalyticsMetric::REVENUE->value);

    expect($revenueHistory)->toHaveKeys(['labels', 'values']);
    expect($revenueHistory['values'])->toBeArray();

    // Verify specifically that we have the expected totals in the history
    // Since getHistory returns values for the period, we check the sum matches total paid
    expect(array_sum($revenueHistory['values']))->toBe(3500);
});

it('generates domain stats', function () {
    $plan = Wave::plan()->make()->name('Top Plan')->slug('top')->price(100)->save();
    Wave::newSubscription()->for((object)['id' => 1], 'user')->plan($plan->id)->start();
    // Delete implicit invoice created by subscription start to ensure clean state for invoice stats
    Invoice::truncate();

    $stats = Wave::analytics()->productStats();
    expect($stats['top_plans'])->toHaveCount(1);
    expect($stats['top_plans'][0]['name'])->toBe('Top Plan');

    // Invoices stats
    Wave::invoices()->make()->for((object)['id' => 1], 'user')->amount(100)->paid()->create(); // Paid
    Wave::invoices()->make()->for((object)['id' => 1], 'user')->amount(100)->create(); // Unpaid/Open

    $invStats = Wave::analytics()->invoiceStats();
    expect($invStats['paid_count'])->toBe(1);
    expect($invStats['unpaid_count'])->toBe(1);
    expect($invStats['total_collected']->getAmount())->toBe(100);
});

it('generates subscriber stats', function () {
    // Subscriber with 1 active sub and 500 paid
    Wave::invoices()->make()->for((object)['id' => 99], 'user')->amount(500)->paid()->create();
    $plan = Wave::plan()->make()->name('P')->slug('ltv')->price(10)->save();
    Wave::newSubscription()->for((object)['id' => 99], 'user')->plan($plan->id)->start();

    $stats = Wave::analytics()->subscriberStats(99, 'user');

    expect($stats['ltv']->getAmount())->toBe(500);
    expect($stats['active_subscriptions'])->toBe(1);
});
