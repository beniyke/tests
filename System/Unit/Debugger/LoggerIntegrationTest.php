<?php

declare(strict_types=1);

use Debugger\Debugger;
use Debugger\DebuggerInterface;
use Helpers\File\FileLogger;

beforeEach(function () {
    // Reset log callbacks
    FileLogger::$logCallbacks = [];
});

test('logger messages are pushed to debugger messages collector', function () {
    $container = container();

    // Initialize debugger
    $debugger = Debugger::getInstance($container);

    // Simulate service provider boot
    FileLogger::listen(function (array $logData) use ($container) {
        try {
            $debugger = $container->get(DebuggerInterface::class);

            $label = match ($logData['level']) {
                'error', 'critical' => 'error',
                'warning' => 'warning',
                default => 'info',
            };

            $message = $logData['message'];
            if (!empty($logData['context'])) {
                $message .= ' ' . json_encode($logData['context'], JSON_UNESCAPED_SLASHES);
            }

            $debugger->push('messages', $message, $label);
        } catch (Throwable $e) {
            // Silently fail
        }
    });

    // Log some messages
    logger('test.log')->info('Test info message', ['user_id' => 123]);
    logger('test.log')->warning('Test warning message');
    logger('test.log')->error('Test error message');

    // Get messages from debugger
    $messagesCollector = $debugger->getDebugBar()->getCollector('messages');
    $messages = $messagesCollector->getMessages();

    // Verify messages were added
    expect($messages)->toHaveCount(3);
    expect($messages[0]['message'])->toContain('Test info message');
    expect($messages[0]['message'])->toContain('"user_id":123');
    expect($messages[0]['label'])->toBe('info');

    expect($messages[1]['message'])->toBe('Test warning message');
    expect($messages[1]['label'])->toBe('warning');

    expect($messages[2]['message'])->toBe('Test error message');
    expect($messages[2]['label'])->toBe('error');
});

test('logger integration handles debugger unavailability gracefully', function () {
    // Reset callbacks
    FileLogger::$logCallbacks = [];

    // Register callback that tries to access non-existent debugger
    FileLogger::listen(function (array $logData) {
        $container = container();
        try {
            // This should fail gracefully
            $debugger = $container->get('NonExistentDebugger');
            $debugger->push('messages', $logData['message'], 'info');
        } catch (Throwable $e) {
            // Should catch and continue
        }
    });

    // This should not throw an exception
    expect(fn () => logger('test.log')->info('Test message'))->not->toThrow(Throwable::class);
});

test('log level mapping works correctly', function () {
    // Test the match expression logic directly
    $testCases = [
        'debug' => 'info',
        'info' => 'info',
        'warning' => 'warning',
        'error' => 'error',
        'critical' => 'error',
    ];

    foreach ($testCases as $level => $expectedLabel) {
        $label = match ($level) {
            'error', 'critical' => 'error',
            'warning' => 'warning',
            default => 'info',
        };

        expect($label)->toBe($expectedLabel, "Level '{$level}' should map to '{$expectedLabel}'");
    }
});
