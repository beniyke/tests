<?php

declare(strict_types=1);

namespace Tests\Packages\Academy\Integration;

use Academy\Enums\ProgramStatus;
use Academy\Models\AcademyProgram;
use Academy\Services\ProgramManagerService;

describe('Program Management', function () {
    beforeEach(function () {
        $this->refreshDatabase();
        $this->bootPackage('Academy', null, true);
        $this->bootPackage('Audit', null, true);
    });

    test('it creates a program in draft status', function () {
        $service = new ProgramManagerService();
        $program = $service->create(['title' => 'Pest Test Program', 'slug' => 'pest-test']);

        expect($program)->toBeInstanceOf(AcademyProgram::class);
        expect($program->title)->toBe('Pest Test Program');
        expect($program->status)->toBe(ProgramStatus::DRAFT);
    });

    test('it publishes a program', function () {
        $service = new ProgramManagerService();
        $program = AcademyProgram::create(['title' => 'Draft', 'slug' => 'draft']);

        $service->publish($program);

        expect($program->fresh()->status)->toBe(ProgramStatus::PUBLISHED);
    });
})->covers(ProgramManagerService::class);
