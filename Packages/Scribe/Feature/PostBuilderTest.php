<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Unit tests for PostBuilder.
 */

namespace Tests\Packages\Scribe\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Helpers\String\Str;
use RuntimeException;
use Scribe\Models\Category;
use Scribe\Models\Post;
use Scribe\Scribe;
use Scribe\Services\Builders\PostBuilder;

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

    $this->category = Category::create([
        'refid' => 'cat_123',
        'name' => 'Technology',
        'slug' => 'technology'
    ]);
});

test('post builder can create a basic post', function () {
    $post = Scribe::post()
        ->title('Test Post')
        ->content('This is a test content.')
        ->create();

    expect($post)->toBeInstanceOf(Post::class);
    expect($post->title)->toBe('Test Post');
    expect($post->slug)->toBe('test-post');
    expect($post->status)->toBe('draft');
    expect($post->refid)->toStartWith('pst_');
});

test('post builder handles manual slugs', function () {
    $post = Scribe::post()
        ->title('Test Post')
        ->slug('custom-slug')
        ->content('Content')
        ->create();

    expect($post->slug)->toBe('custom-slug');
});

test('post builder can set categories and authors', function () {
    $post = Scribe::post()
        ->title('Taxonomy Test')
        ->content('Content')
        ->category((int) $this->category->id)
        ->author((int) $this->user->id)
        ->create();

    expect($post->scribe_category_id)->toBe((int) $this->category->id);
    expect($post->user_id)->toBe((int) $this->user->id);
});

test('post builder requires a title', function () {
    expect(fn () => resolve(PostBuilder::class)->create())
        ->toThrow(RuntimeException::class, 'Post title is required.');
});

test('post builder handles seo metadata', function () {
    $post = Scribe::post()
        ->title('SEO Post')
        ->content('Content')
        ->seo(['title' => 'Meta Title', 'description' => 'Meta Desc'])
        ->create();

    expect($post->seo_meta)->toBe([
        'title' => 'Meta Title',
        'description' => 'Meta Desc'
    ]);
});
