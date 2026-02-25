<?php

declare(strict_types=1);

namespace Tests\Packages\Academy\Integration;

use Academy\Models\AcademyAssessment;
use Academy\Models\AcademyChoice;
use Academy\Models\AcademyEnrolment;
use Academy\Models\AcademyLesson;
use Academy\Models\AcademyModule;
use Academy\Models\AcademyProgram;
use Academy\Models\AcademyQuestion;
use Academy\Services\AssessmentService;
use Academy\Services\EnrolmentManagerService;
use App\Models\User;

describe('Academy Bulk Operations', function () {
    beforeEach(function () {
        $this->refreshDatabase();
        $this->bootPackage('Academy', null, true);
        $this->bootPackage('Audit', null, true);
        $this->bootPackage('Blish', null, true);
        $this->bootPackage('Refer', null, true);
        $this->bootPackage('Wave', null, true);
    });

    test('it can bulk add questions and choices', function () {
        $program = AcademyProgram::create(['title' => 'Bulk Test', 'slug' => 'bulk-test']);
        $module = AcademyModule::create(['program_id' => $program->id, 'title' => 'Mod 1', 'order' => 1]);
        $lesson = AcademyLesson::create(['module_id' => $module->id, 'title' => 'L1', 'slug' => 'l1', 'order' => 1]);
        $assessment = AcademyAssessment::create(['lesson_id' => $lesson->id, 'title' => 'Bulk Quiz']);

        $service = new AssessmentService();
        $service->bulkAddQuestions((int) $assessment->id, [
            [
                'text' => 'Question 1',
                'type' => 'mcq',
                'points' => 5,
                'choices' => [
                    ['text' => 'C1', 'is_correct' => true],
                    ['text' => 'C2', 'is_correct' => false],
                ],
            ],
            [
                'text' => 'Question 2',
                'type' => 'short_answer',
                'points' => 10,
            ],
        ]);

        expect(AcademyQuestion::where('assessment_id', $assessment->id)->count())->toBe(2);

        $q1 = AcademyQuestion::where('text', 'Question 1')->first();
        expect(AcademyChoice::where('question_id', $q1->id)->count())->toBe(2);

        $q2 = AcademyQuestion::where('text', 'Question 2')->first();
        expect($q2->points)->toBe(10);
    });

    test('it can bulk enrol users', function () {
        $program = AcademyProgram::create(['title' => 'Enrol Test', 'slug' => 'enrol-test']);

        $user1 = User::create(['name' => 'U1', 'email' => 'u1@example.com', 'password' => 'pass', 'gender' => 'male']);
        $user2 = User::create(['name' => 'U2', 'email' => 'u2@example.com', 'password' => 'pass', 'gender' => 'male']);

        $service = new EnrolmentManagerService();
        $service->bulkEnrol([$user1->id, $user2->id], $program->id);

        expect(AcademyEnrolment::where('program_id', $program->id)->count())->toBe(2);
        expect(AcademyEnrolment::where('user_id', $user1->id)->where('program_id', $program->id)->exists())->toBeTrue();
        expect(AcademyEnrolment::where('user_id', $user2->id)->where('program_id', $program->id)->exists())->toBeTrue();
    });

    test('individual addQuestion and addChoice methods work', function () {
        $program = AcademyProgram::create(['title' => 'Indiv Test', 'slug' => 'indiv-test']);
        $module = AcademyModule::create(['program_id' => $program->id, 'title' => 'Mod 1', 'order' => 1]);
        $lesson = AcademyLesson::create(['module_id' => $module->id, 'title' => 'L1', 'slug' => 'l1-indiv', 'order' => 1]);
        $assessment = AcademyAssessment::create(['lesson_id' => $lesson->id, 'title' => 'Indiv Quiz']);

        $service = new AssessmentService();
        $question = $service->addQuestion((int) $assessment->id, ['text' => 'What is 2+2?', 'points' => 5]);
        $choice = $service->addChoice((int) $question->id, '4', true);

        expect($question)->toBeInstanceOf(AcademyQuestion::class);
        expect($choice)->toBeInstanceOf(AcademyChoice::class);
        expect($choice->is_correct)->toBeTrue();
    });
});
