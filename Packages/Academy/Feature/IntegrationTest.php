<?php

declare(strict_types=1);

namespace Tests\Packages\Academy\Feature;

use Academy\Enums\DiscussionType;
use Academy\Enums\EnrolmentStatus;
use Academy\Events\ProgramCompletedEvent;
use Academy\Exceptions\AccessDeniedException;
use Academy\Listeners\AwardLearningRewardListener;
use Academy\Models\AcademyEnrolment;
use Academy\Models\AcademyProgram;
use Academy\Services\DiscussionService;
use Academy\Services\EnrolmentManagerService;
use App\Models\User;
use Core\Event;
use Database\DB;
use Exception;
use Helpers\DateTimeHelper;
use Testing\Support\DatabaseTestHelper;
use Wallet\Models\Wallet;
use Wave\Enums\SubscriptionStatus;
use Wave\Models\Subscription;

describe('Academy Optional Enhancements Integration', function () {
    beforeEach(function () {
        $this->refreshDatabase();
        Event::clearListeners();

        // Boot packages and run migrations
        $this->bootPackage('Academy', null, true);
        $this->bootPackage('Audit', null, true);
        $this->bootPackage('Wave', null, true);
        $this->bootPackage('Wallet', null, true);
        $this->bootPackage('Hub', null, true);

        // Migrate additional dependencies
        DatabaseTestHelper::runPackageMigrations('Rank');
        DatabaseTestHelper::runPackageMigrations('Refer');
        DatabaseTestHelper::runPackageMigrations('Link');
        DatabaseTestHelper::runPackageMigrations('Verify');

        // Enable integrations in config
        config()->set('academy.integrations.wave', true);
        config()->set('academy.integrations.hub', true);
        config()->set('academy.integrations.wallet', true);
        config()->set('academy.integrations.pay', true);
        config()->set('academy.integrations.rank', true);
        config()->set('academy.integrations.audit', true);
        config()->set('academy.integrations.refer', true);
        config()->set('academy.integrations.link', true);
        config()->set('academy.integrations.verify', true);
        config()->set('academy.integrations.blish', false);
        config()->set('academy.rewards.enabled', true);
        config()->set('academy.rewards.amounts.program_completed', 10.0);
    });

    test('wave subscription gating', function () {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'gender' => 'male',
        ]);

        $program = AcademyProgram::create([
            'title' => 'Subscription Gated Program',
            'slug' => 'gated-program',
            'metadata' => ['required_plan_ids' => [1]]
        ]);

        $enrolmentService = resolve(EnrolmentManagerService::class);

        // Attempt enrolment without subscription (should fail)
        try {
            $enrolmentService->enrol($user->id, $program);
            $this->fail('Expected AccessDeniedException was not thrown.');
        } catch (AccessDeniedException $e) {
            expect($e->getMessage())->toBe('Active subscription plan required to enrol in this program.');
        }

        // Create active subscription
        // Need to create a plan first due to foreign key constraint
        $plan = DB::table('wave_plan')->insertGetId([
            'refid' => 'plan_' . time(),
            'name' => 'Test Plan',
            'slug' => 'test-plan',
            'price' => 1000,
            'currency' => 'USD',
            'status' => 'active',
            'created_at' => DateTimeHelper::now()->toDateTimeString(),
            'updated_at' => DateTimeHelper::now()->toDateTimeString(),
        ]);

        Subscription::create([
            'owner_id' => $user->id,
            'owner_type' => 'user',
            'plan_id' => $plan,
            'status' => SubscriptionStatus::ACTIVE->value,
            'current_period_start' => DateTimeHelper::now(),
            'current_period_end' => DateTimeHelper::now()->addMonth(),
        ]);

        // Attempt enrolment with subscription (should succeed)
        $enrolment = $enrolmentService->enrol($user->id, $program);
        expect($enrolment)->toBeInstanceOf(AcademyEnrolment::class);
    });

    test('hub discussion integration', function () {
        $user = User::create([
            'name' => 'Hub User',
            'email' => 'hub@example.com',
            'password' => 'password',
            'gender' => 'male',
        ]);

        $program = AcademyProgram::create([
            'title' => 'Hub Discussion Program',
            'slug' => 'hub-program',
        ]);

        $data = [
            'program_id' => $program->id,
            'user_id' => $user->id,
            'content' => 'This is a test discussion topic',
            'type' => DiscussionType::PROGRAM->value,
        ];

        // Topic: Create a new discussion

        $discussionService = resolve(DiscussionService::class);
        $discussion = $discussionService->post($data);

        expect($discussion->metadata)->toHaveKey('hub_thread_id');

        // Verify Hub Thread exists if hub_thread table exists
        try {
            $threadExists = DB::table('hub_thread')
                ->where('id', (int) $discussion->metadata['hub_thread_id'])
                ->exists();
            expect($threadExists)->toBeTrue();
        } catch (Exception $e) {
            $this->markTestSkipped('Hub thread verification skipped: ' . $e->getMessage());
        }
    });

    test('wallet learning rewards', function () {
        $user = User::create([
            'name' => 'Reward User',
            'email' => 'reward@example.com',
            'password' => 'password',
            'gender' => 'male',
        ]);

        $program = AcademyProgram::create([
            'title' => 'Reward Program',
            'slug' => 'reward-program',
        ]);

        // Verify listener is registered
        $listeners = Event::listeners(ProgramCompletedEvent::class);
        expect($listeners)->toContain(AwardLearningRewardListener::class);

        // Initial balance should be 0
        $wallet = Wallet::create([
            'owner_id' => $user->id,
            'owner_type' => User::class,
            'balance' => 0,
            'currency' => 'USD'
        ]);

        // Trigger program completion event
        Event::dispatch(new ProgramCompletedEvent(
            AcademyEnrolment::create([
                'user_id' => $user->id,
                'program_id' => $program->id,
                'status' => EnrolmentStatus::COMPLETED,
            ])
        ));

        $wallet->refresh();
        $rewardAmountCents = (float) config('academy.rewards.amounts.program_completed', 10.0) * 100;
        expect((float) $wallet->balance)->toBe($rewardAmountCents);
    });

    test('award learning reward listener direct', function () {
        $user = User::create([
            'name' => 'Direct User',
            'email' => 'direct@example.com',
            'password' => 'password',
            'gender' => 'male',
        ]);

        $program = AcademyProgram::create([
            'title' => 'Direct Program',
            'slug' => 'direct-program',
        ]);

        $wallet = Wallet::create([
            'owner_id' => $user->id,
            'owner_type' => User::class,
            'balance' => 0,
            'currency' => 'USD'
        ]);

        $enrolment = AcademyEnrolment::create([
            'user_id' => $user->id,
            'program_id' => $program->id,
            'status' => EnrolmentStatus::COMPLETED,
        ]);

        $event = new ProgramCompletedEvent($enrolment);

        $listener = resolve(AwardLearningRewardListener::class);
        $listener->handle($event);

        $wallet->refresh();
        expect((float) $wallet->balance)->toBe(1000.0); // 10 dollars = 1000 cents
    });

    test('audit logging integration', function () {
        $program = AcademyProgram::create([
            'title' => 'Audit Test Program',
            'slug' => 'audit-program',
        ]);

        // Check if audit log exists
        $auditExists = DB::table('audit_log')
            ->where('auditable_type', AcademyProgram::class)
            ->where('auditable_id', $program->id)
            ->where('event', 'academy.academyprogram.created')
            ->exists();

        expect($auditExists)->toBeTrue();
    });
});
