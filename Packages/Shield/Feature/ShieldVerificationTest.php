<?php

declare(strict_types=1);

use Audit\Audit;
use Core\Services\ConfigServiceInterface;
use Database\DB;
use Shield\Drivers\CaptchaDriverInterface;
use Shield\Services\ShieldManagerService;
use Shield\Shield;
use Testing\Support\DatabaseTestHelper;

beforeEach(function () {
    DatabaseTestHelper::setupTestEnvironment(['Shield', 'Audit'], true);
});

test('facade verify returns true for successful driver response', function () {
    $config = resolve(ConfigServiceInterface::class);
    $manager = new ShieldManagerService($config);
    container()->instance(ShieldManagerService::class, $manager);

    $mockDriver = Mockery::mock(CaptchaDriverInterface::class);
    $mockDriver->shouldReceive('verify')->andReturn(true);

    // Inject mock driver into manager
    $reflection = new ReflectionClass($manager);
    $property = $reflection->getProperty('drivers');
    $property->setAccessible(true);
    $property->setValue($manager, ['recaptcha' => $mockDriver]);

    $result = Shield::verify('valid-token');

    expect($result)->toBeTrue();

    // Verify Audit Log
    $log = DB::table('audit_log')->where('event', 'captcha.verified')->first();
    expect($log)->not->toBeNull()
        ->and(json_decode($log->metadata, true)['driver'])->toBe('recaptcha');
});

test('facade verify returns false and logs failure for invalid token', function () {
    $config = resolve(ConfigServiceInterface::class);
    $manager = new ShieldManagerService($config);
    container()->instance(ShieldManagerService::class, $manager);

    $mockDriver = Mockery::mock(CaptchaDriverInterface::class);
    $mockDriver->shouldReceive('verify')->andReturn(false);

    $reflection = new ReflectionClass($manager);
    $property = $reflection->getProperty('drivers');
    $property->setAccessible(true);
    $property->setValue($manager, ['recaptcha' => $mockDriver]);

    $result = Shield::verify('invalid-token');

    expect($result)->toBeFalse();

    // Verify Audit Log
    $log = DB::table('audit_log')->where('event', 'captcha.failed')->first();
    expect($log)->not->toBeNull();
});

test('facade verify handles driver exceptions and logs error', function () {
    $config = resolve(ConfigServiceInterface::class);
    $manager = new ShieldManagerService($config);
    container()->instance(ShieldManagerService::class, $manager);

    $mockDriver = Mockery::mock(CaptchaDriverInterface::class);
    $mockDriver->shouldReceive('verify')->andThrow(new RuntimeException('Network timeout'));

    $reflection = new ReflectionClass($manager);
    $property = $reflection->getProperty('drivers');
    $property->setAccessible(true);
    $property->setValue($manager, ['recaptcha' => $mockDriver]);

    $result = Shield::verify('error-token');

    expect($result)->toBeFalse();

    // Verify Audit Log
    $log = DB::table('audit_log')->where('event', 'captcha.error')->first();
    expect($log)->not->toBeNull()
        ->and(json_decode($log->metadata, true)['error'])->toBe('Network timeout');
});
