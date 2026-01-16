<?php

declare(strict_types=1);

namespace Tests\Packages\Tenancy\Unit;

use Mockery;
use Tenancy\Models\Tenant;
use Tenancy\Tenancy;
use Tenancy\TenantManager;

describe('Tenancy Facade', function () {

    beforeEach(function () {
        $this->manager = Mockery::mock(TenantManager::class);
        container()->instance(TenantManager::class, $this->manager);
    });

    afterEach(function () {
        Mockery::close();
    });

    test('identify delegates to TenantManager', function () {
        $tenant = new Tenant();
        $this->manager->shouldReceive('identifyBySubdomain')->once()->with('acme')->andReturn($tenant);

        $result = Tenancy::identify('acme');

        expect($result)->toBe($tenant);
    });

    test('setContext delegates to TenantManager', function () {
        $tenant = new Tenant();
        $this->manager->shouldReceive('setContext')->once()->with($tenant);

        Tenancy::setContext($tenant);
    });

    test('current delegates to TenantManager', function () {
        $tenant = new Tenant();
        $this->manager->shouldReceive('current')->once()->andReturn($tenant);

        $result = Tenancy::current();

        expect($result)->toBe($tenant);
    });

    test('reset delegates to TenantManager', function () {
        $this->manager->shouldReceive('reset')->once();

        Tenancy::reset();
    });

    test('isEnabled delegates to TenantManager', function () {
        $this->manager->shouldReceive('isEnabled')->once()->andReturn(true);

        expect(Tenancy::isEnabled())->toBeTrue();
    });

    test('testConnection delegates to TenantManager', function () {
        $tenant = new Tenant();
        $this->manager->shouldReceive('testConnection')->once()->with($tenant)->andReturn(true);

        expect(Tenancy::testConnection($tenant))->toBeTrue();
    });

    test('invalidateCache delegates to TenantManager', function () {
        $tenant = new Tenant();
        $this->manager->shouldReceive('invalidateCache')->once()->with($tenant);

        Tenancy::invalidateCache($tenant);
    });
});
