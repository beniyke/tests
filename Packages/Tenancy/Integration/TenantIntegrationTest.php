<?php

declare(strict_types=1);

namespace Tests\Packages\Tenancy\Integration;

use Core\Services\ConfigServiceInterface;
use Exception;
use Helpers\File\Paths;
use Tenancy\Exceptions\TenantException;
use Tenancy\Models\Tenant;
use Tenancy\Services\TenantProvisioningService;
use Testing\Support\DatabaseTestHelper;

describe('Tenant Integration', function () {

    beforeEach(function () {
        // Setup test environment with Tenancy package migrations
        DatabaseTestHelper::setupTestEnvironment(['Tenancy']);

        $this->service = new TenantProvisioningService();
    });

    test('tenant creation lifecycle', function () {
        // Mock config to use sqlite and a safe file path for test databases
        $configService = resolve(ConfigServiceInterface::class);
        $configService->set('tenancy.database.driver', 'sqlite');
        $configService->set('tenancy.database.prefix_pattern', Paths::storagePath('database/tenants/test_'));

        $data = [
            'name' => 'Test Corp',
            'subdomain' => 'test-corp-' . uniqid(),
            'email' => 'admin@test-corp.com',
            'plan' => 'starter',
            // 'seed' => false // skip seeding for speed
        ];

        // 1. Provision Tenant
        try {
            $tenant = $this->service->create($data);
        } catch (Exception $e) {
            $this->fail("Tenant creation failed: " . $e->getMessage());
        }

        expect($tenant)->toBeInstanceOf(Tenant::class);
        $this->assertDatabaseHas('tenant', ['subdomain' => $data['subdomain']]);

        // 2. Verify Database Creation
        $dbName = $tenant->db_name;
        $this->assertFileExists($dbName, "Tenant database file {$dbName} was not created.");

        // 3. Cleanup
        $this->service->delete($tenant, true);

        // 4. Verify Cleanup
        $this->assertFileDoesNotExist($dbName, "Tenant database file {$dbName} was not dropped.");
        $this->assertDatabaseMissing('tenant', ['id' => $tenant->id]);
    });

    test('validation prevents duplicate subdomains', function () {
        $configService = resolve(ConfigServiceInterface::class);
        $configService->set('tenancy.database.driver', 'sqlite');
        $configService->set('tenancy.database.prefix_pattern', Paths::storagePath('database/tenants/test_dup_'));

        $subdomain = 'duplicate-' . uniqid();

        $data = [
            'name' => 'First Corp',
            'subdomain' => $subdomain,
            'plan' => 'starter',
        ];

        $tenant1 = $this->service->create($data);

        try {
            // Need to wrap in closure for Pest toThrow expectation check if validating external service
            // Or use try catch block with explicit failure if not thrown
            $this->service->create($data);
            $this->fail("Should have thrown TenantException");
        } catch (TenantException $e) {
            expect($e->getMessage())->toContain('already exists');
        } finally {
            // Cleanup first tenant even if test fails
            if (isset($tenant1)) {
                $this->service->delete($tenant1, true);
            }
        }
    });
});
