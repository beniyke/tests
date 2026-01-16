<?php

declare(strict_types=1);

use App\Models\User;
use Carbon\Carbon;
use Metric\Metric;
use Metric\Models\Goal;
use Metric\Models\KpiValue;

beforeEach(function () {
    $now = Carbon::parse('2026-01-02 12:00:00');
    Carbon::setTestNow($now);

    $this->refreshDatabase();
    $this->fakeAudit();
    $this->bootPackage('Audit', null, true);
    $this->bootPackage('Hub', null, true);
    $this->bootPackage('Metric', null, true);

    // Mock Audit if it's being resolved as a mock globally or failing
    // For now let's see if real Audit works if we don't mock it.
    // Actually, if it's failing, it's because something is mocking it.

    $this->user = User::create([
        'name' => 'Developer',
        'email' => 'dev@example.com',
        'password' => 'password',
        'gender' => 'male',
        'refid' => 'DEV-001',
    ]);
});

test('it can create a goal with key results', function () {
    $goal = Metric::goal()
        ->for($this->user)
        ->title('Improve Performance')
        ->addKeyResult('Speed up API', 50, 'percentage')
        ->create();

    $goal->refresh();

    expect($goal)->toBeInstanceOf(Goal::class);
    expect($goal->keyResults)->toHaveCount(1);
    expect($goal->progress)->toBe(0.0);
});

test('it updates goal progress when key results are updated', function () {
    $goal = Metric::goal()
        ->for($this->user)
        ->title('Improve Performance')
        ->addKeyResult('Speed up API', 100)
        ->create();

    $goal->refresh();

    $kr = $goal->keyResults->first();
    Metric::updateKeyResult($kr, 50);
    $goal->refresh();

    expect($goal->progress)->toBe(50.0);
});

test('it can record KPI values', function () {
    $kpi = Metric::kpi()->name('Code Reviews')->create();
    $value = Metric::recordKpi($kpi, 12, $this->user);

    expect($value)->toBeInstanceOf(KpiValue::class);
    expect($value->value)->toBe(12.0);
    expect($value->user_id)->toBe($this->user->id);
});

test('it can send peer recognition', function () {
    $receiver = User::create([
        'name' => 'Peer',
        'email' => 'peer@example.com',
        'password' => 'pass',
        'gender' => 'female',
        'refid' => 'PEER-001'
    ]);

    $recognition = Metric::recognize($this->user, $receiver, 'kudos', 'Great help!');

    expect($recognition->award_type)->toBe('kudos');
    expect($recognition->receiver_id)->toBe($receiver->id);
});

test('it calculates basic analytics', function () {
    Metric::goal()->for($this->user)->title('Goal 1')->create();

    $stats = Metric::analytics()->goalOverview();

    expect($stats['total_goals'])->toBe(1);
    expect($stats['average_progress'])->toBe(0.0);
});
