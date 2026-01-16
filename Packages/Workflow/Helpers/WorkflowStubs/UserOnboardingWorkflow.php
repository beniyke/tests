<?php

declare(strict_types=1);

namespace Tests\Packages\Workflow\Helpers\WorkflowStubs;

use Generator;
use Workflow\Contracts\ActivityOptions;
use Workflow\Contracts\Workflow;

class UserOnboardingWorkflow implements Workflow
{
    public function execute(array $input): Generator
    {
        $email = $input['email'];
        $name = $input['name'];

        // Step 1: Create User Account (Critical, needs retries)
        $userId = yield new CreateUserAccount(
            ['email' => $email, 'name' => $name],
            ActivityOptions::make()->withRetries(3)->withTimeout(30)
        );

        // Step 2: Wait for 1 minute (simulating a delay before welcome email)
        yield minutes(1);

        // Step 3: Send Welcome Email (Less critical, fewer retries)
        yield new SendWelcomeEmail(
            ['user_id' => $userId, 'email' => $email],
            ActivityOptions::make()->withRetries(2)
        );

        // Step 4: Inline activity for simple logging
        yield fn () => error_log("User $userId onboarded successfully.");

        return $userId;
    }

    public function handleSignal(string $signalName, array $payload): void
    {
        // Handle signals like 'CancelOnboarding'
    }
}
