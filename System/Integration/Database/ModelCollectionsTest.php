<?php

declare(strict_types=1);

use Tests\System\Fixtures\Models\CollectionTestUser;

describe('ModelCollection - Iteration', function () {
    beforeEach(function () {
        CollectionTestUser::create(['name' => 'John', 'email' => 'john@test.com', 'age' => 30, 'status' => 'active']);
        CollectionTestUser::create(['name' => 'Jane', 'email' => 'jane@test.com', 'age' => 25, 'status' => 'inactive']);
        CollectionTestUser::create(['name' => 'Bob', 'email' => 'bob@test.com', 'age' => 45, 'status' => 'active']);
    });

    test('iterates with each', function () {
        $count = 0;
        $users = CollectionTestUser::all();
        $users->each(function ($user) use (&$count) {
            $count++;
        });

        expect($count)->toBe(3);
    });

    test('is iterable', function () {
        $users = CollectionTestUser::all();
        $count = 0;
        foreach ($users as $user) {
            $count++;
        }
        expect($count)->toBe(3);
    });

    test('is array accessible', function () {
        $users = CollectionTestUser::all();
        expect($users[0])->toBeInstanceOf(CollectionTestUser::class);
        expect(isset($users[0]))->toBeTrue();
        expect(count($users))->toBe(3);
    });
});

describe('ModelCollection - Advanced Operations', function () {
    beforeEach(function () {
        CollectionTestUser::create(['name' => 'John', 'email' => 'john@test.com', 'age' => 30, 'status' => 'active']);
        CollectionTestUser::create(['name' => 'Jane', 'email' => 'jane@test.com', 'age' => 25, 'status' => 'inactive']);
    });

    test('finds item by key', function () {
        $users = CollectionTestUser::all();
        $user = $users->find(1);
        expect($user->name)->toBe('John');
    });

    test('checks if contains item', function () {
        $users = CollectionTestUser::all();
        $user = CollectionTestUser::find(1);
        expect($users->contains($user))->toBeTrue();
        expect($users->contains(function ($u) {
            return $u->name === 'John';
        }))->toBeTrue();
    });

    test('merges collections', function () {
        $col1 = CollectionTestUser::where('id', 1)->get();
        $col2 = CollectionTestUser::where('id', 2)->get();

        $merged = $col1->merge($col2);
        expect($merged)->toHaveCount(2);
        expect($merged->contains(CollectionTestUser::find(2)))->toBeTrue();
    });

    test('diffs collections', function () {
        $all = CollectionTestUser::all();
        $one = CollectionTestUser::where('id', 1)->get();

        $diff = $all->diff($one);
        expect($diff)->toHaveCount(1);
        expect($diff->first()->id)->toBe(2);
    });
});
