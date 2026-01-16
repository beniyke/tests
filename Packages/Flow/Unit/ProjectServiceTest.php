<?php

declare(strict_types=1);

namespace Tests\Packages\Flow\Unit;

use App\Models\User;
use Flow\Models\Project;
use Flow\Services\ProjectService;
use Tests\Packages\Flow\Helpers\SetupFlow;

uses(SetupFlow::class);

beforeEach(function () {
    $this->setupFlow();
    $this->service = resolve(ProjectService::class);
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'gender' => 'male',
        'refid' => 'USR' . uniqid(),
        'password' => password_hash('password', PASSWORD_DEFAULT),
    ]);
});

it('can create a project', function () {
    $project = $this->service->create([
        'name' => 'Test Project',
        'description' => 'A test project'
    ], $this->user);

    expect($project)->toBeInstanceOf(Project::class)
        ->name->toBe('Test Project')
        ->owner_id->toBe($this->user->id);

    expect(Project::count())->toBe(1);
});

it('creates default columns for new project', function () {
    $project = $this->service->create(['name' => 'Test Project'], $this->user);

    expect($project->columns)->toHaveCount(3);
    expect($project->columns->pluck('name'))->toContain('To Do', 'In Progress', 'Done');
});
