<?php

declare(strict_types=1);

use Cli\Helpers\CommandResult;
use Cli\Helpers\DockCommand;

describe('DockCommand', function () {
    test('creates instance with make method', function () {
        $command = DockCommand::make('test');
        expect($command)->toBeInstanceOf(DockCommand::class);
    });

    test('builds simple command', function () {
        $command = DockCommand::make('migration:status');
        expect($command->buildCommand())->toBe('php dock migration:status');
    });

    test('builds command with arguments', function () {
        $command = DockCommand::make('package:uninstall')
            ->argument('Bridge');

        expect($command->buildCommand())->toBe('php dock package:uninstall Bridge');
    });

    test('builds command with multiple arguments', function () {
        $command = DockCommand::make('test')
            ->arguments(['UserTest', 'PostTest']);

        expect($command->buildCommand())->toBe('php dock test UserTest PostTest');
    });

    test('builds command with options', function () {
        $command = DockCommand::make('test')
            ->option('filter', 'UserTest');

        expect($command->buildCommand())->toBe('php dock test --filter=UserTest');
    });

    test('builds command with multiple options', function () {
        $command = DockCommand::make('migration:run')
            ->options(['file' => '2024_01_01_create_users', 'step' => '1']);

        $result = $command->buildCommand();
        expect($result)->toContain('--file=2024_01_01_create_users');
        expect($result)->toContain('--step=1');
    });

    test('builds command with flags', function () {
        $command = DockCommand::make('package:uninstall')
            ->argument('Bridge')
            ->flag('system')
            ->flag('yes');

        $result = $command->buildCommand();
        expect($result)->toContain('--system');
        expect($result)->toContain('--yes');
    });

    test('builds complex command', function () {
        $command = DockCommand::make('package:uninstall')
            ->argument('Bridge')
            ->option('system')
            ->flag('yes')
            ->flag('force');

        $result = $command->buildCommand();
        expect($result)->toBe('php dock package:uninstall Bridge --system --yes --force');
    });

    test('escapes arguments with spaces', function () {
        $command = DockCommand::make('test')
            ->argument('User Test');

        expect($command->buildCommand())->toBe('php dock test "User Test"');
    });

    test('sets timeout', function () {
        $command = DockCommand::make('test')->timeout(300);
        expect($command)->toBeInstanceOf(DockCommand::class);
    });

    test('enables retry logic', function () {
        $command = DockCommand::make('test')->retry(3, 1000);
        expect($command)->toBeInstanceOf(DockCommand::class);
    });

    test('dry run returns command string', function () {
        $result = DockCommand::make('migration:status')
            ->dryRun()
            ->run();

        expect($result)->toBeInstanceOf(CommandResult::class);
        expect($result->successful())->toBeTrue();
        expect($result->getOutput())->toBe('php dock migration:status');
    });

    test('throws exception for empty command', function () {
        $command = DockCommand::make();
        expect(fn () => $command->buildCommand())->toThrow(RuntimeException::class);
    });

    test('global dock helper creates instance', function () {
        $command = dock('test');
        expect($command)->toBeInstanceOf(DockCommand::class);
        expect($command->buildCommand())->toBe('php dock test');
    });

    test('global dock helper without command', function () {
        $command = dock()->command('test');
        expect($command)->toBeInstanceOf(DockCommand::class);
        expect($command->buildCommand())->toBe('php dock test');
    });

    test('fluent chaining works', function () {
        $command = dock('package:install')
            ->argument('Tenancy')
            ->option('system')
            ->flag('yes')
            ->timeout(600)
            ->retry(2);

        expect($command)->toBeInstanceOf(DockCommand::class);
        expect($command->buildCommand())->toContain('package:install');
        expect($command->buildCommand())->toContain('Tenancy');
    });
});

describe('CommandResult', function () {
    test('successful result', function () {
        $result = new CommandResult(
            success: true,
            output: 'Test output',
            error: '',
            exitCode: 0,
            commandLine: 'php dock test',
            executionTime: 1.5
        );

        expect($result->successful())->toBeTrue();
        expect($result->failed())->toBeFalse();
        expect($result->getOutput())->toBe('Test output');
        expect($result->getExitCode())->toBe(0);
    });

    test('failed result', function () {
        $result = new CommandResult(
            success: false,
            output: '',
            error: 'Command failed',
            exitCode: 1,
            commandLine: 'php dock test',
            executionTime: 0.5
        );

        expect($result->successful())->toBeFalse();
        expect($result->failed())->toBeTrue();
        expect($result->getError())->toBe('Command failed');
        expect($result->getExitCode())->toBe(1);
    });

    test('throw on failure', function () {
        $result = new CommandResult(
            success: false,
            output: '',
            error: 'Test error',
            exitCode: 1,
            commandLine: 'php dock test',
            executionTime: 0.5
        );

        expect(fn () => $result->throw())->toThrow(RuntimeException::class);
    });

    test('throw returns self on success', function () {
        $result = new CommandResult(
            success: true,
            output: 'Success',
            error: '',
            exitCode: 0,
            commandLine: 'php dock test',
            executionTime: 1.0
        );

        expect($result->throw())->toBe($result);
    });

    test('onSuccess callback executes', function () {
        $executed = false;

        $result = new CommandResult(
            success: true,
            output: 'Success',
            error: '',
            exitCode: 0,
            commandLine: 'php dock test',
            executionTime: 1.0
        );

        $result->onSuccess(function () use (&$executed) {
            $executed = true;
        });

        expect($executed)->toBeTrue();
    });

    test('onFailure callback executes', function () {
        $executed = false;

        $result = new CommandResult(
            success: false,
            output: '',
            error: 'Failed',
            exitCode: 1,
            commandLine: 'php dock test',
            executionTime: 0.5
        );

        $result->onFailure(function () use (&$executed) {
            $executed = true;
        });

        expect($executed)->toBeTrue();
    });

    test('onSuccess callback skipped on failure', function () {
        $executed = false;

        $result = new CommandResult(
            success: false,
            output: '',
            error: 'Failed',
            exitCode: 1,
            commandLine: 'php dock test',
            executionTime: 0.5
        );

        $result->onSuccess(function () use (&$executed) {
            $executed = true;
        });

        expect($executed)->toBeFalse();
    });
});
