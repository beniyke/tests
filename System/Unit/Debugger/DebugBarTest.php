<?php

declare(strict_types=1);

use Database\Connection;
use Database\DB;
use DebugBar\DebugBar;
use Debugger\Collector\QueryCollector;

describe('DebugBar', function () {
    test('creates debug bar instance', function () {
        $debugBar = new DebugBar();
        expect($debugBar)->toBeInstanceOf(DebugBar::class);
    });

    test('adds collector', function () {
        $debugBar = new DebugBar();
        $collector = new QueryCollector();

        $debugBar->addCollector($collector);
        expect($debugBar->hasCollector('query'))->toBeTrue();
    });

    test('collects data', function () {
        $debugBar = new DebugBar();
        expect(method_exists($debugBar, 'collect'))->toBeTrue();
    });

    test('has renderer', function () {
        $debugBar = new DebugBar();
        expect(method_exists($debugBar, 'getJavascriptRenderer'))->toBeTrue();
    });
});

describe('QueryCollector', function () {
    beforeEach(function () {
        // Setup in-memory DB for query logging
        $connection = Connection::configure('sqlite::memory:')->connect();
        DB::setConnection($connection);
        Connection::clearQueryLog();
    });

    test('collects query data', function () {
        $collector = new QueryCollector();

        // Run a query
        DB::statement('SELECT 1');

        $data = $collector->collect();
        expect($data)->toHaveKey('queries');
        expect($data['queries'])->toHaveCount(1);
        expect($data['queries'][0]['sql'])->toContain('SELECT 1');
    });

    test('tracks query count', function () {
        $collector = new QueryCollector();

        DB::statement('SELECT 1');
        DB::statement('SELECT 2');

        $data = $collector->collect();
        expect($data['count'])->toBe(2);
    });
});
