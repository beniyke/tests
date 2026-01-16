<?php

declare(strict_types=1);

use Tests\System\Fixtures\Models\TestSoftDeleteUser;
use Tests\System\Fixtures\Models\TestUpdateUser;

describe('Feature - Builder Update', function () {
    test('updates records correctly', function () {

        $user = TestUpdateUser::create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $affected = TestUpdateUser::where('id', $user->id)
            ->update(['name' => 'Updated Name']);

        expect($affected)->toBe(1);

        $updatedUser = TestUpdateUser::find($user->id);
        expect($updatedUser->name)->toBe('Updated Name');
    });

    test('updates records with soft deletes enabled', function () {
        $user = TestSoftDeleteUser::create([
            'name' => 'Soft Delete User',
            'email' => 'soft@example.com',
        ]);

        $affected = TestSoftDeleteUser::where('id', $user->id)
            ->update(['name' => 'Updated Soft Delete User']);

        expect($affected)->toBe(1);

        $updatedUser = TestSoftDeleteUser::find($user->id);
        expect($updatedUser->name)->toBe('Updated Soft Delete User');
    });

    test('updates multiple records', function () {
        TestUpdateUser::create(['name' => 'User 1', 'email' => '1@test.com', 'status' => 'active']);
        TestUpdateUser::create(['name' => 'User 2', 'email' => '2@test.com', 'status' => 'active']);
        TestUpdateUser::create(['name' => 'User 3', 'email' => '3@test.com', 'status' => 'inactive']);

        $affected = TestUpdateUser::where('status', 'active')
            ->update(['status' => 'verified']);

        expect($affected)->toBe(2);
        expect(TestUpdateUser::where('status', 'verified')->count())->toBe(2);
        expect(TestUpdateUser::where('status', 'inactive')->count())->toBe(1);
    });
});
