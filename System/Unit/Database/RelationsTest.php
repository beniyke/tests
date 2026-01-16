<?php

declare(strict_types=1);

use Database\DB;
use Database\Relations\BelongsTo;
use Database\Relations\BelongsToMany;
use Database\Relations\HasMany;
use Database\Relations\HasManyThrough;
use Database\Relations\HasOne;
use Database\Relations\MorphMany;
use Database\Relations\MorphOne;
use Database\Relations\MorphTo;
use Database\Schema\Schema;
use Testing\Support\DatabaseTestHelper;
use Tests\System\Fixtures\Models\RelationCountry;
use Tests\System\Fixtures\Models\RelationImage;
use Tests\System\Fixtures\Models\RelationPost;
use Tests\System\Fixtures\Models\RelationUser;

beforeEach(function () {
    // Setup in-memory database
    DatabaseTestHelper::resetDefaultConnection();
    $this->connection = DatabaseTestHelper::setupInMemoryDatabase();
    DB::setDefaultConnection($this->connection);
    Schema::setConnection($this->connection);

    // Create test tables via helper
    DatabaseTestHelper::createRelationSchema();
});

afterEach(function () {
    DatabaseTestHelper::dropAllTables();
});

describe('Relations - HasOne', function () {
    test('defines hasOne relationship', function () {
        $user = new RelationUser();
        $relation = $user->profile();

        expect($relation)->toBeInstanceOf(HasOne::class);
    });

    test('loads hasOne relationship', function () {
        $userId = $this->connection->table('test_rel_users')->insertGetId([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->connection->table('test_rel_profiles')->insert([
            'user_id' => $userId,
            'bio' => 'Developer',
            'avatar' => 'avatar.jpg',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $user = RelationUser::find($userId);
        $profile = $user->profile;

        expect($profile)->not->toBeNull();
        expect($profile->bio)->toBe('Developer');
    });
});

describe('Relations - HasMany', function () {
    test('defines hasMany relationship', function () {
        $user = new RelationUser();
        $relation = $user->posts();

        expect($relation)->toBeInstanceOf(HasMany::class);
    });

    test('loads hasMany relationship', function () {
        $userId = $this->connection->table('test_rel_users')->insertGetId([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->connection->table('test_rel_posts')->insert([
            'user_id' => $userId,
            'title' => 'First Post',
            'content' => 'Content 1',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->connection->table('test_rel_posts')->insert([
            'user_id' => $userId,
            'title' => 'Second Post',
            'content' => 'Content 2',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $user = RelationUser::find($userId);
        $posts = $user->posts;

        expect($posts)->toHaveCount(2);
    });
});

describe('Relations - BelongsTo', function () {
    test('defines belongsTo relationship', function () {
        $post = new RelationPost();
        $relation = $post->user();

        expect($relation)->toBeInstanceOf(BelongsTo::class);
    });

    test('loads belongsTo relationship', function () {
        $userId = $this->connection->table('test_rel_users')->insertGetId([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $postId = $this->connection->table('test_rel_posts')->insertGetId([
            'user_id' => $userId,
            'title' => 'Test Post',
            'content' => 'Content',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $post = RelationPost::find($postId);
        $user = $post->user;

        expect($user)->not->toBeNull();
        expect($user->name)->toBe('John Doe');
    });
});

describe('Relations - BelongsToMany', function () {
    test('defines belongsToMany relationship', function () {
        $user = new RelationUser();
        $relation = $user->roles();

        expect($relation)->toBeInstanceOf(BelongsToMany::class);
    });

    test('loads belongsToMany relationship', function () {
        $userId = $this->connection->table('test_rel_users')->insertGetId([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $adminId = $this->connection->table('test_rel_roles')->insertGetId([
            'name' => 'Admin',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $editorId = $this->connection->table('test_rel_roles')->insertGetId([
            'name' => 'Editor',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->connection->table('test_rel_user_roles')->insert([
            'user_id' => $userId,
            'role_id' => $adminId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->connection->table('test_rel_user_roles')->insert([
            'user_id' => $userId,
            'role_id' => $editorId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $user = RelationUser::find($userId);
        $roles = $user->roles;

        expect($roles)->toHaveCount(2);
    });
});

describe('Relations - HasManyThrough', function () {
    test('defines hasManyThrough relationship', function () {
        $country = new RelationCountry();
        $relation = $country->posts();

        expect($relation)->toBeInstanceOf(HasManyThrough::class);
    });

    test('loads hasManyThrough relationship', function () {
        $countryId = $this->connection->table('test_rel_countries')->insertGetId([
            'name' => 'USA',
        ]);

        $userId = $this->connection->table('test_rel_users')->insertGetId([
            'country_id' => $countryId,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->connection->table('test_rel_posts')->insert([
            'user_id' => $userId,
            'title' => 'Post 1',
            'content' => 'Content 1',
        ]);

        $this->connection->table('test_rel_posts')->insert([
            'user_id' => $userId,
            'title' => 'Post 2',
            'content' => 'Content 2',
        ]);

        $country = RelationCountry::find($countryId);
        $posts = $country->posts;

        expect($posts)->toHaveCount(2);
        expect($posts[0]->title)->toBe('Post 1');
    });
});

describe('Relations - Polymorphic Relations', function () {
    test('defines morphOne relationship', function () {
        $user = new RelationUser();
        $relation = $user->image();

        expect($relation)->toBeInstanceOf(MorphOne::class);
    });

    test('loads morphOne relationship', function () {
        $userId = $this->connection->table('test_rel_users')->insertGetId([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->connection->table('test_rel_images')->insert([
            'url' => 'user-avatar.jpg',
            'imageable_id' => $userId,
            'imageable_type' => RelationUser::class,
        ]);

        $user = RelationUser::find($userId);
        $image = $user->image;

        expect($image)->not->toBeNull();
        expect($image->url)->toBe('user-avatar.jpg');
    });

    test('defines morphMany relationship', function () {
        $post = new RelationPost();
        $relation = $post->images();

        expect($relation)->toBeInstanceOf(MorphMany::class);
    });

    test('loads morphMany relationship', function () {
        $postId = $this->connection->table('test_rel_posts')->insertGetId([
            'user_id' => 1,
            'title' => 'Test Post',
            'content' => 'Content',
        ]);

        $this->connection->table('test_rel_images')->insert([
            'url' => 'post-image-1.jpg',
            'imageable_id' => $postId,
            'imageable_type' => RelationPost::class,
        ]);

        $this->connection->table('test_rel_images')->insert([
            'url' => 'post-image-2.jpg',
            'imageable_id' => $postId,
            'imageable_type' => RelationPost::class,
        ]);

        $post = RelationPost::find($postId);
        $images = $post->images;

        expect($images)->toHaveCount(2);
    });

    test('defines morphTo relationship', function () {
        $image = new RelationImage();
        $relation = $image->imageable();

        expect($relation)->toBeInstanceOf(MorphTo::class);
    });

    test('loads morphTo relationship', function () {
        $userId = $this->connection->table('test_rel_users')->insertGetId([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $imageId = $this->connection->table('test_rel_images')->insertGetId([
            'url' => 'avatar.jpg',
            'imageable_id' => $userId,
            'imageable_type' => RelationUser::class,
        ]);

        $image = RelationImage::find($imageId);
        $imageable = $image->imageable;

        expect($imageable)->toBeInstanceOf(RelationUser::class);
        expect($imageable->name)->toBe('John Doe');
    });
});

describe('Relations - Eager Loading', function () {
    test('eager loads multiple relationships', function () {
        $userId = $this->connection->table('test_rel_users')->insertGetId([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->connection->table('test_rel_profiles')->insert([
            'user_id' => $userId,
            'bio' => 'Developer',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->connection->table('test_rel_posts')->insert([
            'user_id' => $userId,
            'title' => 'Post 1',
            'content' => 'Content',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $users = RelationUser::with(['profile', 'posts'])->get();

        expect($users)->toHaveCount(1);
        expect($users[0]->getRelations())->toHaveKey('profile');
        expect($users[0]->getRelations())->toHaveKey('posts');
    });
});
