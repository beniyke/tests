<?php

declare(strict_types=1);

namespace Tests\Packages\Hub\Unit;

use Hub\Models\Thread;
use Hub\Services\Builders\ThreadBuilder;
use Hub\Services\HubManagerService;
use Mockery;

describe('ThreadBuilder', function () {
    function setupThreadBuilderMocks(): array
    {
        $manager = Mockery::mock(HubManagerService::class);

        return [$manager, new ThreadBuilder($manager)];
    }

    afterEach(function () {
        Mockery::close();
    });

    describe('fluent API', function () {
        it('chains methods correctly', function () {
            [$manager, $builder] = setupThreadBuilderMocks();

            $result = $builder
                ->title('Test Thread')
                ->members([1, 2, 3])
                ->by(1);

            expect($result)->toBeInstanceOf(ThreadBuilder::class);
        });

        it('sets title correctly', function () {
            [$manager, $builder] = setupThreadBuilderMocks();

            $manager->shouldReceive('createThread')
                ->once()
                ->with(Mockery::on(function ($data) {
                    return $data['title'] === 'Sprint Planning';
                }))
                ->andReturn(Mockery::mock(Thread::class));

            $builder->title('Sprint Planning')->create();
        });

        it('sets members correctly', function () {
            [$manager, $builder] = setupThreadBuilderMocks();

            $manager->shouldReceive('createThread')
                ->once()
                ->with(Mockery::on(function ($data) {
                    return $data['members'] === [1, 2, 3];
                }))
                ->andReturn(Mockery::mock(Thread::class));

            $builder->members([1, 2, 3])->create();
        });

        it('adds single member correctly', function () {
            [$manager, $builder] = setupThreadBuilderMocks();

            $manager->shouldReceive('createThread')
                ->once()
                ->with(Mockery::on(function ($data) {
                    return in_array(5, $data['members']) && in_array(6, $data['members']);
                }))
                ->andReturn(Mockery::mock(Thread::class));

            $builder->addMember(5)->addMember(6)->create();
        });

        it('sets pinned status', function () {
            [$manager, $builder] = setupThreadBuilderMocks();

            $manager->shouldReceive('createThread')
                ->once()
                ->with(Mockery::on(function ($data) {
                    return $data['is_pinned'] === true;
                }))
                ->andReturn(Mockery::mock(Thread::class));

            $builder->pinned()->create();
        });

        it('sets creator correctly', function () {
            [$manager, $builder] = setupThreadBuilderMocks();

            $manager->shouldReceive('createThread')
                ->once()
                ->with(Mockery::on(function ($data) {
                    return $data['created_by'] === 42;
                }))
                ->andReturn(Mockery::mock(Thread::class));

            $builder->by(42)->create();
        });

        it('sets metadata via with()', function () {
            [$manager, $builder] = setupThreadBuilderMocks();

            $manager->shouldReceive('createThread')
                ->once()
                ->with(Mockery::on(function ($data) {
                    return $data['metadata']['priority'] === 'high';
                }))
                ->andReturn(Mockery::mock(Thread::class));

            $builder->with('priority', 'high')->create();
        });
    });
});
