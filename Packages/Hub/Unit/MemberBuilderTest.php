<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Unit tests for Hub MemberBuilder.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Tests\Packages\Hub\Unit;

use Database\Connection;
use Database\DB;
use Database\Query\Builder;
use Hub\Enums\ThreadRole;
use Hub\Models\Thread;
use Hub\Models\ThreadMember;
use Hub\Services\Builders\MemberBuilder;
use Mockery;
use ReflectionClass;
use Tests\Packages\Hub\Support\HubMockHelper;

describe('MemberBuilder', function () {
    function setupMemberMocks(): array
    {
        $connection = Mockery::mock(Connection::class);
        $connection->allows('table')->with('audit_log')->andReturn(Mockery::mock(Builder::class)->allows('setModelClass')->andReturnSelf()->getMock());
        DB::setDefaultConnection($connection);

        /** @var Mockery\MockInterface|Thread $thread */
        $thread = HubMockHelper::mockModel(Thread::class);
        $thread->allows('getMember')->andReturn(null);
        $thread->id = 1;

        $builder = new MemberBuilder($thread, 123);

        return [$connection, $thread, $builder];
    }

    afterEach(function () {
        Mockery::close();
        DB::setDefaultConnection(null);
    });

    it('sets owner role correctly', function () {
        [,, $builder] = setupMemberMocks();
        $builder->asOwner();

        $reflection = new ReflectionClass($builder);
        $role = $reflection->getProperty('role')->getValue($builder);

        expect($role)->toBe(ThreadRole::OWNER);
    });

    it('sets admin role correctly', function () {
        [,, $builder] = setupMemberMocks();
        $builder->asAdmin();

        $reflection = new ReflectionClass($builder);
        $role = $reflection->getProperty('role')->getValue($builder);

        expect($role)->toBe(ThreadRole::ADMIN);
    });

    it('sets guest role correctly', function () {
        [,, $builder] = setupMemberMocks();
        $builder->asGuest();

        $reflection = new ReflectionClass($builder);
        $role = $reflection->getProperty('role')->getValue($builder);

        expect($role)->toBe(ThreadRole::GUEST);
    });

    it('sets notifications correctly', function () {
        [,, $builder] = setupMemberMocks();
        $builder->silent();

        $reflection = new ReflectionClass($builder);
        $notif = $reflection->getProperty('notificationsEnabled')->getValue($builder);

        expect($notif)->toBeFalse();
    });

    it('persists a new member', function () {
        [$connection, $thread, $builder] = setupMemberMocks();

        $queryBuilder = Mockery::mock(Builder::class);
        $queryBuilder->allows('setModelClass')->andReturnSelf();
        $connection->shouldReceive('table')
            ->with('hub_member')
            ->andReturn($queryBuilder);

        $queryBuilder->shouldReceive('insertGetId')
            ->once()
            ->andReturn(500);

        $queryBuilder->shouldReceive('where')
            ->andReturnSelf();

        $queryBuilder->shouldReceive('first')
            ->andReturn((object) [
                'id' => 500,
                'thread_id' => 1,
                'user_id' => 123,
                'role' => 'admin',
                'notifications_enabled' => 1,
            ]);

        $member = $builder->asAdmin()->add();

        expect($member)->toBeInstanceOf(ThreadMember::class)
            ->and($member->user_id)->toBe(123)
            ->and($member->role)->toBe(ThreadRole::ADMIN);
    });
});
