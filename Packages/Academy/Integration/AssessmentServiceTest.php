<?php

declare(strict_types=1);

namespace Tests\Packages\Academy\Integration;

use Academy\Enums\SubmissionStatus;
use Academy\Models\AcademyAssessment;
use Academy\Models\AcademyChoice;
use Academy\Models\AcademyGrade;
use Academy\Models\AcademyLesson;
use Academy\Models\AcademyModule;
use Academy\Models\AcademyProgram;
use Academy\Models\AcademyQuestion;
use Academy\Models\AcademySubmission;
use Academy\Services\AssessmentService;

describe('Assessment Service', function () {
    beforeEach(function () {
        $this->refreshDatabase();
        $this->bootPackage('Academy', null, true);
        $this->bootPackage('Audit', null, true);
    });

    test('it starts an assessment attempt', function () {
        $program = AcademyProgram::create(['title' => 'Test', 'slug' => 'test']);
        $module = AcademyModule::create(['program_id' => $program->id, 'title' => 'Mod 1', 'order' => 1]);

        $lesson = AcademyLesson::create(['module_id' => $module->id, 'title' => 'L1', 'slug' => 'l1', 'order' => 1]);
        $assessment = AcademyAssessment::create([
            'lesson_id' => $lesson->id,
            'title' => 'Test Assessment',
            'attempts_allowed' => 1,
        ]);

        $service = new AssessmentService();
        $submission = $service->startAttempt((int) $assessment->id, 10);

        expect($submission)->toBeInstanceOf(AcademySubmission::class);
        expect($submission->status)->toBe(SubmissionStatus::PENDING);
        expect($submission->attempt_number)->toBe(1);
    });

    test('it auto-grades MCQ submission', function () {
        $program = AcademyProgram::create(['title' => 'Test', 'slug' => 'test']);
        $module = AcademyModule::create(['program_id' => $program->id, 'title' => 'Mod 1', 'order' => 1]);
        $lesson = AcademyLesson::create(['module_id' => $module->id, 'title' => 'L1', 'slug' => 'l1-auto', 'order' => 1]);
        $assessment = AcademyAssessment::create(['lesson_id' => (int) $lesson->id, 'title' => 'Quiz', 'passing_score' => 50]);
        $question = AcademyQuestion::create(['assessment_id' => (int) $assessment->id, 'text' => '1+1?', 'points' => 10]);
        $choice = AcademyChoice::create(['question_id' => (int) $question->id, 'text' => '2', 'is_correct' => true]);

        $submission = AcademySubmission::create([
            'assessment_id' => $assessment->id,
            'enrolment_id' => 10,
            'status' => SubmissionStatus::PENDING,
        ]);

        $submission->answers()->create([
            'question_id' => $question->id,
            'choice_id' => $choice->id,
        ]);

        $service = new AssessmentService();
        $service->autoGrade($submission);

        $grade = AcademyGrade::where('submission_id', $submission->id)->first();
        expect($grade->percent_score)->toBe(100);
        expect($grade->is_passing)->toBeTrue();
    });
})->covers(AssessmentService::class);
