<?php

declare(strict_types=1);

namespace Tests\Packages\Link\Unit;

use Carbon\Carbon;
use Link\Enums\LinkScope;
use Link\Services\Builders\LinkBuilder;
use Link\Services\LinkManagerService;
use Mockery;

describe('LinkBuilder', function () {
    function setupBuilderMocks(): array
    {
        $manager = Mockery::mock(LinkManagerService::class);

        return [$manager, new LinkBuilder($manager)];
    }

    afterEach(function () {
        Mockery::close();
        Carbon::setTestNow();
    });

    beforeEach(function () {
        Carbon::setTestNow('2026-01-02 12:00:00');
    });

    describe('fluent API', function () {
        it('chains methods correctly', function () {
            [$manager, $builder] = setupBuilderMocks();

            $result = $builder
                ->validForHours(24)
                ->maxUses(5)
                ->view()
                ->download();

            expect($result)->toBeInstanceOf(LinkBuilder::class);
        });

        it('sets scopes via shorthand methods', function () {
            [$manager, $builder] = setupBuilderMocks();

            $manager->shouldReceive('create')
                ->once()
                ->with(Mockery::on(function ($data) {
                    return in_array('view', $data['scopes'])
                        && in_array('download', $data['scopes'])
                        && in_array('join', $data['scopes']);
                }))
                ->andReturn(Mockery::mock(\Link\Models\Link::class));

            $builder->view()->download()->join()->create();
        });

        it('sets single use via singleUse()', function () {
            [$manager, $builder] = setupBuilderMocks();

            $manager->shouldReceive('create')
                ->once()
                ->with(Mockery::on(function ($data) {
                    return $data['max_uses'] === 1;
                }))
                ->andReturn(Mockery::mock(\Link\Models\Link::class));

            $builder->singleUse()->create();
        });

        it('sets invite shorthand correctly', function () {
            [$manager, $builder] = setupBuilderMocks();

            $manager->shouldReceive('create')
                ->once()
                ->with(Mockery::on(function ($data) {
                    return in_array('join', $data['scopes'])
                        && $data['max_uses'] === 1;
                }))
                ->andReturn(Mockery::mock(\Link\Models\Link::class));

            $builder->invite()->create();
        });

        it('sets recipient email', function () {
            [$manager, $builder] = setupBuilderMocks();

            $manager->shouldReceive('create')
                ->once()
                ->with(Mockery::on(function ($data) {
                    return $data['recipient_type'] === 'email'
                        && $data['recipient_value'] === 'test@example.com';
                }))
                ->andReturn(Mockery::mock(\Link\Models\Link::class));

            $builder->recipient('test@example.com')->create();
        });

        it('sets recipient IP', function () {
            [$manager, $builder] = setupBuilderMocks();

            $manager->shouldReceive('create')
                ->once()
                ->with(Mockery::on(function ($data) {
                    return $data['recipient_type'] === 'ip'
                        && $data['recipient_value'] === '192.168.1.1';
                }))
                ->andReturn(Mockery::mock(\Link\Models\Link::class));

            $builder->recipientIp('192.168.1.1')->create();
        });

        it('sets metadata via with()', function () {
            [$manager, $builder] = setupBuilderMocks();

            $manager->shouldReceive('create')
                ->once()
                ->with(Mockery::on(function ($data) {
                    return $data['metadata']['key1'] === 'value1'
                        && $data['metadata']['key2'] === 'value2';
                }))
                ->andReturn(Mockery::mock(\Link\Models\Link::class));

            $builder->with('key1', 'value1')->with('key2', 'value2')->create();
        });

        it('sets creator via by()', function () {
            [$manager, $builder] = setupBuilderMocks();

            $manager->shouldReceive('create')
                ->once()
                ->with(Mockery::on(function ($data) {
                    return $data['created_by'] === 42;
                }))
                ->andReturn(Mockery::mock(\Link\Models\Link::class));

            $builder->by(42)->create();
        });
    });

    describe('expiration', function () {
        it('sets expiry via validForHours()', function () {
            [$manager, $builder] = setupBuilderMocks();

            $manager->shouldReceive('create')
                ->once()
                ->with(Mockery::on(function ($data) {
                    return $data['valid_for_hours'] === 48;
                }))
                ->andReturn(Mockery::mock(\Link\Models\Link::class));

            $builder->validForHours(48)->create();
        });

        it('sets expiry via validForDays()', function () {
            [$manager, $builder] = setupBuilderMocks();

            $manager->shouldReceive('create')
                ->once()
                ->with(Mockery::on(function ($data) {
                    return $data['valid_for_hours'] === 168; // 7 * 24
                }))
                ->andReturn(Mockery::mock(\Link\Models\Link::class));

            $builder->validForDays(7)->create();
        });

        it('sets expiry via until()', function () {
            [$manager, $builder] = setupBuilderMocks();

            $targetDate = Carbon::parse('2026-01-10 12:00:00');

            $manager->shouldReceive('create')
                ->once()
                ->with(Mockery::on(function ($data) use ($targetDate) {
                    return $data['expires_at']->equalTo($targetDate);
                }))
                ->andReturn(Mockery::mock(\Link\Models\Link::class));

            $builder->until($targetDate)->create();
        });

        it('sets no expiry via forever()', function () {
            [$manager, $builder] = setupBuilderMocks();

            $manager->shouldReceive('create')
                ->once()
                ->with(Mockery::on(function ($data) {
                    return !isset($data['expires_at']) && !isset($data['valid_for_hours']);
                }))
                ->andReturn(Mockery::mock(\Link\Models\Link::class));

            $builder->forever()->create();
        });
    });

    describe('scopes', function () {
        it('accepts scope enum', function () {
            [$manager, $builder] = setupBuilderMocks();

            $manager->shouldReceive('create')
                ->once()
                ->with(Mockery::on(function ($data) {
                    return in_array(LinkScope::EDIT->value, $data['scopes']);
                }))
                ->andReturn(Mockery::mock(\Link\Models\Link::class));

            $builder->scope(LinkScope::EDIT)->create();
        });

        it('accepts array of scopes', function () {
            [$manager, $builder] = setupBuilderMocks();

            $manager->shouldReceive('create')
                ->once()
                ->with(Mockery::on(function ($data) {
                    return $data['scopes'] === ['view', 'download', 'edit'];
                }))
                ->andReturn(Mockery::mock(\Link\Models\Link::class));

            $builder->scopes(['view', 'download', 'edit'])->create();
        });

        it('does not duplicate scopes', function () {
            [$manager, $builder] = setupBuilderMocks();

            $manager->shouldReceive('create')
                ->once()
                ->with(Mockery::on(function ($data) {
                    return count(array_filter($data['scopes'], fn ($s) => $s === 'view')) === 1;
                }))
                ->andReturn(Mockery::mock(\Link\Models\Link::class));

            $builder->view()->view()->view()->create();
        });
    });
});
