<?php

declare(strict_types=1);

use Cli\Commands\Runners\GenerateAppKeyCommand;
use Symfony\Component\Console\Tester\CommandTester;

describe('GenerateAppKeyCommand', function () {
    test('displays key with --show option without modifying .env', function () {
        $command = new GenerateAppKeyCommand();
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--show' => true,
        ]);

        $output = $commandTester->getDisplay();

        // Check for success message
        expect($output)->toContain('Here is your new APP_KEY');

        // Check for base64 key format
        expect($output)->toMatch('/base64:[a-zA-Z0-9+\/=]{44}/');

        // Ensure it did NOT try to save (which would print "Successfully generated and saved")
        expect($output)->not->toContain('Successfully generated and saved');
    });
});
