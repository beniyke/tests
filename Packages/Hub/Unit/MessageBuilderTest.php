<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Unit tests for Hub MessageBuilder.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Tests\Packages\Hub\Unit;

use Database\Connection;
use Database\DB;
use Database\Query\Builder;
use Hub\Models\Message;
use Hub\Models\Thread;
use Hub\Services\Builders\MessageBuilder;
use Hub\Services\HubManagerService;
use Mockery;
use ReflectionClass;
use Tests\Packages\Hub\Support\HubMockHelper;

describe('MessageBuilder', function () {
    function setupBuilderMocks(): array
    {
        $connection = Mockery::mock(Connection::class);
        $connection->allows('table')->with('audit_log')->andReturn(Mockery::mock(Builder::class)->allows('setModelClass')->andReturnSelf()->getMock());
        DB::setDefaultConnection($connection);

        $manager = Mockery::mock(HubManagerService::class);

        $thread = HubMockHelper::mockModel(Thread::class);
        $thread->id = 1;
        $thread->refid = 'thread_1';

        $builder = new MessageBuilder($manager);
        $builder->in($thread);

        return [$connection, $thread, $builder, $manager];
    }

    afterEach(function () {
        Mockery::close();
        DB::setDefaultConnection(null);
    });

    it('sets the sender correctly', function () {
        [,, $builder] = setupBuilderMocks();

        $builder->from(123);

        $reflection = new ReflectionClass($builder);
        $userId = $reflection->getProperty('userId')->getValue($builder);

        expect($userId)->toBe(123);
    });

    it('sets the message body', function () {
        [,, $builder] = setupBuilderMocks();

        $builder->body('Hello world');

        $reflection = new ReflectionClass($builder);
        $body = $reflection->getProperty('body')->getValue($builder);

        expect($body)->toBe('Hello world');
    });

    it('sets as a reply correctly', function () {
        [,, $builder] = setupBuilderMocks();
        $parentMessage = HubMockHelper::mockModel(Message::class);
        $parentMessage->id = 50;
        $parentMessage->thread_id = 1;

        $builder->replyTo($parentMessage);

        $reflection = new ReflectionClass($builder);
        $parentId = $reflection->getProperty('parentId')->getValue($builder);

        expect($parentId)->toBe(50);
    });

    it('sets mentions correctly', function () {
        [,, $builder] = setupBuilderMocks();

        $builder->mentions([1, 2, 3]);

        $reflection = new ReflectionClass($builder);
        $mentions = $reflection->getProperty('mentions')->getValue($builder);

        expect($mentions)->toBe([1, 2, 3]);
    });

    it('persists a new message', function () {
        [,, $builder, $manager] = setupBuilderMocks();

        $manager->expects('postMessage')
            ->once()
            ->andReturn(HubMockHelper::mockModel(Message::class));

        $message = $builder->from(123)->body('Test message')->send();

        expect($message)->toBeInstanceOf(Message::class);
    });
});
