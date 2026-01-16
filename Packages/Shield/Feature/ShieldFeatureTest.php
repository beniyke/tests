<?php

declare(strict_types=1);

use Core\Ioc\Container;
use Shield\Drivers\GoogleRecaptchaDriver;
use Shield\Services\ShieldManagerService;
use Shield\Shield;

beforeEach(function () {
    $this->bootPackage('Shield');
});

afterEach(function () {
    Mockery::close();
    // Reset any container overrides if needed, though Mockery::close() helps with mocks
});

test('manager resolves default driver', function () {
    $manager = resolve(ShieldManagerService::class);
    $driver = $manager->driver('recaptcha');

    expect($driver)->toBeInstanceOf(GoogleRecaptchaDriver::class);
});

test('facade forwards calls', function () {
    // Mock the resolved instance
    $mock = Mockery::mock(ShieldManagerService::class);
    $mock->shouldReceive('verify')->once()->with('token', null)->andReturn(true);

    Container::getInstance()->instance(ShieldManagerService::class, $mock);

    expect(Shield::verify('token'))->toBeTrue();
});
