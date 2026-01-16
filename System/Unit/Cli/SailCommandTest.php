<?php

declare(strict_types=1);

use Cli\Commands\Runners\SailCommand;
use Symfony\Component\Process\Process;

class TestableSailCommand extends SailCommand
{
    public array $ranCommands = [];

    public array $processResults = [];

    protected function runProcess(string $command, bool $async = false): Process
    {
        $this->ranCommands[] = $command;

        $mock = Mockery::mock(Process::class);
        $mock->shouldReceive('start')->byDefault();
        $mock->shouldReceive('run')->byDefault();
        $mock->shouldReceive('isRunning')->andReturn(false)->byDefault();
        $mock->shouldReceive('isSuccessful')->andReturn(true)->byDefault();
        $mock->shouldReceive('getOutput')->andReturn('')->byDefault();
        $mock->shouldReceive('getErrorOutput')->andReturn('')->byDefault();
        $mock->shouldReceive('setTty')->byDefault();
        $mock->shouldReceive('wait')->byDefault();

        if (isset($this->processResults[$command])) {
            foreach ($this->processResults[$command] as $method => $value) {
                $mock->shouldReceive($method)->andReturn($value);
            }
        }

        return $mock;
    }
}

describe('SailCommand - Prerequisites', function () {
    test('checks for required binaries before running', function () {
        $command = new SailCommand();

        expect($command->getName())->toBe('sail');
        expect($command->getDescription())->toContain('production');
    });

    test('command has proper configuration', function () {
        $command = new SailCommand();

        expect($command->getName())->toBe('sail');
        expect($command->getDescription())->toBe('Run comprehensive pre-flight checks to ensure production readiness.');
    });
});

describe('SailCommand - Execution Flow', function () {
    test('executes checks in correct order', function () {
        $command = new TestableSailCommand();
        $tester = new Symfony\Component\Console\Tester\CommandTester($command);

        $tester->execute([]);

        expect($command->ranCommands)->toContain(PHP_BINARY . ' vendor/bin/pint --test')
            ->and($command->ranCommands)->toContain(PHP_BINARY . ' dock format --check --skip-pint')
            ->and($command->ranCommands)->toContain(PHP_BINARY . ' vendor/bin/pest --colors=always');

        expect($tester->getDisplay())->toContain('Integrity & Style Inspection')
            ->and($tester->getDisplay())->toContain('Operational Readiness & Testing')
            ->and($tester->getDisplay())->toContain('VOYAGE CLEAR!');
    });

    test('failure in first step stops execution', function () {
        $command = new TestableSailCommand();
        $pintCmd = PHP_BINARY . ' vendor/bin/pint --test';
        $command->processResults[$pintCmd] = ['isSuccessful' => false, 'getOutput' => 'Pint Error'];

        $tester = new Symfony\Component\Console\Tester\CommandTester($command);
        $tester->execute([]);

        expect($tester->getStatusCode())->toBe(Symfony\Component\Console\Command\Command::FAILURE)
            ->and($tester->getDisplay())->toContain('Coding Style (Pint) check failed!')
            ->and($command->ranCommands)->not->toContain(PHP_BINARY . ' vendor/bin/pest --colors=always');
    });

    test('provides helpful error messages', function () {
        $command = new SailCommand();

        expect($command->getHelp())->toContain('repair checks');
        expect($command->getHelp())->toContain('style inspections');
        expect($command->getHelp())->toContain('production readiness');
    });
});

describe('SailCommand - Maritime Theme', function () {
    test('uses maritime-themed messaging', function () {
        $command = new SailCommand();

        expect($command->getDescription())->toContain('readiness');
    });

    test('command name aligns with framework philosophy', function () {
        $command = new SailCommand();

        expect($command->getName())->toBe('sail');
    });
});

describe('SailCommand - Integration Points', function () {
    test('integrates with other quality commands', function () {
        $command = new SailCommand();

        expect($command->getHelp())->toContain('repair checks');
        expect($command->getHelp())->toContain('style inspections');
        expect($command->getHelp())->toContain('unit tests');
    });
});
