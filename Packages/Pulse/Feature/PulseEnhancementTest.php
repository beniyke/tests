<?php

declare(strict_types=1);

use App\Models\User;
use Pulse\Pulse;
use Testing\Support\DatabaseTestHelper;

beforeEach(function () {
    $this->refreshDatabase();
    DatabaseTestHelper::runPackageMigrations('Pulse');
    DatabaseTestHelper::runPackageMigrations('Audit');

    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'gender' => 'male',
        'refid' => 'USR_PULSE_ENHANCE_TEST',
    ]);
});

test('it can block and unblock a post', function () {
    $channel = Pulse::channel()->name('General')->create();
    $thread = Pulse::thread()->by($this->user)->in($channel)->title('Title')->content('Content')->create();
    $post = $thread->posts->first();

    expect($post->fresh()->status)->toBe('active');

    Pulse::block($post);
    expect($post->fresh()->status)->toBe('blocked');

    Pulse::unblock($post);
    expect($post->fresh()->status)->toBe('active');
});

test('it can block and unblock a thread', function () {
    $channel = Pulse::channel()->name('General')->create();
    $thread = Pulse::thread()->by($this->user)->in($channel)->title('Title')->content('Content')->create();

    expect($thread->fresh()->status)->toBe('active');

    Pulse::block($thread);
    expect($thread->fresh()->status)->toBe('blocked');

    Pulse::unblock($thread);
    expect($thread->fresh()->status)->toBe('active');
});

test('it can retrieve subscribed threads', function () {
    $channel = Pulse::channel()->name('General')->create();
    $thread1 = Pulse::thread()->by($this->user)->in($channel)->title('Thread 1')->content('Hi')->create();
    $thread2 = Pulse::thread()->by($this->user)->in($channel)->title('Thread 2')->content('Hello')->create();
    $thread3 = Pulse::thread()->by($this->user)->in($channel)->title('Thread 3')->content('Hey')->create();

    Pulse::subscribe($this->user, $thread1);
    Pulse::subscribe($this->user, $thread3);

    $subscriptions = Pulse::getSubscribedThreads($this->user);

    expect($subscriptions)->toHaveCount(2);

    $titles = array_map(fn ($t) => $t->title, $subscriptions);
    expect($titles)->toContain('Thread 1');
    expect($titles)->toContain('Thread 3');
    expect($titles)->not->toContain('Thread 2');
});
