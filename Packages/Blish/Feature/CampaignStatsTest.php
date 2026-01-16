<?php

declare(strict_types=1);

use Blish\Models\Campaign;
use Blish\Models\Event;
use Blish\Models\Subscriber;
use Carbon\Carbon;

beforeEach(function () {
    $this->bootPackage('Blish', null, true);
});

describe('Campaign Statistics', function () {
    test('it can get event trends', function () {
        // Freeze time for deterministic testing
        $now = Carbon::parse('2026-01-08 12:00:00');
        Carbon::setTestNow($now);

        // Create a campaign
        $campaign = Campaign::create([
            'refid' => 'test-refid',
            'title' => 'Test Campaign',
            'subject' => 'Test Subject',
            'status' => 'draft',
        ]);

        // Create subscriber
        $subscriber = Subscriber::create([
            'uuid' => 'test-uuid-1',
            'refid' => 'sub-ref-1',
            'email' => 'test@example.com',
            'status' => 'active',
        ]);

        $subscriber2 = Subscriber::create([
            'uuid' => 'test-uuid-2',
            'refid' => 'sub-ref-2',
            'email' => 'test2@example.com',
            'status' => 'active',
        ]);

        // Today: 2 opens, 1 click
        Event::create(['campaign_id' => $campaign->id, 'subscriber_id' => $subscriber->id, 'type' => 'open']);
        Event::create(['campaign_id' => $campaign->id, 'subscriber_id' => $subscriber2->id, 'type' => 'open']);
        Event::create(['campaign_id' => $campaign->id, 'subscriber_id' => $subscriber->id, 'type' => 'click']);

        // Yesterday: 1 open
        Carbon::setTestNow($now->copy()->subDay());
        Event::create(['campaign_id' => $campaign->id, 'subscriber_id' => $subscriber->id, 'type' => 'open']);

        // Back to now
        Carbon::setTestNow($now);

        // Get trends
        $openTrend = $campaign->getOpenTrend();
        $clickTrend = $campaign->getClickTrend();

        // Verify Open Trend
        $todayOps = $now->format('Y-m-d');
        $yesterdayOps = $now->copy()->subDay()->format('Y-m-d');

        expect($openTrend)->toBeArray()
            ->and($openTrend)->toHaveKey($todayOps)
            ->and($openTrend[$todayOps])->toBe(2)
            ->and($openTrend)->toHaveKey($yesterdayOps)
            ->and($openTrend[$yesterdayOps])->toBe(1);

        // Verify Click Trend
        expect($clickTrend)->toBeArray()
            ->and($clickTrend)->toHaveKey($todayOps)
            ->and($clickTrend[$todayOps])->toBe(1);
    });
});
