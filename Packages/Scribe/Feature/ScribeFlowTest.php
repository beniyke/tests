<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Feature tests for Scribe blogging flow.
 */

namespace Tests\Packages\Scribe\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Helpers\String\Str;
use Scribe\Models\Post;
use Scribe\Scribe;

beforeEach(function () {
    $this->refreshDatabase();
    $this->bootPackage('Audit', runMigrations: true);
    $this->bootPackage('Scribe', runMigrations: true);

    $this->user = User::create([
        'refid' => Str::random('secure'),
        'name' => 'Test Author',
        'gender' => 'male',
        'email' => 'author@example.com',
        'password' => 'password',
        'status' => UserStatus::Active
    ]);
});

test('user can create a post with tags and track views', function () {
    // 1. Create Post via Facade
    $post = Scribe::post()
        ->title('Feature Flow Post')
        ->content('Detailed content here.')
        ->status('published')
        ->create();

    expect(Post::count())->toBe(1);

    // 2. Record Multiple Views
    Scribe::recordView($post, userId: (int) $this->user->id);
    Scribe::recordView($post, userId: 2); // Random ID for guest-like view
    Scribe::recordView($post, userId: (int) $this->user->id); // Repeat view

    // 3. Verify Analytics
    $totalViews = Scribe::analytics()->getTotalViews($post);
    expect($totalViews)->toBe(3);

    // 4. Verify Trends
    $trends = Scribe::analytics()->getPostTrends($post);
    expect($trends)->not->toBeEmpty();
    expect($trends[0]['count'])->toBe(3);
});

test('guest users can add comments expecting approval', function () {
    $post = Scribe::post()
        ->title('Comment Test')
        ->content('Content')
        ->create();

    $comment = Scribe::addComment($post, [
        'content' => 'Nice post!'
    ]);

    expect($comment->status)->toBe('pending');
    expect($comment->content)->toBe('Nice post!');
    expect($post->comments()->count())->toBe(1);
});
