<?php

declare(strict_types=1);

namespace Tests\Packages\Release\Unit;

use Release\Services\VersionService;

afterEach(function () {
    // Clean up version file if it was created
    $versionFile = __DIR__ . '/../../../../version';
    if (file_exists($versionFile)) {
        // unlink($versionFile);
    }
});

it('returns default version if file not exists', function () {
    $version = VersionService::current();
    expect($version)->toBeString()
        ->and($version)->toMatch('/^\d+\.\d+\.\d+$/');
});
