<?php

declare(strict_types=1);

namespace Tests\Packages\Flow\Unit;

use App\Models\User;
use Flow\Flow;
use Flow\Models\Attachment;
use Flow\Models\Project;
use Flow\Models\Tag;
use Flow\Models\Task;
use Tests\Packages\Flow\Helpers\SetupFlow;

uses(SetupFlow::class);

beforeEach(function () {
    $this->setupFlow();
    $this->user = User::create([
        'name' => 'Fluent Tester',
        'email' => 'fluent@example.com',
        'gender' => 'male',
        'refid' => 'USR' . uniqid(),
        'password' => password_hash('password', PASSWORD_DEFAULT),
    ]);
});

it('can create a project fluently', function () {
    $project = Flow::projects()->make()
        ->name('Fluent Project')
        ->description('Testing fluent API')
        ->owner($this->user)
        ->save();

    expect($project)->toBeInstanceOf(Project::class)
        ->name->toBe('Fluent Project');

    expect($project->columns()->count())->toBeGreaterThan(0);
});

it('can create a task fluently', function () {
    $project = Flow::projects()->make()
        ->name('Task Project')
        ->owner($this->user)
        ->save();

    $task = Flow::tasks()->make()
        ->title('Fluent Task')
        ->project($project->id)
        ->priority('high')
        ->creator($this->user)
        ->save();

    expect($task)->toBeInstanceOf(Task::class)
        ->title->toBe('Fluent Task')
        ->priority->toBe('high');
});

it('can add tags fluently', function () {
    $project = Flow::projects()->make()
        ->name('Tag Project')
        ->owner($this->user)
        ->save();

    $task = Flow::tasks()->make()
        ->title('Tagged Task')
        ->project($project->id)
        ->creator($this->user)
        ->save();

    Flow::tasks()->addTag($task, 'NewTag');

    $tag = $task->tags()->first();
    expect($tag)->not->toBeNull();
    expect($task->tags()->first()->name)->toBe('NewTag');
    expect(Tag::count())->toBe(1);

    // Test duplicate avoidance
    Flow::tasks()->addTag($task, 'NewTag');
    expect($task->tags()->count())->toBe(1);
});

it('can setup recurring tasks fluently', function () {
    $project = Flow::projects()->make()
        ->name('Recurring Project')
        ->owner($this->user)
        ->save();

    $task = Flow::tasks()->make()
        ->title('Recurring Task')
        ->project($project->id)
        ->creator($this->user)
        ->save();

    Flow::recurring()->for($task)
        ->weekly()
        ->startingAt('next Monday')
        ->save();

    $task->refresh();
    expect($task->is_recurring)->toBeTrue();
    expect($task->recurrence_pattern)->toBe('weekly');
    expect($task->next_recurrence_at)->not->toBeNull();
});

it('can access reports fluently', function () {
    $project = Flow::projects()->make()
        ->name('Report Project')
        ->owner($this->user)
        ->save();

    $rate = Flow::reports()->for($project)->completionRate();
    $burndown = Flow::reports()->for($project)->burndown();

    expect($rate)->toBeFloat();
    expect($burndown)->toBeArray();
});

it('can attach files fluently', function () {
    $project = Flow::projects()->make()
        ->name('Attachment Project')
        ->owner($this->user)
        ->save();

    $task = Flow::tasks()->make()
        ->title('File Task')
        ->project($project->id)
        ->creator($this->user)
        ->save();

    $attachment = Flow::collaboration()->makeAttachment()
        ->to($task)
        ->by($this->user)
        ->path('test.png')
        ->filename('test.png')
        ->mime('image/png')
        ->size(1024)
        ->save();

    expect($attachment)->toBeInstanceOf(Attachment::class)
        ->filename->toBe('test.png');
});
