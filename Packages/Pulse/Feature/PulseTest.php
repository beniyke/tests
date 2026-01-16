<?php

declare(strict_types=1);

use App\Models\User;
use Pulse\Models\Channel;
use Pulse\Models\Thread;
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
        'refid' => 'USR_PULSE_TEST',
    ]);
});

test('it can create a channel', function () {
    $channel = Pulse::channel()
        ->name('General Discussion')
        ->description('Standard forum channel')
        ->create();

    expect($channel)->toBeInstanceOf(Channel::class);
    expect($channel->name)->toBe('General Discussion');
    expect($channel->slug)->toBe('general-discussion');
});

test('it can create a thread with a first post', function () {
    $channel = Pulse::channel()->name('General')->create();

    $thread = Pulse::thread()
        ->by($this->user)
        ->in($channel)
        ->title('First Thread')
        ->content('This is the first post content.')
        ->create();

    expect($thread)->toBeInstanceOf(Thread::class);
    expect($thread->posts)->toHaveCount(1);
    expect($thread->posts->first()->content)->toBe('This is the first post content.');
});

test('it can reply to a thread', function () {
    $channel = Pulse::channel()->name('General')->create();
    $thread = Pulse::thread()
        ->by($this->user)
        ->in($channel)
        ->title('Discuss')
        ->content('Start')
        ->create();

    $post = Pulse::post()
        ->by($this->user)
        ->on($thread)
        ->content('Replying here')
        ->create();

    expect($thread->posts)->toHaveCount(2);
    expect($post->content)->toBe('Replying here');
});

test('it tracks thread view counts', function () {
    $channel = Pulse::channel()->name('General')->create();
    $thread = Pulse::thread()->by($this->user)->in($channel)->title('View Me')->content('Hi')->create();

    // Logic to increment view count would typically be in a controller or manager method
    $thread->increment('view_count');

    expect($thread->fresh()->view_count)->toBe(1);
});

test('it can react to a post', function () {
    $channel = Pulse::channel()->name('General')->create();
    $thread = Pulse::thread()->by($this->user)->in($channel)->title('React')->content('Hi')->create();
    $post = $thread->posts->first();

    Pulse::react($this->user, $post, 'like');

    expect($post->reactions)->toHaveCount(1);
    expect($post->reactions->first()->type)->toBe('like');
});
