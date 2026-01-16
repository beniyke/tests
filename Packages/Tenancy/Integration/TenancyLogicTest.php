<?php

declare(strict_types=1);

namespace Tests\Packages\Tenancy\Integration;

use Core\Services\ConfigServiceInterface;
use Helpers\File\Contracts\CacheInterface;
use Helpers\File\Paths;
use Tenancy\Exceptions\TenantException;
use Tenancy\Models\Tenant;
use Tenancy\Services\TenantProvisioningService;
use Tenancy\TenantManager;
use Throwable;

describe('Tenancy System', function () {

    beforeEach(function () {
        // Boot the package with migrations
        $this->bootPackage('Tenancy', runMigrations: true);

        // Tenancy configuration overrides for testing (AFTER boot to avoid being overwritten)
        $configService = resolve(ConfigServiceInterface::class);
        $configService->set('tenancy.enabled', true);
        $configService->set('tenancy.database.driver', 'sqlite');
        $configService->set('tenancy.database.prefix_pattern', Paths::storagePath('database/tenants/test_unit_'));

        // Clean up any existing test tenants
        try {
            Tenant::query()->where('subdomain', 'like', 'test%')->delete();
        } catch (Throwable $e) {
            // Table might not exist yet if migrations failed
        }
    });

    test('validates subdomain format', function () {
        expect(Tenant::isValidSubdomain('test-tenant'))->toBeTrue()
            ->and(Tenant::isValidSubdomain('test_tenant'))->toBeFalse()
            ->and(Tenant::isValidSubdomain('Test-Tenant'))->toBeFalse()
            ->and(Tenant::isValidSubdomain('admin'))->toBeFalse();
    });

    test('encrypts database password', function () {
        $tenant = new Tenant();
        $tenant->db_password = 'secret123';

        // Internal attribute should be encrypted (not equal to plain text)
        expect($tenant->attributes['db_password'])->not->toBe('secret123')
            // Accessor should decrypt it back
            ->and($tenant->db_password)->toBe('secret123');
    });

    test('checks tenant status correctly', function () {
        $tenant = new Tenant([
            'status' => 'active',
            'expires_at' => null,
        ]);

        expect($tenant->isActive())->toBeTrue()
            ->and($tenant->isSuspended())->toBeFalse();
    });

    test('identifies tenant by subdomain', function () {
        $tenant = Tenant::create([
            'name' => 'Test Company',
            'subdomain' => 'testcompany',
            'status' => 'active',
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_name' => 'tenant_testcompany',
            'db_user' => 'tenant_testcompany',
            'db_password' => 'password123',
        ]);

        // Clear cache to ensure fresh lookup
        $cache = resolve(CacheInterface::class);
        $cache->delete('tenant:testcompany');

        $manager = new TenantManager(
            resolve(ConfigServiceInterface::class),
            $cache
        );
        $found = $manager->identifyBySubdomain('testcompany');

        expect($found)->not->toBeNull()
            ->and($found->subdomain)->toBe('testcompany');

        $tenant->delete();
    });

    test('sanitizes subdomain input', function () {
        $manager = new TenantManager(
            resolve(ConfigServiceInterface::class),
            resolve(CacheInterface::class)
        );
        // Should sanitize 'Test@Company' to 'testcompany' and return null (not found)
        // instead of throwing invalid subdomain exception
        $result = $manager->identifyBySubdomain('Test@Company');
        expect($result)->toBeNull();
    });

    test('prevents context switching mid-request', function () {
        $tenant1 = Tenant::create([
            'name' => 'Tenant 1',
            'subdomain' => 'tenant1',
            'status' => 'active',
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_name' => 'tenant_tenant1',
            'db_user' => 'tenant_tenant1',
            'db_password' => 'password123',
        ]);

        $tenant2 = Tenant::create([
            'name' => 'Tenant 2',
            'subdomain' => 'tenant2',
            'status' => 'active',
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_name' => 'tenant_tenant2',
            'db_user' => 'tenant_tenant2',
            'db_password' => 'password123',
        ]);

        $manager = new TenantManager(
            resolve(ConfigServiceInterface::class),
            resolve(CacheInterface::class)
        );
        $manager->setContext($tenant1);

        try {
            expect(fn () => $manager->setContext($tenant2))->toThrow(TenantException::class);
        } finally {
            $manager->reset();
            $tenant1->delete();
            $tenant2->delete();
        }
    });

    test('creates tenant with database', function () {
        $service = new TenantProvisioningService();

        $tenant = $service->create([
            'name' => 'Test Provisioned Tenant',
            'subdomain' => 'testprovisioned',
            'plan' => 'free',
            'max_users' => 10,
            'max_storage_mb' => 1000,
        ]);

        expect($tenant)->toBeInstanceOf(Tenant::class)
            ->and($tenant->subdomain)->toBe('testprovisioned')
            ->and($tenant->db_name)->toContain('test_unit_testprovisioned');

        // Cleanup
        $service->delete($tenant, true);
    });

    test('validates required fields', function () {
        $service = new TenantProvisioningService();

        expect(fn () => $service->create(['name' => 'Test']))->toThrow(TenantException::class);
    });

    test('prevents duplicate subdomains', function () {
        $service = new TenantProvisioningService();

        // Use unique subdomain to avoid collision with other tests if cleanup fails
        $subdomain = 'duplicate' . uniqid();

        $tenant1 = $service->create([
            'name' => 'First Tenant',
            'subdomain' => $subdomain,
        ]);

        try {
            // Expecting exception
            $service->create([
                'name' => 'Second Tenant',
                'subdomain' => $subdomain,
            ]);
            // If no exception, fail
            $this->fail("Should have thrown TenantException");
        } catch (TenantException $e) {
            expect($e->getMessage())->toContain('already exists');
        } finally {
            $service->delete($tenant1, true);
        }
    });

    test('returns null when no tenant context', function () {
        expect(tenant())->toBeNull();
    });

    test('checks multi tenant mode', function () {
        expect(is_multi_tenant())->toBeTrue();
    });

    test('generates tenant cache key', function () {
        $key = tenant_cache_key('users');
        expect($key)->toBe('users'); // No tenant context
    });
});
