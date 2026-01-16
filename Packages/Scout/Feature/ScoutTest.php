<?php

declare(strict_types=1);

use App\Models\User;
use Scout\Models\Application;
use Scout\Models\Job;
use Scout\Models\Stage;
use Scout\Scout;

beforeEach(function () {
    $this->refreshDatabase();
    $this->bootPackage('Audit', null, true);
    $this->bootPackage('Scout', null, true);
    $this->user = User::create([
        'name' => 'Recruiter',
        'email' => 'recruiter@example.com',
        'password' => 'password',
        'gender' => 'male',
        'refid' => 'REC001',
    ]);

    // Seed default stage
    $this->stage = Stage::create([
        'name' => 'Applied',
        'slug' => 'applied',
        'is_default' => true
    ]);
});

test('it can create and publish a job', function () {
    $job = Scout::job()
        ->title('Backend Developer')
        ->description('Develop core services')
        ->publish()
        ->create();

    expect($job)->toBeInstanceOf(Job::class);
    expect($job->status)->toBe('published');
    expect($job->slug)->toBe('backend-developer');
});

test('it can submit an application', function () {
    $job = Scout::job()->title('Dev')->description('Desc')->publish()->create();
    $candidate = Scout::candidate()->name('John')->email('john@example.com')->create();

    $application = Scout::application()
        ->for($job)
        ->from($candidate)
        ->submit();

    expect($application)->toBeInstanceOf(Application::class);
    expect($application->scout_job_id)->toBe($job->id);
    expect($application->candidate->name)->toBe('John');
    expect($application->stage->slug)->toBe('applied');
});

test('it can advance an application stage', function () {
    $job = Scout::job()->title('Dev')->description('Desc')->publish()->create();
    $candidate = Scout::candidate()->name('John')->email('john@example.com')->create();
    $application = Scout::application()->for($job)->from($candidate)->submit();

    $interviewStage = Stage::create(['name' => 'Interview', 'slug' => 'interview']);

    Scout::advance($application, $interviewStage);

    expect($application->fresh()->stage->slug)->toBe('interview');
});

test('it can reject an application', function () {
    $job = Scout::job()->title('Dev')->description('Desc')->publish()->create();
    $candidate = Scout::candidate()->name('John')->email('john@example.com')->create();
    $application = Scout::application()->for($job)->from($candidate)->submit();

    Scout::reject($application, 'Lack of skills');

    expect($application->fresh()->status)->toBe('rejected');
    expect($application->rejection_reason)->toBe('Lack of skills');
});

test('it tracks basic analytics', function () {
    $job = Scout::job()->title('Dev')->description('Desc')->publish()->create();
    $candidate = Scout::candidate()->name('John')->email('john@example.com')->create();
    Scout::application()->for($job)->from($candidate)->submit();

    $analytics = Scout::analytics()->overview();

    expect($analytics['total_jobs'])->toBe(1);
    expect($analytics['total_applicants'])->toBe(1);
    expect($analytics['total_applications'])->toBe(1);
});
