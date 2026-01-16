<?php

declare(strict_types=1);

namespace Tests\Packages\Release\Feature;

use Release\Commands\ReleaseCheckCommand;
use Release\Commands\ReleaseUpdateCommand;
use Release\Commands\ReleaseVersionCommand;

it('has release check command', function () {
    expect(class_exists(ReleaseCheckCommand::class))->toBeTrue();
});

it('has release update command', function () {
    expect(class_exists(ReleaseUpdateCommand::class))->toBeTrue();
});

it('has release version command', function () {
    expect(class_exists(ReleaseVersionCommand::class))->toBeTrue();
});
