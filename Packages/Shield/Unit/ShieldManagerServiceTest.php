<?php

declare(strict_types=1);

use Audit\Audit;
use Audit\Services\AuditManagerService;
use Audit\Services\Builders\LogBuilder;
use Core\Services\ConfigServiceInterface;
use Shield\Drivers\CaptchaDriverInterface;
use Shield\Drivers\GoogleRecaptchaDriver;
use Shield\Services\ShieldManagerService;

test('it resolves default driver', function () {
    // Mock Config
    $config = Mockery::mock(ConfigServiceInterface::class);
    $config->shouldReceive('get')->with('shield.default', 'recaptcha')->andReturn('recaptcha');
    $config->shouldReceive('get')->with('shield.drivers.recaptcha')->andReturn(['secret' => 's', 'site_key' => 'k']);

    $manager = new ShieldManagerService($config);
    $driver = $manager->driver();

    expect($driver)->toBeInstanceOf(GoogleRecaptchaDriver::class);
});

test('verify method calls driver and logs audit event', function () {
    $config = Mockery::mock(ConfigServiceInterface::class);
    $config->shouldReceive('get')->with('shield.default', 'recaptcha')->andReturn('recaptcha');
    $config->shouldReceive('get')->with('shield.drivers.recaptcha')->andReturn(['secret' => 's', 'site_key' => 'k']);

    // Mock Audit dependency
    $logBuilder = Mockery::mock(LogBuilder::class);
    $logBuilder->shouldReceive('event')->andReturnSelf();
    $logBuilder->shouldReceive('metadata')->andReturnSelf();
    $logBuilder->shouldReceive('log');

    $auditManager = Mockery::mock(AuditManagerService::class);
    $auditManager->shouldReceive('make')->andReturn($logBuilder);
    container()->instance(AuditManagerService::class, $auditManager);

    $manager = new ShieldManagerService($config);

    // Mock Driver
    $mockDriver = Mockery::mock(CaptchaDriverInterface::class);
    $mockDriver->shouldReceive('verify')->with('valid-token', '1.2.3.4')->andReturn(true);

    // Inject mock driver
    $reflection = new ReflectionClass($manager);
    $property = $reflection->getProperty('drivers');
    $property->setAccessible(true);
    $property->setValue($manager, ['recaptcha' => $mockDriver]);

    $result = $manager->verify('valid-token', '1.2.3.4');

    expect($result)->toBeTrue();
});

test('it throws exception for unsupported driver', function () {
    $config = Mockery::mock(ConfigServiceInterface::class);
    $config->shouldReceive('get')->with('shield.default', 'recaptcha')->andReturn('invalid');
    $config->shouldReceive('get')->with('shield.drivers.invalid')->andReturn(['secret' => 's']);

    $manager = new ShieldManagerService($config);

    expect(fn () => $manager->driver())->toThrow(InvalidArgumentException::class);
});
