<?php

declare(strict_types=1);

namespace Tests\Packages\Vault\Unit;

use Core\Ioc\Container;
use Vault\Services\VaultManagerService;
use Vault\Vault;

describe('Vault Facade', function () {
    it('proxies calls to VaultManagerService', function () {
        // Manually mock the method we want to test to ensure strict control
        $mock = new class () {
            public function allocate($account, $quota)
            {
                return 1;
            }
        };

        // Bind the mock to the container
        if (class_exists(Container::class)) {
            $container = Container::getInstance();
            $container->instance(VaultManagerService::class, $mock);
        } else {
            $this->markTestSkipped('Container not available for Facade test.');
        }

        // Verify resolution works
        $resolved = resolve(VaultManagerService::class);
        expect($resolved)->toBe($mock);

        // Perform the Facade call
        $result = Vault::allocate('account-123', 1000);

        expect($result)->toBe(1);
    });

    afterEach(function () {
        if (class_exists(Container::class)) {
            Container::getInstance()->forgetCachedInstance(VaultManagerService::class);
        }
    });
});
