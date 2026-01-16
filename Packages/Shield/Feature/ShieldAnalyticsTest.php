<?php

declare(strict_types=1);

use Audit\Audit;
use Database\DB;
use Helpers\DateTimeHelper;
use Shield\Services\ShieldAnalytics;
use Shield\Services\ShieldManagerService;
use Shield\Shield;
use Testing\Support\DatabaseTestHelper;

beforeEach(function () {
    DatabaseTestHelper::setupTestEnvironment(['Shield', 'Audit'], true);
});

test('analytics service can be resolved via facade', function () {
    expect(Shield::analytics())->toBeInstanceOf(ShieldAnalytics::class);
});

test('analytics service can be resolved via manager', function () {
    $manager = resolve(ShieldManagerService::class);
    expect($manager->analytics())->toBeInstanceOf(ShieldAnalytics::class);
});

test('analytics returns empty stats when audit log is missing or empty', function () {
    $analytics = Shield::analytics();

    // safe check for overview
    $overview = $analytics->overview();
    expect($overview)->toBeArray()
        ->toHaveKeys(['total', 'verified', 'failed', 'errors', 'success_rate'])
        ->and($overview['total'])->toBe(0);

    // safe check for trends
    $trends = $analytics->trends();
    expect($trends)->toBeArray();

    // safe check for topIps
    $topIps = $analytics->topIps();
    expect($topIps)->toBeArray();

    // safe check for driverPerformance
    $perf = $analytics->driverPerformance();
    expect($perf)->toBeArray();
});

test('analytics aggregates data correctly from seeded logs', function () {
    $now = DateTimeHelper::now();

    // Seed verified checks
    for ($i = 0; $i < 10; $i++) {
        Audit::make()
            ->event('captcha.verified')
            ->metadata(['driver' => 'recaptcha', 'ip' => '1.2.3.4'])
            ->log();
    }

    // Seed failed checks (different driver/IP)
    for ($i = 0; $i < 5; $i++) {
        Audit::make()
            ->event('captcha.failed')
            ->metadata(['driver' => 'turnstile', 'ip' => '5.6.7.8'])
            ->log();
    }

    // Seed errors
    Audit::make()
        ->event('captcha.error')
        ->metadata(['driver' => 'recaptcha', 'ip' => '1.2.3.4'])
        ->log();

    $analytics = Shield::analytics();
    $overview = $analytics->overview();

    expect($overview['total'])->toBe(16)
        ->and($overview['verified'])->toBe(10)
        ->and($overview['failed'])->toBe(5)
        ->and($overview['errors'])->toBe(1)
        ->and($overview['success_rate'])->toBe(62.5);

    // Test driver filtering
    $recaptchaStats = $analytics->overview('recaptcha');
    expect($recaptchaStats['total'])->toBe(11)
        ->and($recaptchaStats['verified'])->toBe(10);

    // Test Top IPs
    $topIps = $analytics->topIps();
    expect($topIps[0]['ip'])->toBe('1.2.3.4')
        ->and($topIps[0]['total'])->toBe(11)
        ->and($topIps[1]['ip'])->toBe('5.6.7.8')
        ->and($topIps[1]['total'])->toBe(5);

    // Test Trends
    $trends = $analytics->trends();
    expect($trends)->toHaveCount(1)
        ->and($trends[0]['verified'])->toBe(10)
        ->and($trends[0]['total'])->toBe(16);
});

test('analytics respects date range', function () {
    $oldDate = DateTimeHelper::now()->subDays(10);

    // Manual insert for custom date or if Audit::make() doesn't support custom date yet
    DB::table('audit_log')->insert([
        'refid' => uniqid(),
        'event' => 'captcha.verified',
        'metadata' => json_encode(['driver' => 'recaptcha']),
        'created_at' => $oldDate->toDateTimeString(),
    ]);

    $analytics = Shield::analytics();

    // Default (last 30 days) should find it
    expect($analytics->overview()['total'])->toBe(1);

    // Trends with 5 days should NOT find it
    expect($analytics->trends(5))->toBeEmpty();
});
