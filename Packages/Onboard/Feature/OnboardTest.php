<?php

declare(strict_types=1);

use App\Models\User;
use Metric\Models\OneOnOne;
use Onboard\Enums\OnboardStatus;
use Onboard\Models\Onboarding;
use Onboard\Models\Task;
use Onboard\Models\Template;
use Onboard\Onboard;
use Testing\Support\DatabaseTestHelper;

beforeEach(function () {
    DatabaseTestHelper::setupTestEnvironment(['Onboard', 'Audit', 'Metric'], true);
    $this->bootPackage('Onboard');
    $this->bootPackage('Metric', null, true);
    $this->fakeAudit();

    $this->user = User::create([
        'name' => 'New Employee',
        'email' => 'employee@example.com',
        'password' => 'password',
        'gender' => 'male',
    ]);

    $this->template = Template::create([
        'name' => 'General Onboarding',
        'role' => 'All'
    ]);

    $this->task = Task::create([
        'onboard_template_id' => $this->template->id,
        'name' => 'Setup Email',
        'is_required' => true
    ]);
});

afterEach(function () {
    DatabaseTestHelper::dropAllTables();
    DatabaseTestHelper::resetDefaultConnection();
});

test('it can start onboarding for a user', function () {
    $onboarding = Onboard::onboarding()
        ->for($this->user)
        ->using($this->template)
        ->start();

    expect($onboarding)->toBeInstanceOf(Onboarding::class);
    expect($onboarding->status)->toBe(OnboardStatus::IN_PROGRESS);
    expect($onboarding->user_id)->toBe($this->user->id);
});

test('it can complete an onboarding task', function () {
    Onboard::onboarding()->for($this->user)->using($this->template)->start();

    $completion = Onboard::completeTask($this->user, $this->task, 'Email is working');

    expect($completion->completed_at)->not->toBeNull();
    expect($completion->notes)->toBe('Email is working');
});

test('it completes onboarding when all required tasks are done', function () {
    $onboarding = Onboard::onboarding()->for($this->user)->using($this->template)->start();

    Onboard::completeTask($this->user, $this->task);
    $onboarding = $onboarding->fresh();

    expect($onboarding->status)->toBe(OnboardStatus::COMPLETED);
    expect($onboarding->completed_at)->not->toBeNull();

    // Verify handoff to Metric (should create a 1-on-1)
    $oneOnOne = OneOnOne::query()->where('user_id', $this->user->id)->first();
    expect($oneOnOne)->not->toBeNull();
    expect($oneOnOne->agenda)->toContain('Onboarding Handoff');
});

test('it tracks progress percentage', function () {
    Onboard::onboarding()->for($this->user)->using($this->template)->start();

    // Add another task
    $optionalTask = Onboard::task()->name('Intro')->template($this->template)->optional()->create();

    // 1 of 2 tasks done (50%)
    Onboard::completeTask($this->user, $this->task);

    $progress = Onboard::analytics()->progress((int) $this->user->id);

    expect($progress)->toBe(50.0);
});
