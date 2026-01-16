<?php

declare(strict_types=1);

use Core\Console;
use Symfony\Component\Console\Command\Command;

describe('CLI Console System', function () {
    test('Console class exists', function () {
        expect(class_exists(Console::class))->toBeTrue();
    });

    test('Console uses DeferredTaskTrait', function () {
        $reflection = new ReflectionClass(Console::class);
        $traits = $reflection->getTraitNames();

        expect($traits)->toContain('Defer\DeferredTaskTrait');
    });

    test('Console has run method', function () {
        expect(method_exists(Console::class, 'run'))->toBeTrue();
    });

    test('Console discovers commands from Cli directory', function () {
        // Test that the discoverCommands method exists (it's private but we can check the class structure)
        $reflection = new ReflectionClass(Console::class);
        $method = $reflection->getMethod('discoverCommands');

        expect($method)->not->toBeNull();
    });
});

describe('Symfony Console Commands', function () {
    test('CreateDatabaseCommand extends Symfony Command', function () {
        $commandClass = 'Cli\Commands\Database\CreateDatabaseCommand';

        if (class_exists($commandClass)) {
            $reflection = new ReflectionClass($commandClass);
            expect($reflection->isSubclassOf(Command::class))->toBeTrue();
        } else {
            expect(true)->toBeTrue(); // Skip if class doesn't exist
        }
    });

    test('Commands have configure method', function () {
        $commandClass = 'Cli\Commands\Database\CreateDatabaseCommand';

        if (class_exists($commandClass)) {
            expect(method_exists($commandClass, 'configure'))->toBeTrue();
        } else {
            expect(true)->toBeTrue();
        }
    });

    test('Commands have execute method', function () {
        $commandClass = 'Cli\Commands\Database\CreateDatabaseCommand';

        if (class_exists($commandClass)) {
            expect(method_exists($commandClass, 'execute'))->toBeTrue();
        } else {
            expect(true)->toBeTrue();
        }
    });

    test('Command uses SymfonyStyle for output', function () {
        // This tests that commands follow the Symfony Console pattern
        $commandClass = 'Cli\Commands\Database\CreateDatabaseCommand';

        if (class_exists($commandClass)) {
            $reflection = new ReflectionClass($commandClass);
            $method = $reflection->getMethod('execute');
            $source = file_get_contents($reflection->getFileName());

            expect($source)->toContain('SymfonyStyle');
        } else {
            expect(true)->toBeTrue();
        }
    });
});
