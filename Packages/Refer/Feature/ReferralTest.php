<?php

declare(strict_types=1);

use App\Models\User;
use Refer\Enums\ReferralStatus;
use Refer\Models\Referral;
use Refer\Models\ReferralCode;
use Tests\System\Support\Helpers\DatabaseTestHelper;

beforeEach(function () {
    DatabaseTestHelper::setupTestEnvironment(['Refer'], true);
});

afterEach(function () {
    DatabaseTestHelper::resetDefaultConnection();
});

describe('ReferralCode Model', function () {

    test('creates referral code with required fields', function () {
        $user = User::create(['name' => 'Referrer', 'email' => 'ref@example.com', 'password' => 'secret', 'gender' => 'male', 'refid' => 'REF001']);
        $code = ReferralCode::create([
            'user_id' => $user->id,
            'code' => 'TESTCODE',
            'is_active' => true,
            'uses_count' => 0,
            'max_uses' => 0,
        ]);

        expect($code)->toBeInstanceOf(ReferralCode::class)
            ->and($code->code)->toBe('TESTCODE')
            ->and($code->is_active)->toBeTrue()
            ->and($code->uses_count)->toBe(0);
    });

    test('isValid returns true for active code with no limits', function () {
        $code = new ReferralCode();
        $code->is_active = true;
        $code->max_uses = 0;
        $code->uses_count = 0;
        $code->expires_at = null;

        expect($code->isValid())->toBeTrue();
    });

    test('isValid returns false for inactive code', function () {
        $code = new ReferralCode();
        $code->is_active = false;

        expect($code->isValid())->toBeFalse();
    });

    test('isValid returns false when max uses reached', function () {
        $code = new ReferralCode();
        $code->is_active = true;
        $code->max_uses = 5;
        $code->uses_count = 5;

        expect($code->isValid())->toBeFalse();
    });

    test('incrementUsage increases uses_count', function () {
        $user = User::create(['name' => 'Referrer', 'email' => 'inc@example.com', 'password' => 'secret', 'gender' => 'male', 'refid' => 'REF002']);
        $code = ReferralCode::create([
            'user_id' => $user->id,
            'code' => 'INC001',
            'is_active' => true,
            'uses_count' => 0,
            'max_uses' => 10,
        ]);

        $initialCount = $code->uses_count;
        $code->incrementUsage();
        $code->refresh();

        expect($code->uses_count)->toBe($initialCount + 1);
    });

    test('findByCode finds code case-insensitively', function () {
        $user = User::create(['name' => 'Referrer', 'email' => 'upper@example.com', 'password' => 'secret', 'gender' => 'male', 'refid' => 'REF003']);
        ReferralCode::create([
            'user_id' => $user->id,
            'code' => 'UPPERCASE',
            'is_active' => true,
            'uses_count' => 0,
            'max_uses' => 0,
        ]);

        $found = ReferralCode::findByCode('uppercase');

        expect($found)->not->toBeNull()
            ->and($found->code)->toBe('UPPERCASE');
    });
});

describe('Referral Model', function () {

    test('creates referral with required fields', function () {
        $user1 = User::create(['name' => 'R1', 'email' => 'r1@example.com', 'password' => 'secret', 'gender' => 'male', 'refid' => 'REF004']);
        $user2 = User::create(['name' => 'R2', 'email' => 'r2@example.com', 'password' => 'secret', 'gender' => 'female', 'refid' => 'REF005']);
        $code = ReferralCode::create(['user_id' => $user1->id, 'code' => 'C1']);

        $referral = Referral::create([
            'code_id' => $code->id,
            'referrer_id' => $user1->id,
            'referee_id' => $user2->id,
            'status' => ReferralStatus::PENDING,
            'referrer_reward' => 100,
            'referee_reward' => 50,
        ]);

        expect($referral)->toBeInstanceOf(Referral::class)
            ->and($referral->status)->toBe(ReferralStatus::PENDING)
            ->and($referral->referrer_reward)->toBe(100);
    });

    test('isPending returns correct value', function () {
        $referral = new Referral();

        $referral->status = ReferralStatus::PENDING;
        expect($referral->isPending())->toBeTrue();

        $referral->status = ReferralStatus::REWARDED;
        expect($referral->isPending())->toBeFalse();
    });

    test('isRewarded returns correct value', function () {
        $referral = new Referral();

        $referral->status = ReferralStatus::REWARDED;
        expect($referral->isRewarded())->toBeTrue();

        $referral->status = ReferralStatus::PENDING;
        expect($referral->isRewarded())->toBeFalse();
    });
});

describe('ReferralStatus Enum', function () {

    test('has all expected cases', function () {
        $cases = ReferralStatus::cases();

        expect($cases)->toHaveCount(5)
            ->and(array_column($cases, 'value'))->toContain(
                'pending',
                'qualified',
                'rewarded',
                'expired',
                'cancelled'
            );
    });
});
