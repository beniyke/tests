<?php

declare(strict_types=1);

namespace Tests\Packages\Academy\Integration;

use Academy\Models\AcademyEnrolment;
use Academy\Models\AcademyProgram;
use Academy\Models\AcademyProgress;
use Academy\Services\ProgressTrackingService;

describe('Progress Tracking', function () {
    beforeEach(function () {
        $this->refreshDatabase();
        $this->bootPackage('Academy', null, true);
        $this->bootPackage('Audit', null, true);
    });

    test('it tracks lesson completion with time spent', function () {
        $program = AcademyProgram::create(['title' => 'Test', 'slug' => 'test']);
        $enrolment = AcademyEnrolment::create(['user_id' => 1, 'program_id' => $program->id]);

        $service = new ProgressTrackingService();
        $progress = $service->completeLesson((int) $enrolment->id, 99, 600);

        expect($progress)->toBeInstanceOf(AcademyProgress::class);
        expect((int) $progress->enrolment_id)->toBe((int) $enrolment->id);
        expect($progress->lesson_id)->toBe(99);
        expect($progress->time_spent)->toBe(600);
        expect($progress->completed_at)->not->toBeNull();
    });
})->covers(ProgressTrackingService::class);
