<?php

declare(strict_types=1);

namespace Tests\Packages\Flow\Unit;

use App\Models\User;
use Flow\Models\Task;
use Flow\Services\ProjectService;
use Flow\Services\TaskService;
use Tests\Packages\Flow\Helpers\SetupFlow;

uses(SetupFlow::class);

beforeEach(function () {
    $this->setupFlow();
    $this->projectService = resolve(ProjectService::class);
    $this->taskService = resolve(TaskService::class);
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'gender' => 'male',
        'refid' => 'USR' . uniqid(),
        'password' => password_hash('password', PASSWORD_DEFAULT),
    ]);
    $this->project = $this->projectService->create(['name' => 'Task Project'], $this->user);
});

it('can create a task', function () {
    $task = $this->taskService->create([
        'project_id' => $this->project->id,
        'title' => 'My First Task'
    ], $this->user);

    expect($task)->toBeInstanceOf(Task::class)
        ->title->toBe('My First Task')
        ->project_id->toBe($this->project->id);

    expect(Task::count())->toBe(1);
});

it('assigns task to first column by default', function () {
    $task = $this->taskService->create([
        'project_id' => $this->project->id,
        'title' => 'Default Column Task'
    ], $this->user);

    $firstColumn = $this->project->columns()->orderBy('order')->first();
    expect($task->column_id)->toBe($firstColumn->id);
});
