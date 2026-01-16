<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Unit tests for ScribeManagerService.
 */

namespace Tests\Packages\Scribe\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Helpers\DateTimeHelper;
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

test('scribe manager can publish a post', function () {
    $post = Post::create([
        'refid' => 'pst_123',
        'title' => 'Draft Post',
        'slug' => 'draft',
        'content' => 'Content',
        'status' => 'draft'
    ]);

    Scribe::publish($post);

    $post->refresh();
    expect($post->status)->toBe('published');
    expect($post->published_at)->not->toBeNull();
});

test('scribe manager can schedule a post', function () {
    $post = Post::create([
        'refid' => 'pst_456',
        'title' => 'Future Post',
        'slug' => 'future',
        'content' => 'Content',
        'status' => 'draft'
    ]);

    $future = DateTimeHelper::now()->addDays(5);
    Scribe::schedule($post, $future);

    $post->refresh();
    expect($post->status)->toBe('scheduled');
    expect($post->published_at->toDateTimeString())->toBe($future->toDateTimeString());
});

test('scribe manager generates seo meta defaults', function () {
    $post = Post::create([
        'refid' => 'pst_789',
        'title' => 'SEO Default Post',
        'slug' => 'seo-default',
        'content' => 'This is a long content that should be truncated for description.',
        'excerpt' => 'Manual excerpt'
    ]);

    $meta = Scribe::generateSeoMeta($post);

    expect($meta['title'])->toBe('SEO Default Post');
    expect($meta['description'])->toBe('Manual excerpt');
});
