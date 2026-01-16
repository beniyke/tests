<?php

declare(strict_types=1);

namespace Tests\Packages\Flow\Unit;

use App\Models\User;
use Core\Services\ConfigServiceInterface;
use Flow\Flow;
use Flow\Models\Column;
use Flow\Models\Project;
use Flow\Models\Task;
use Helpers\DateTimeHelper;
use Tests\Packages\Flow\Helpers\SetupFlow;

uses(SetupFlow::class);

beforeEach(function () {
    $this->setupFlow();
    $this->config = resolve(ConfigServiceInterface::class);
    $this->config->set('flow.urls.task', 'http://localhost/tasks');
});

test('it helps user retrieves kanban board data', function () {
    $user = User::create(['name' => 'User K', 'email' => 'k@test.com', 'password' => 'Pass', 'refid' => 'K1', 'gender' => 'other']);
    $project = Project::create(['name' => 'Kanban Proj', 'owner_id' => $user->id, 'refid' => 'KP1']);

    // Create columns
    $col1 = Column::create(['project_id' => $project->id, 'name' => 'Todo', 'type' => 'todo', 'order' => 1]);
    $col2 = Column::create(['project_id' => $project->id, 'name' => 'Done', 'type' => 'done', 'order' => 2]);

    // Create tasks
    $t1 = Task::create(['title' => 'T1', 'project_id' => $project->id, 'column_id' => $col1->id, 'refid' => 'T1']);
    $t2 = Task::create(['title' => 'T2', 'project_id' => $project->id, 'column_id' => $col2->id, 'refid' => 'T2']);

    $data = Flow::reports()->for($project)->kanbanData();

    // Check structure
    expect($data)->toBeArray();
    // Assuming allColumns fetches all columns created
    // Filter for our columns if implementation fetches all global columns
    // The implementation fetches all columns.

    $todoCol = array_filter($data, fn ($c) => $c['id'] == $col1->id);
    $doneCol = array_filter($data, fn ($c) => $c['id'] == $col2->id);

    expect($todoCol)->not->toBeEmpty();
    expect(reset($todoCol)['tasks'])->toHaveCount(1);
    expect(reset($todoCol)['tasks'][0]['id'])->toEqual($t1->id);

    expect($doneCol)->not->toBeEmpty();
    expect(reset($doneCol)['tasks'])->toHaveCount(1);
    expect(reset($doneCol)['tasks'][0]['id'])->toEqual($t2->id);
});

test('it calculates user task stats', function () {
    $user1 = User::create(['name' => 'U1', 'email' => 'u1@t.com', 'password' => 'P', 'refid' => 'U1', 'gender' => 'male']);
    $user2 = User::create(['name' => 'U2', 'email' => 'u2@t.com', 'password' => 'P', 'refid' => 'U2', 'gender' => 'female']);
    $project = Project::create(['name' => 'Stats Proj', 'owner_id' => $user1->id, 'refid' => 'SP1']);

    $doneCol = Column::create(['project_id' => $project->id, 'name' => 'Done', 'type' => 'done']);
    $todoCol = Column::create(['project_id' => $project->id, 'name' => 'Todo', 'type' => 'todo']);

    // T1: User1, Done
    $t1 = Task::create(['title' => 'T1', 'project_id' => $project->id, 'column_id' => $doneCol->id, 'refid' => 'T1']);
    Flow::tasks()->addAssignee($t1, $user1);
    $t1->refresh();
    expect($t1->assignees()->count())->toBe(1);

    // T2: User1, Overdue, Todo
    $t2 = Task::create(['title' => 'T2', 'project_id' => $project->id, 'column_id' => $todoCol->id, 'due_date' => DateTimeHelper::now()->subDay(), 'refid' => 'T2']);
    Flow::tasks()->addAssignee($t2, $user1);

    // T3: User2, Todo
    $t3 = Task::create(['title' => 'T3', 'project_id' => $project->id, 'column_id' => $todoCol->id, 'refid' => 'T3']);
    Flow::tasks()->addAssignee($t3, $user2);

    $stats = Flow::reports()->for($project)->userStats();

    // User1 stats
    $u1Stats = array_values(array_filter($stats, fn ($s) => $s['user']['id'] == $user1->id));

    $u1Stats = $u1Stats[0];
    expect($u1Stats['total'])->toBe(2);
    expect($u1Stats['completed'])->toBe(1);
    expect($u1Stats['overdue'])->toBe(1);

    // User2 stats
    $u2Stats = array_values(array_filter($stats, fn ($s) => $s['user']['id'] == $user2->id))[0];
    expect($u2Stats['total'])->toBe(1);
    expect($u2Stats['completed'])->toBe(0);
    expect($u2Stats['overdue'])->toBe(0);
});

test('it calculates task distribution', function () {
    $user = User::create(['name' => 'Owner', 'email' => 'o@t.com', 'password' => 'P', 'refid' => 'O1', 'gender' => 'other']);
    $project = Project::create(['name' => 'Dist Proj', 'owner_id' => $user->id, 'refid' => 'DP1']);

    Task::create(['title' => 'High 1', 'project_id' => $project->id, 'priority' => 'high', 'refid' => 'H1']);
    Task::create(['title' => 'High 2', 'project_id' => $project->id, 'priority' => 'high', 'refid' => 'H2']);
    Task::create(['title' => 'Low 1', 'project_id' => $project->id, 'priority' => 'low', 'refid' => 'L1']);

    $dist = Flow::reports()->for($project)->taskDistribution('priority');

    expect($dist['high'])->toBe(2);
    expect($dist['low'])->toBe(1);
});
