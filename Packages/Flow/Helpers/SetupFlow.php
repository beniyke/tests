<?php

declare(strict_types=1);

namespace Tests\Packages\Flow\Helpers;

use Testing\Support\DatabaseTestHelper;

trait SetupFlow
{
    public function setupFlow(): void
    {
        // Setup test environment with App migrations (user table) and Flow package migrations
        DatabaseTestHelper::setupTestEnvironment(['Flow'], true);
    }
}
