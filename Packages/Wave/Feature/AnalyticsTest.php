<?php

declare(strict_types=1);

namespace Tests\Packages\Wave\Feature;

use Helpers\DateTimeHelper;
use Testing\Concerns\RefreshDatabase;
use Wave\Enums\SubscriptionStatus;
use Wave\Models\Plan;
use Wave\Models\Subscription;
use Wave\Wave;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->refreshDatabase();
});

it('calculates MRR correctly', function () {
    // 2 Monthly Plans @ $10
    $monthlyPlan = Wave::plan()->make()->name('Monthly')->slug('monthly')->price(1000)->monthly()->trialDays(0)->save(); // $10.00
    Wave::newSubscription()->for((object)['id' => 1], 'user')->plan($monthlyPlan->id)->start();
    Wave::newSubscription()->for((object)['id' => 2], 'user')->plan($monthlyPlan->id)->start();

    // 1 Yearly Plan @ $120 ($10/month)
    $yearlyPlan = Wave::plan()->make()->name('Yearly')->slug('yearly')->price(12000)->yearly()->trialDays(0)->save(); // $120.00
    Wave::newSubscription()->for((object)['id' => 3], 'user')->plan($yearlyPlan->id)->start();

    // Total MRR should be $10 + $10 + ($120/12 = $10) = $30.00 -> 3000 cents
    expect(Wave::analytics()->mrr()->getAmount())->toBe(3000);
});

it('calculates total revenue from paid invoices', function () {
    // Paid invoice
    Wave::invoices()->make()->for((object)['id' => 1], 'user')->amount(5000)->paid()->create();

    // Unpaid invoice (should be ignored)
    Wave::invoices()->make()->for((object)['id' => 1], 'user')->amount(2000)->create(); // status default OPEN

    // Paid invoice earlier
    $oldInvoice = Wave::invoices()->make()->for((object)['id' => 1], 'user')->amount(3000)->paid()->create();
    // Update paid_at manually if builder sets it to now
    $oldInvoice->update(['paid_at' => DateTimeHelper::now()->subDays(10)]);

    expect(Wave::analytics()->revenue()->getAmount())->toBe(8000); // 5000 + 3000

    // Date range test
    $start = DateTimeHelper::now()->subDays(1)->format('Y-m-d');
    $end = DateTimeHelper::now()->addDays(1)->format('Y-m-d');

    // Only the recent 5000 should be counted
    expect(Wave::analytics()->revenue($start, $end)->getAmount())->toBe(5000);
});

it('calculates active subscribers count', function () {
    $plan = Wave::plan()->make()->name('P')->slug('active-plan')->price(10)->save();

    // Active
    Wave::newSubscription()->for((object)['id' => 1], 'user')->plan($plan->id)->start();

    // Trialing (counted)
    Wave::newSubscription()->for((object)['id' => 2], 'user')->plan($plan->id)->trialDays(5)->start();

    // Canceled (ignored)
    $sub = Wave::newSubscription()->for((object)['id' => 3], 'user')->plan($plan->id)->start();
    $sub->update(['status' => SubscriptionStatus::CANCELED->value]);

    expect(Wave::analytics()->activeSubscribers())->toBe(2);
});

it('calculates churn rate', function () {
    $plan = Wave::plan()->make()->name('P')->slug('churn-plan')->price(10)->save();

    // 5 active
    for ($i = 1; $i <= 5; $i++) {
        Wave::newSubscription()->for((object)['id' => $i], 'user')->plan($plan->id)->start();
    }

    // 2 canceled
    for ($i = 6; $i <= 7; $i++) {
        $sub = Wave::newSubscription()->for((object)['id' => $i], 'user')->plan($plan->id)->start();
        $sub->update(['status' => SubscriptionStatus::CANCELED->value, 'canceled_at' => DateTimeHelper::now()]);
    }

    Subscription::query()->update(['created_at' => DateTimeHelper::now()->subDays(60)]);

    expect(Wave::analytics()->churnRate())->toBe(28.57);

    // Test with custom days (e.g. 7 days - should be same as we moved created_at back 60 days)
    // But canceled_at is NOW, so it falls in 7 days window.
    // Start active (7 days ago) would be same: 5 active + 2 canceled - 0 new (since all created 60 days ago) = 7
    // Churn = (2 / 7) * 100 = 28.57
    expect(Wave::analytics()->churnRate(7))->toBe(28.57);
});
