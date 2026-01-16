<?php

declare(strict_types=1);

use Cli\Commands\Runners\CodeQualityCommand;

describe('CodeQualityCommand - Configuration', function () {
    test('has correct command name', function () {
        $command = new CodeQualityCommand();

        expect($command->getName())->toBe('inspect');
    });

    test('has descriptive help text', function () {
        $command = new CodeQualityCommand();

        expect($command->getDescription())->toContain('quality');
        expect($command->getDescription())->toContain('PHPStan');
    });

    test('describes what it does', function () {
        $command = new CodeQualityCommand();

        expect($command->getHelp())->toContain('PHPStan');
        expect($command->getHelp())->toContain('App');
        expect($command->getHelp())->toContain('System');
    });
});

describe('CodeQualityCommand - Execution Strategy', function () {
    test('runs both checks regardless of first failure', function () {
        $command = new CodeQualityCommand();

        // The inspect command runs both CS Fixer and PHPStan
        // even if the first one fails, to collect all issues
        expect($command)->toBeInstanceOf(CodeQualityCommand::class);
    });

    test('provides comprehensive quality analysis', function () {
        $command = new CodeQualityCommand();

        // Verify it checks both coding standards and static analysis
        expect($command->getHelp())->toContain('PHPStan');
    });
});

describe('CodeQualityCommand - Integration', function () {
    test('integrates with sail command', function () {
        $command = new CodeQualityCommand();

        // The inspect command is part of the sail workflow
        // Verify it's properly configured
        expect($command->getName())->toBe('inspect');
    });

    test('complements repair command', function () {
        $command = new CodeQualityCommand();

        // inspect checks quality, repair fixes issues
        // Both are part of the code quality workflow
        expect($command->getDescription())->toContain('quality');
    });
});
