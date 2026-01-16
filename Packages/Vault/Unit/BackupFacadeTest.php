<?php

declare(strict_types=1);

namespace Tests\Packages\Vault\Unit;

use Core\Ioc\Container;
use Vault\Backup;
use Vault\Services\BackupService;

describe('Backup Facade', function () {
    it('proxies calls to BackupService', function () {
        // Manually mock the method we want to test to ensure strict control
        $mock = new class () {
            public function create($account)
            {
                return 'path/to/backup.zip';
            }
        };

        // Bind the mock to the container
        if (class_exists(Container::class)) {
            $container = Container::getInstance();
            $container->singleton(BackupService::class, function () use ($mock) {
                return $mock;
            });
        } else {
            $this->markTestSkipped('Container not available for Facade test.');
        }

        // Verify resolution works
        $resolved = resolve(BackupService::class);
        expect($resolved)->toBe($mock);

        // Perform the Facade call
        $result = Backup::create('account-123');

        expect($result)->toBe('path/to/backup.zip');
    });
});
