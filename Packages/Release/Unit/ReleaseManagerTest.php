<?php

declare(strict_types=1);

namespace Tests\Packages\Release\Unit;

use Core\Services\ConfigServiceInterface;
use Mockery as m;
use Release\Services\ReleaseManagerService;

beforeEach(function () {
    $this->configMock = m::mock(ConfigServiceInterface::class);
    $this->configMock->shouldReceive('get')->with('release.endpoint')->andReturn('https://api.example.com');
    $this->configMock->shouldReceive('get')->with('release.tmp_folder')->andReturn('App/storage/tmp');
    $this->configMock->shouldReceive('get')->with('release.maintenance_mode')->andReturn(true);
    $this->configMock->shouldReceive('get')->with('release.backup')->andReturn(true);
    $this->configMock->shouldReceive('get')->with('release.authorized_users')->andReturn([]);
    $this->configMock->shouldReceive('get')->with('release.exclude')->andReturn([]);

    $this->manager = new ReleaseManagerService($this->configMock);
});

afterEach(function () {
    m::close();
});

it('returns null on check failure', function () {
    // We need to mock the Curl client usage within the method.
    // Since Curl is instantiated inside check(), we test the failure path (network failure).

    $result = $this->manager->check();
    expect($result)->toBeNull();
});
