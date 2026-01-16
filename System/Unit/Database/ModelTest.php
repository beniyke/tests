<?php

declare(strict_types=1);

use Database\DB;
use Database\Schema\Schema;
use Testing\Support\DatabaseTestHelper;
use Tests\System\Fixtures\Models\TestModel;

beforeEach(function () {
    // Setup in-memory database
    DatabaseTestHelper::resetDefaultConnection();
    $this->connection = DatabaseTestHelper::setupInMemoryDatabase();
    DB::setDefaultConnection($this->connection);
    Schema::setConnection($this->connection);

    // Create test table via helper
    DatabaseTestHelper::createModelSchema();
});

afterEach(function () {
    Schema::dropIfExists('test_rel_models');
});

describe('BaseModel - Basic Operations', function () {
    test('sets table name', function () {
        $model = new TestModel();
        expect($model->getTable())->toBe('test_rel_models');
    });

    test('sets fillable attributes', function () {
        $model = new TestModel();
        $model->fill(['name' => 'John', 'email' => 'john@example.com']);

        expect($model->name)->toBe('John');
        expect($model->email)->toBe('john@example.com');
    });

    test('guards non-fillable attributes', function () {
        $model = new TestModel();
        $model->fill(['name' => 'John', 'admin' => true]);

        expect($model->name)->toBe('John');
        expect(isset($model->admin))->toBeFalse();
    });

    test('hides attributes in array', function () {
        $model = new TestModel();
        $model->name = 'John';
        $model->password = 'secret';

        $array = $model->toArray();
        expect($array)->toHaveKey('name');
        expect($array)->not->toHaveKey('password');
    });

    test('casts attributes', function () {
        $model = new TestModel();
        $model->is_active = '1';

        expect($model->is_active)->toBeTrue();
    });

    test('checks if attribute exists', function () {
        $model = new TestModel();
        $model->name = 'John';

        expect(isset($model->name))->toBeTrue();
        expect(isset($model->nonexistent))->toBeFalse();
    });

    test('gets dirty attributes', function () {
        $model = new TestModel();
        $model->name = 'John';
        $model->email = 'john@example.com';

        expect($model->isDirty())->toBeTrue();
        expect($model->getDirty())->toHaveKey('name');
        expect($model->getDirty())->toHaveKey('email');
    });

    test('checks if model is new', function () {
        $model = new TestModel();
        expect($model->isNew())->toBeTrue();

        $model->id = 1;
        expect($model->isNew())->toBeFalse();
    });
});

describe('BaseModel - Database Operations', function () {
    test('creates a new record', function () {
        $model = TestModel::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
        ]);

        expect($model->id)->toBeGreaterThan(0);
        expect($model->name)->toBe('John Doe');
        expect($model->email)->toBe('john@example.com');
    });

    test('finds record by ID', function () {
        $created = TestModel::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $found = TestModel::find($created->id);

        expect($found)->not->toBeNull();
        expect($found->id)->toEqual($created->id);
        expect($found->name)->toBe('Jane Doe');
    });

    test('gets all records', function () {
        TestModel::create(['name' => 'User 1', 'email' => 'user1@example.com']);
        TestModel::create(['name' => 'User 2', 'email' => 'user2@example.com']);
        TestModel::create(['name' => 'User 3', 'email' => 'user3@example.com']);

        $all = TestModel::all();
        expect($all)->toHaveCount(3);
    });

    test('updates a record', function () {
        $model = TestModel::create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $model->name = 'Updated Name';
        $model->save();

        $updated = TestModel::find($model->id);
        expect($updated->name)->toBe('Updated Name');
    });

    test('deletes a record', function () {
        $model = TestModel::create([
            'name' => 'To Delete',
            'email' => 'delete@example.com',
        ]);

        $id = $model->id;
        $model->delete();

        $deleted = TestModel::find($id);
        expect($deleted)->toBeNull();
    });
});

describe('BaseModel - Query Scopes', function () {
    test('applies where clause', function () {
        TestModel::create(['name' => 'Active User', 'email' => 'active@example.com', 'status' => 'active']);
        TestModel::create(['name' => 'Inactive User', 'email' => 'inactive@example.com', 'status' => 'inactive']);

        $active = TestModel::where('status', 'active')->get();

        expect($active)->toHaveCount(1);
        expect($active[0]->name)->toBe('Active User');
    });

    test('counts records', function () {
        TestModel::create(['name' => 'User 1', 'email' => 'user1@example.com']);
        TestModel::create(['name' => 'User 2', 'email' => 'user2@example.com']);

        $count = TestModel::count();
        expect($count)->toBe(2);
    });

    test('finds first record', function () {
        TestModel::create(['name' => 'First', 'email' => 'first@example.com']);
        TestModel::create(['name' => 'Second', 'email' => 'second@example.com']);

        $first = TestModel::first();
        expect($first)->not->toBeNull();
        expect($first->name)->toBe('First');
    });
});

describe('BaseModel - Timestamps', function () {
    test('has timestamps enabled by default', function () {
        $model = new TestModel();
        expect($model->usesTimestamps())->toBeTrue();
    });

    test('automatically sets timestamps on create', function () {
        $model = TestModel::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        expect($model->created_at)->not->toBeNull();
        expect($model->updated_at)->not->toBeNull();
    });

    test('updates timestamp on save', function () {
        $model = TestModel::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $originalUpdatedAt = $model->updated_at;

        // Wait a moment and update
        sleep(1);
        $model->name = 'Jane Doe';
        $model->save();

        $updated = TestModel::find($model->id);
        expect($updated->updated_at)->not->toBe($originalUpdatedAt);
    });
});

describe('BaseModel - Serialization', function () {
    test('converts to array', function () {
        $model = new TestModel();
        $model->name = 'John';
        $model->email = 'john@example.com';

        $array = $model->toArray();
        expect($array)->toBeArray();
        expect($array)->toHaveKey('name');
    });

    test('converts to JSON', function () {
        $model = new TestModel();
        $model->name = 'John';

        $json = $model->toJson();
        expect($json)->toBeString();
        expect(json_decode($json))->not->toBeNull();
    });
});
