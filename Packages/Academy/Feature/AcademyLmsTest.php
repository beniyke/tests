<?php

declare(strict_types=1);

namespace Tests\Packages\Academy\Feature;

use Academy\Academy;
use Academy\Enums\EnrolmentStatus;
use Academy\Enums\ProgramStatus;
use Academy\Models\AcademyAssessment;
use Academy\Models\AcademyBadge;
use Academy\Models\AcademyBadgeAward;
use Academy\Models\AcademyCertificate;
use Academy\Models\AcademyEnrolment;
use Academy\Models\AcademyLesson;
use Academy\Models\AcademyModule;
use Academy\Models\AcademyProgram;
use App\Models\User;

describe('Academy LMS Lifecycle', function () {
    beforeEach(function () {
        $this->refreshDatabase();

        $this->bootPackage('Academy', null, true);
        $this->bootPackage('Audit', null, true);

        // Disable optional integrations for core lifecycle test
        config()->set('academy.integrations.blish', false);
        config()->set('academy.integrations.wave', false);
        config()->set('academy.integrations.wallet', false);
        config()->set('academy.rewards.enabled', false);
    });

    test('full learner journey from enrolment to certification', function () {
        // 1. Setup Program & Content
        $program = AcademyProgram::create([
            'title' => 'Mastering Pest',
            'slug' => 'mastering-pest',
            'status' => ProgramStatus::PUBLISHED
        ]);

        $module = AcademyModule::create([
            'program_id' => $program->id,
            'title' => 'Getting Started',
            'order' => 1
        ]);

        $lesson = AcademyLesson::create([
            'module_id' => $module->id,
            'title' => 'Intro to Testing',
            'slug' => 'intro-to-testing',
            'content' => 'Sample content',
            'order' => 1
        ]);

        $assessment = AcademyAssessment::create([
            'lesson_id' => $lesson->id,
            'title' => 'Final Exam',
            'attempts_allowed' => 1,
        ]);

        // 2. Enrolment
        $user = User::create([
            'name' => 'Learner',
            'email' => 'learner@example.com',
            'password' => 'password',
            'gender' => 'male',
        ]);
        $userId = $user->id;
        $enrolment = Academy::enrol($userId, (int) $program->id);
        expect($enrolment->status)->toBe(EnrolmentStatus::PENDING);

        // 3. Activation
        Academy::enrolments()->activate($enrolment);
        expect($enrolment->fresh()->status)->toBe(EnrolmentStatus::ACTIVE);

        // 4. Progress Tracking
        Academy::progress()->completeLesson((int) $enrolment->id, (int) $lesson->id, 300);
        expect($enrolment->fresh()->progress_percent)->toBe(100); // 1 lesson in 1 module = 100%

        // 5. Assessment & Grading
        $submission = Academy::assessments()->startAttempt((int) $assessment->id, (int) $enrolment->id);
        Academy::assessments()->submit($submission, []);

        expect($submission->fresh()->status->value)->toBe('graded');

        // 6. Program Completion (Manual status update as no service method exists yet)
        $enrolment->update(['status' => EnrolmentStatus::COMPLETED]);
        expect($enrolment->fresh()->status)->toBe(EnrolmentStatus::COMPLETED);

        // 7. Credentials
        // Certificate is issued automatically by IssueCertificateListener when progress reaches 100%
        $certificate = AcademyCertificate::where('enrolment_id', $enrolment->id)->first();
        expect($certificate)->toBeInstanceOf(AcademyCertificate::class);
        expect((int) $certificate->enrolment_id)->toBe((int) $enrolment->id);

        $badge = AcademyBadge::create(['name' => 'Pest Master', 'slug' => 'pest-master']);
        $award = Academy::badges()->award($userId, (int) $badge->id, (int) $program->id);
        expect($award)->toBeInstanceOf(AcademyBadgeAward::class);
    });

    test('it correctly identifies enrolled users', function () {
        $p1 = AcademyProgram::create(['title' => 'P1', 'slug' => 'p1']);
        $e1 = AcademyEnrolment::create(['user_id' => 1, 'program_id' => $p1->id, 'status' => EnrolmentStatus::ACTIVE]);

        $isEnrolled = Academy::enrolments()->isEnrolled(1, (int) $p1->id);
        expect($isEnrolled)->toBeTrue();
    });
});
