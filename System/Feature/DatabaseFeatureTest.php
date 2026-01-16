<?php

declare(strict_types=1);

use Testing\Concerns\RefreshDatabase;
use Tests\System\Fixtures\Models\TestComment;
use Tests\System\Fixtures\Models\TestPost;
use Tests\System\Fixtures\Models\TestUser;

uses(RefreshDatabase::class);

describe('Feature - Complete CRUD Workflow', function () {
    test('creates, reads, updates, and deletes records', function () {
        // CREATE
        $user = TestUser::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
        ]);

        expect($user->id)->toBeGreaterThan(0);
        expect($user->name)->toBe('John Doe');

        // READ
        $foundUser = TestUser::find($user->id);
        expect($foundUser)->not->toBeNull();
        expect($foundUser->email)->toBe('john@example.com');

        // UPDATE
        $foundUser->name = 'Jane Doe';
        $foundUser->save();

        $updatedUser = TestUser::find($user->id);
        expect($updatedUser->name)->toBe('Jane Doe');

        // DELETE
        $updatedUser->delete();

        $deletedUser = TestUser::find($user->id);
        expect($deletedUser)->toBeNull();
    });

    test('performs bulk operations', function () {
        // Bulk insert
        $users = [
            ['name' => 'User 1', 'email' => 'user1@example.com', 'status' => 'active', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'User 2', 'email' => 'user2@example.com', 'status' => 'active', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ['name' => 'User 3', 'email' => 'user3@example.com', 'status' => 'inactive', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
        ];

        TestUser::query()->insert($users);

        $count = TestUser::count();
        expect($count)->toBe(3);

        // Bulk update
        TestUser::where('status', 'active')->update(['status' => 'verified']);

        $verifiedCount = TestUser::where('status', 'verified')->count();
        expect($verifiedCount)->toBe(2);

        // Bulk delete
        TestUser::where('status', 'inactive')->delete();

        $remainingCount = TestUser::count();
        expect($remainingCount)->toBe(2);
    });
});

describe('Feature - Relationships and Eager Loading', function () {
    test('loads nested relationships', function () {
        // Create user
        $user = TestUser::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Create post
        $post = TestPost::create([
            'user_id' => $user->id,
            'title' => 'Test Post',
            'content' => 'Content',
            'published' => true,
        ]);

        // Create comments
        TestComment::create([
            'post_id' => $post->id,
            'content' => 'Comment 1',
        ]);

        TestComment::create([
            'post_id' => $post->id,
            'content' => 'Comment 2',
        ]);

        // Load with nested relationships
        $loadedUser = TestUser::with(['posts.comments'])->find($user->id);

        expect($loadedUser)->not->toBeNull();
        expect(isset($loadedUser->posts))->toBeTrue();
        expect($loadedUser->posts)->toHaveCount(1);
        expect(isset($loadedUser->posts[0]->comments))->toBeTrue();
        expect($loadedUser->posts[0]->comments)->toHaveCount(2);
    });

    test('filters through relationships', function () {
        // Create user with posts
        $user = TestUser::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        TestPost::create([
            'user_id' => $user->id,
            'title' => 'Published Post',
            'content' => 'Content',
            'published' => true,
        ]);

        TestPost::create([
            'user_id' => $user->id,
            'title' => 'Draft Post',
            'content' => 'Content',
            'published' => false,
        ]);

        // Get only published posts
        $publishedPosts = $user->posts()->where('published', true)->get();

        expect($publishedPosts)->toHaveCount(1);
        expect($publishedPosts[0]->title)->toBe('Published Post');
    });
});

describe('Feature - Pagination with Filters', function () {
    test('paginates filtered and sorted results', function () {
        // Create 30 users
        for ($i = 1; $i <= 30; $i++) {
            TestUser::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'status' => $i % 2 === 0 ? 'active' : 'inactive',
            ]);
        }

        // Paginate active users, sorted by name
        $paginator = TestUser::where('status', 'active')
            ->orderBy('name', 'asc')
            ->paginate(5);

        expect($paginator->total())->toBe(15);
        expect($paginator->items())->toHaveCount(5);
        expect($paginator->lastPage())->toBe(3);
    });
});

describe('Feature - Complex Queries', function () {
    test('performs complex query with joins and aggregates', function () {
        // Create users with posts
        $user1 = TestUser::create(['name' => 'User 1', 'email' => 'user1@example.com']);
        $user2 = TestUser::create(['name' => 'User 2', 'email' => 'user2@example.com']);

        // User 1 has 3 posts
        for ($i = 1; $i <= 3; $i++) {
            TestPost::create([
                'user_id' => $user1->id,
                'title' => "Post {$i}",
                'content' => 'Content',
            ]);
        }

        // User 2 has 2 posts
        for ($i = 1; $i <= 2; $i++) {
            TestPost::create([
                'user_id' => $user2->id,
                'title' => "Post {$i}",
                'content' => 'Content',
            ]);
        }

        // Get users with post count
        $users = TestUser::withCount('posts')->get();

        expect($users)->toHaveCount(2);
        expect($users[0]->posts_count)->toBeGreaterThan(0);
    });

    test('performs subquery', function () {
        // Create users
        TestUser::create(['name' => 'Active User', 'email' => 'active@example.com', 'status' => 'active']);
        TestUser::create(['name' => 'Inactive User', 'email' => 'inactive@example.com', 'status' => 'inactive']);

        // Query with where in subquery
        $activeUsers = TestUser::whereIn('status', ['active', 'verified'])->get();

        expect($activeUsers)->toHaveCount(1);
        expect($activeUsers[0]->name)->toBe('Active User');
    });
});

describe('Feature - Model Events and Timestamps', function () {
    test('automatically sets timestamps on create', function () {
        $user = TestUser::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        expect($user->created_at)->not->toBeNull();
        expect($user->updated_at)->not->toBeNull();
    });

    test('updates timestamp on save', function () {
        $user = TestUser::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $originalUpdatedAt = $user->updated_at;

        // Wait a moment and update
        sleep(1);
        $user->name = 'Jane Doe';
        $user->save();

        $updatedUser = TestUser::find($user->id);
        expect($updatedUser->updated_at)->not->toBe($originalUpdatedAt);
    });
});

describe('Feature - Cascade Deletes', function () {
    test('cascades delete through foreign keys', function () {
        // Create user with posts and comments
        $user = TestUser::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $post = TestPost::create([
            'user_id' => $user->id,
            'title' => 'Test Post',
            'content' => 'Content',
        ]);

        TestComment::create([
            'post_id' => $post->id,
            'content' => 'Comment 1',
        ]);

        // Delete user (should cascade to posts and comments)
        $user->delete();

        // Verify cascade
        expect(TestUser::find($user->id))->toBeNull();
        expect(TestPost::find($post->id))->toBeNull();
        expect(TestComment::where('post_id', $post->id)->count())->toBe(0);
    });
});
