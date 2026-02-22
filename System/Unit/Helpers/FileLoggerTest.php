<?php

declare(strict_types=1);

use Helpers\File\FileLogger;
use Helpers\File\FileSystem;
use Helpers\File\Paths;

describe('FileLogger', function () {
    beforeEach(function () {
        $this->testLogsDir = Paths::storagePath('test_logs');
        $this->testLogFile = Paths::join($this->testLogsDir, 'test_' . uniqid() . '.log');
        $this->logger = new FileLogger($this->testLogFile);
    });

    afterEach(function () {
        // Clear callbacks
        FileLogger::$logCallbacks = [];

        if (FileSystem::isDir($this->testLogsDir)) {
            FileSystem::delete($this->testLogsDir);
        }
    });


    test('constructor creates log file directory', function () {
        $logFile = Paths::storagePath('test_logs/subdir/test.log');
        $logger = new FileLogger($logFile);

        expect(FileSystem::isDir(dirname($logFile)))->toBeTrue();

        // Cleanup
        FileSystem::delete(dirname(dirname($logFile)));
    });

    test('constructor uses default log file when none provided', function () {
        $logger = new FileLogger();

        expect($logger->getLogFile())->toContain('anchor.log');
    });

    test('setLogFile changes log file path', function () {
        $newLogFile = Paths::storagePath('test_logs/new_' . uniqid() . '.log');
        $this->logger->setLogFile($newLogFile);

        expect($this->logger->getLogFile())->toBe($newLogFile);

        // Cleanup
        if (FileSystem::exists($newLogFile)) {
            FileSystem::delete($newLogFile);
        }
    });

    test('getLogFile returns current log file path', function () {
        expect($this->logger->getLogFile())->toBe($this->testLogFile);
    });

    test('log writes entry to file', function () {
        $this->logger->log('info', 'Test message');

        expect(FileSystem::exists($this->testLogFile))->toBeTrue();

        $content = FileSystem::get($this->testLogFile);
        expect($content)->toContain('INFO');
        expect($content)->toContain('Test message');
    });

    test('log includes timestamp', function () {
        $this->logger->log('info', 'Test message');

        $content = FileSystem::get($this->testLogFile);
        expect($content)->toMatch('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/');
    });

    test('log includes context as JSON', function () {
        $this->logger->log('info', 'Test message', ['user' => 'john', 'action' => 'login']);

        $content = FileSystem::get($this->testLogFile);
        expect($content)->toContain('"user":"john"');
        expect($content)->toContain('"action":"login"');
    });

    test('error logs at ERROR level', function () {
        $this->logger->error('Error message');

        $content = FileSystem::get($this->testLogFile);
        expect($content)->toContain('ERROR');
        expect($content)->toContain('Error message');
    });

    test('warning logs at WARNING level', function () {
        $this->logger->warning('Warning message');

        $content = FileSystem::get($this->testLogFile);
        expect($content)->toContain('WARNING');
        expect($content)->toContain('Warning message');
    });

    test('critical logs at CRITICAL level', function () {
        $this->logger->critical('Critical message');

        $content = FileSystem::get($this->testLogFile);
        expect($content)->toContain('CRITICAL');
        expect($content)->toContain('Critical message');
    });

    test('info logs at INFO level', function () {
        $this->logger->info('Info message');

        $content = FileSystem::get($this->testLogFile);
        expect($content)->toContain('INFO');
        expect($content)->toContain('Info message');
    });

    test('debug logs at DEBUG level', function () {
        $this->logger->debug('Debug message');

        $content = FileSystem::get($this->testLogFile);
        expect($content)->toContain('DEBUG');
        expect($content)->toContain('Debug message');
    });

    test('listen registers callback', function () {
        $called = false;
        $logData = null;

        FileLogger::listen(function ($data) use (&$called, &$logData) {
            $called = true;
            $logData = $data;
        });

        $this->logger->info('Test message', ['key' => 'value']);

        expect($called)->toBeTrue();
        expect($logData)->toBeArray();
        expect($logData['level'])->toBe('info');
        expect($logData['message'])->toBe('Test message');
        expect($logData['context'])->toBe(['key' => 'value']);
    });

    test('multiple log entries append to file', function () {
        $this->logger->info('First message');
        $this->logger->error('Second message');
        $this->logger->debug('Third message');

        $content = FileSystem::get($this->testLogFile);
        $lines = explode("\n", trim($content));

        expect(count($lines))->toBe(3);
        expect($content)->toContain('First message');
        expect($content)->toContain('Second message');
        expect($content)->toContain('Third message');
    });
});

afterAll(function () {
    $testLogsDir = Paths::storagePath('test_logs');
    if (FileSystem::isDir($testLogsDir)) {
        FileSystem::delete($testLogsDir);
    }
});
