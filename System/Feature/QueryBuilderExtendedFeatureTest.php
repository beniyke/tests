<?php

declare(strict_types=1);

use Tests\System\Fixtures\Models\TestBuilderUser;

describe('Query Builder - Extended Features', function () {

    test('orWhereBetween clause', function () {
        TestBuilderUser::create(['name' => 'A', 'email' => 'a@test.com', 'age' => 20]);
        TestBuilderUser::create(['name' => 'B', 'email' => 'b@test.com', 'age' => 30]);
        TestBuilderUser::create(['name' => 'C', 'email' => 'c@test.com', 'age' => 40]);

        $users = TestBuilderUser::whereBetween('age', [15, 25])
            ->orWhereBetween('age', [35, 45])
            ->get();

        expect($users)->toHaveCount(2);
        expect($users[0]->name)->toBe('A');
        expect($users[1]->name)->toBe('C');
    });

    test('whereDate and date helpers', function () {
        TestBuilderUser::create(['name' => 'A', 'email' => 'a@test.com', 'created_at' => '2024-01-01 10:00:00']);
        TestBuilderUser::create(['name' => 'B', 'email' => 'b@test.com', 'created_at' => '2024-02-01 10:00:00']);

        $users = TestBuilderUser::whereDate('created_at', '=', '2024-01-01')
            ->get();

        expect($users)->toHaveCount(1);
        expect($users[0]->name)->toBe('A');

        $usersYear = TestBuilderUser::whereYear('created_at', 2024)
            ->get();

        expect($usersYear)->toHaveCount(2);
    });

    test('inserts and get id', function () {
        $id = TestBuilderUser::create([
            'name' => 'New User',
            'email' => 'new@test.com',
            'votes' => 5,
        ])->id;

        expect($id)->toBeGreaterThan(0);

        $user = TestBuilderUser::find($id);
        expect($user->name)->toBe('New User');
    });

    test('increment and decrement', function () {
        $id = TestBuilderUser::create([
            'name' => 'Voter',
            'email' => 'voter@test.com',
            'votes' => 10,
        ])->id;

        TestBuilderUser::where('id', $id)->increment('votes', 2);
        $user = TestBuilderUser::find($id);
        expect($user->votes)->toBe(12);

        TestBuilderUser::where('id', $id)->decrement('votes', 5);
        $user = TestBuilderUser::find($id);
        expect($user->votes)->toBe(7);
    });

    test('chunking results', function () {
        for ($i = 0; $i < 10; $i++) {
            TestBuilderUser::create(['name' => "User $i", 'email' => "user$i@test.com"]);
        }

        $count = 0;
        TestBuilderUser::orderBy('id')->chunk(4, function ($users) use (&$count) {
            $count++;
        });

        // 10 records, chunk size 4 => 3 chunks (4, 4, 2)
        expect($count)->toBe(3);
    });

    test('when conditional', function () {
        TestBuilderUser::create(['name' => 'John', 'email' => 'john@test.com']);

        $role = 'admin';
        $users = TestBuilderUser::when($role === 'admin', function ($query) {
            return $query->where('name', 'John');
        })
            ->when($role === 'guest', function ($query) {
                return $query->where('name', 'Guest');
            })
            ->get();

        expect($users)->toHaveCount(1);
    });

    test('whereColumn', function () {
        // Create user where name equals email (weird case but good for testing)
        TestBuilderUser::create(['name' => 'same', 'email' => 'same']);
        TestBuilderUser::create(['name' => 'diff', 'email' => 'different']);

        $users = TestBuilderUser::whereColumn('name', '=', 'email')
            ->get();

        expect($users)->toHaveCount(1);
        expect($users[0]->name)->toBe('same');
    });

    test('delete and truncate', function () {
        TestBuilderUser::create(['name' => 'A', 'email' => 'a@test.com']);
        TestBuilderUser::create(['name' => 'B', 'email' => 'b@test.com']);

        TestBuilderUser::where('name', 'A')->delete();
        expect(TestBuilderUser::count())->toBe(1);

        TestBuilderUser::query()->truncate();
        expect(TestBuilderUser::count())->toBe(0);
    });

    test('whereIn and whereNotIn', function () {
        TestBuilderUser::create(['name' => 'A', 'email' => 'a@test.com', 'age' => 20]);
        TestBuilderUser::create(['name' => 'B', 'email' => 'b@test.com', 'age' => 30]);
        TestBuilderUser::create(['name' => 'C', 'email' => 'c@test.com', 'age' => 40]);

        $users = TestBuilderUser::whereIn('age', [20, 40])->get();
        expect($users)->toHaveCount(2);

        $users = TestBuilderUser::whereNotIn('age', [20, 40])->get();
        expect($users)->toHaveCount(1);
        expect($users[0]->name)->toBe('B');
    });

    test('whereNull and whereNotNull', function () {
        TestBuilderUser::create(['name' => 'A', 'email' => 'a@test.com', 'age' => null]);
        TestBuilderUser::create(['name' => 'B', 'email' => 'b@test.com', 'age' => 30]);

        $users = TestBuilderUser::whereNull('age')->get();
        expect($users)->toHaveCount(1);
        expect($users[0]->name)->toBe('A');

        $users = TestBuilderUser::whereNotNull('age')->get();
        expect($users)->toHaveCount(1);
        expect($users[0]->name)->toBe('B');
    });
});
