<?php

declare(strict_types=1);

namespace Tests\Packages\Flow\Unit;

use App\Models\User;
use Core\Ioc\Container;
use Core\Services\ConfigServiceInterface;
use Flow\Flow;
use Flow\Models\Project;
use Flow\Models\Reminder;
use Flow\Models\Task;
use Flow\Services\ReminderService;
use Flow\Services\TaskService;
use Helpers\DateTimeHelper;
use Mail\Contracts\MailDriverInterface;
use Mail\MailStatus;
use Mockery;
use Tests\Packages\Flow\Helpers\SetupFlow;

uses(SetupFlow::class);

beforeEach(function () {
    // Database setup
    $this->setupFlow();

    // Config setup for URLs (needed for notifications)
    $config = resolve(ConfigServiceInterface::class);
    $config->set('flow.urls.task', 'http://localhost/tasks');

    $this->mailDriver = Mockery::mock(MailDriverInterface::class);
    Container::getInstance()->instance(MailDriverInterface::class, $this->mailDriver);

    $this->reminderService = resolve(ReminderService::class);
    $this->taskService = resolve(TaskService::class);
});

test('it creates reminder using fluent api', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'gender' => 'male',
        'refid' => 'USR' . uniqid()
    ]);

    $project = Project::create([
        'name' => 'Test Project',
        'owner_id' => $user->id,
        'refid' => 'PRJ' . uniqid()
    ]);

    $task = Task::create([
        'title' => 'Test Task',
        'project_id' => $project->id,
        'due_date' => DateTimeHelper::now()->addDays(2)
    ]);

    $reminder = Flow::reminders()->make()
        ->for($task)
        ->notify($user)
        ->beforeDue()
        ->at('2 hours')
        ->save();

    expect($reminder)->toBeInstanceOf(Reminder::class)
        ->and($reminder->task_id)->toBe($task->id)
        ->and($reminder->user_id)->toBe($user->id)
        ->and($reminder->type)->toBe('before_due')
        ->and($reminder->value)->toBe(2)
        ->and($reminder->unit)->toBe('hours')
        ->and($reminder->status)->toBe('active');

    $expected = $task->due_date->copy()->subHours(2);
    expect($reminder->remind_at->timestamp)->toBe($expected->timestamp);
});

test('it processes due reminders and sends email', function () {
    $user = User::create([
        'name' => 'Reminder User',
        'email' => 'remind@example.com',
        'password' => 'password',
        'gender' => 'female',
        'refid' => 'USR' . uniqid()
    ]);

    $project = Project::create([
        'name' => 'Reminder Project',
        'owner_id' => $user->id,
        'refid' => 'PRJ' . uniqid()
    ]);

    $task = Task::create([
        'title' => 'Urgent Task',
        'project_id' => $project->id,
        'priority' => 'high',
        'due_date' => DateTimeHelper::now()->addHour()
    ]);

    Reminder::create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'type' => 'custom',
        'value' => 0,
        'unit' => 'minutes',
        'status' => 'active',
        'remind_at' => DateTimeHelper::now()->subHour() // 1 hour ago
    ]);

    // Expectation: Email sent
    $this->mailDriver->shouldReceive('send')
        ->once() // Expect ONE email
        ->withArgs(function ($from, $subject, $recipients, $message, $attachments = []) {
            return true;
        })
        ->andReturn(new MailStatus(true, 'Sent', []));

    $count = $this->reminderService->processReminders();
    expect($count)->toBe(1);

    // Check status updated
    $updatedReminder = Reminder::where('task_id', $task->id)->first();
    expect($updatedReminder->status)->toBe('sent');
});

test('it sends notification when assigning task', function () {
    $user = User::create([
        'name' => 'Assignee',
        'email' => 'assignee@example-assign.com',
        'password' => 'password',
        'gender' => 'other',
        'refid' => 'USR' . uniqid()
    ]);

    $project = Project::create([
        'name' => 'Assign Project',
        'owner_id' => $user->id,
        'refid' => 'PRJ' . uniqid()
    ]);

    $task = Task::create(['title' => 'Assigned Task', 'project_id' => $project->id]);

    // Expectation: Email sent
    $this->mailDriver->shouldReceive('send')
        ->once()
        ->withArgs(function ($from, $subject, $to, $message, $attachments = []) use ($user) {
            return str_contains($subject, 'New Task Assigned') && array_key_exists($user->email, $to['to']);
        })
        ->andReturn(new MailStatus(true, 'Sent', []));

    $this->taskService->addAssignee($task, $user);

    $task->refresh();

    $ids = $task->assignees->pluck('id');
    expect(in_array($user->id, $ids))->toBeTrue();
});
