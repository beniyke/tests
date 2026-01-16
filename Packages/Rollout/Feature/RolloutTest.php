<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Feature tests for the Rollout package.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

use Rollout\Models\Feature;
use Rollout\Rollout;
use Tests\System\Support\Helpers\DatabaseTestHelper;

beforeEach(function () {
    DatabaseTestHelper::setupTestEnvironment(['Rollout'], true);
});

afterEach(function () {
    DatabaseTestHelper::resetDefaultConnection();
});

describe('Feature Creation', function () {
    test('can create a feature', function () {
        $feature = Rollout::feature()
            ->slug('dark-mode')
            ->name('Dark Mode')
            ->description('Enable dark mode UI')
            ->create();

        expect($feature)->toBeInstanceOf(Feature::class)
            ->and($feature->slug)->toBe('dark-mode')
            ->and($feature->is_enabled)->toBeFalse();
    });

    test('can create enabled feature', function () {
        $feature = Rollout::feature()
            ->slug('new-checkout')
            ->enable()
            ->create();

        expect($feature->is_enabled)->toBeTrue()
            ->and($feature->percentage)->toBe(100);
    });

    test('can create feature with percentage', function () {
        $feature = Rollout::feature()
            ->slug('beta-feature')
            ->percentage(50)
            ->create();

        expect($feature->is_enabled)->toBeTrue()
            ->and($feature->percentage)->toBe(50);
    });

    test('can create feature with date constraints', function () {
        $feature = Rollout::feature()
            ->slug('holiday-theme')
            ->startsAt('2025-12-01')
            ->endsAt('2025-12-31')
            ->enable()
            ->create();

        expect($feature->starts_at)->not->toBeNull()
            ->and($feature->ends_at)->not->toBeNull();
    });
});

describe('Feature Flag Checking', function () {
    test('disabled feature returns false', function () {
        Rollout::feature()->slug('disabled-feature')->disable()->create();

        expect(Rollout::isEnabled('disabled-feature'))->toBeFalse();
    });

    test('enabled feature with 100% returns true without user', function () {
        Rollout::feature()->slug('global-feature')->enable()->create();

        expect(Rollout::isEnabled('global-feature'))->toBeTrue();
    });

    test('undefined feature returns default state', function () {
        expect(Rollout::isEnabled('non-existent'))->toBeFalse();
    });
});

describe('Feature Enable/Disable', function () {
    test('can enable a feature', function () {
        Rollout::feature()->slug('toggle-test')->create();

        expect(Rollout::isEnabled('toggle-test'))->toBeFalse();

        Rollout::enable('toggle-test');

        expect(Rollout::isEnabled('toggle-test'))->toBeTrue();
    });

    test('can disable a feature', function () {
        Rollout::feature()->slug('toggle-test')->enable()->create();

        expect(Rollout::isEnabled('toggle-test'))->toBeTrue();

        Rollout::disable('toggle-test');

        expect(Rollout::isEnabled('toggle-test'))->toBeFalse();
    });
});

describe('Percentage Rollout', function () {
    test('can set percentage', function () {
        Rollout::feature()->slug('perctest')->create();

        Rollout::setPercentage('perctest', 75);

        $feature = Rollout::get('perctest');
        expect($feature->percentage)->toBe(75);
    });

    test('percentage is clamped to 0-100', function () {
        Rollout::feature()->slug('perctest2')->create();

        Rollout::setPercentage('perctest2', 150);
        $feature = Rollout::get('perctest2');
        expect($feature->percentage)->toBe(100);

        Rollout::setPercentage('perctest2', -10);
        $feature = Rollout::get('perctest2');
        expect($feature->percentage)->toBe(0);
    });

    test('consistent hashing returns same result for same user', function () {
        $feature = Rollout::feature()
            ->slug('hash-test')
            ->percentage(50)
            ->create();

        $mockUser = DatabaseTestHelper::createMockUser(123);

        $result1 = $feature->isInPercentage($mockUser);
        $result2 = $feature->isInPercentage($mockUser);
        $result3 = $feature->isInPercentage($mockUser);

        expect($result1)->toBe($result2)->toBe($result3);
    });
});

describe('Segments', function () {
    test('can create feature with role segments', function () {
        $feature = Rollout::feature()
            ->slug('admin-feature')
            ->forRoles(['admin', 'super-admin'])
            ->create();

        $segments = $feature->segments()->get();
        expect($segments)->toHaveCount(2);
    });

    test('can create feature with email domain segments', function () {
        $feature = Rollout::feature()
            ->slug('internal-feature')
            ->forDomains(['company.com', 'internal.org'])
            ->create();

        $segments = $feature->segments()->get();
        expect($segments)->toHaveCount(2);
    });
});

describe('Cache Management', function () {
    test('can clear cache', function () {
        Rollout::feature()->slug('cached-feature')->enable()->create();
        Rollout::get('cached-feature'); // Load into cache

        Rollout::clearCache();

        // Should reload from database
        $feature = Rollout::get('cached-feature');
        expect($feature)->not->toBeNull();
    });
});
