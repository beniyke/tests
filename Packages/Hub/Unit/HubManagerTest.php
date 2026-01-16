<?php

declare(strict_types=1);

namespace Tests\Packages\Hub\Unit;

use Core\Services\ConfigServiceInterface;
use Database\Connection;
use Database\DB;
use Hub\Services\HubManagerService;
use Mockery;

describe('HubManagerService', function () {
    function setupHubMocks(): array
    {
        $connection = Mockery::mock(Connection::class);
        DB::setDefaultConnection($connection);

        $config = Mockery::mock(ConfigServiceInterface::class);
        $config->shouldReceive('get')
            ->with('hub.default_notifications_enabled', true)
            ->andReturn(true);
        $config->shouldReceive('get')
            ->with('hub.mention_pattern', '/@(\w+)/')
            ->andReturn('/@(\w+)/');

        $manager = new HubManagerService($config);

        return [$connection, $config, $manager];
    }

    afterEach(function () {
        Mockery::close();
        DB::setDefaultConnection(null);
    });

    describe('parseMentions()', function () {
        it('extracts mentions from message body', function () {
            [,, $manager] = setupHubMocks();

            $mentions = $manager->parseMentions('Hey @john, please review with @jane');

            expect($mentions)->toBe(['john', 'jane']);
        });

        it('returns empty array when no mentions', function () {
            [,, $manager] = setupHubMocks();

            $mentions = $manager->parseMentions('No mentions here');

            expect($mentions)->toBe([]);
        });

        it('handles multiple mentions of same user', function () {
            [,, $manager] = setupHubMocks();

            $mentions = $manager->parseMentions('@john and @john again');

            expect($mentions)->toBe(['john', 'john']);
        });
    });
});
