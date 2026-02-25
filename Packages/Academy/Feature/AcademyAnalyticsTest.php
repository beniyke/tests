<?php

declare(strict_types=1);

namespace Tests\Packages\Academy\Feature;

use Academy\Academy;
use Academy\Enums\EnrolmentStatus;
use Academy\Enums\PaymentStatus;
use Academy\Models\AcademyAssessment;
use Academy\Models\AcademyEnrolment;
use Academy\Models\AcademyGrade;
use Academy\Models\AcademyInstalment;
use Academy\Models\AcademyLesson;
use Academy\Models\AcademyModule;
use Academy\Models\AcademyRating;
use Academy\Models\AcademySubmission;
use App\Models\User;
use Helpers\DateTimeHelper;

describe('Academy Analytics Service', function () {
    beforeEach(function () {
        $this->refreshDatabase();
        $this->bootPackage('Academy', null, true);

        // Setup users
        $this->instructor = User::create(['name' => 'Instructor', 'email' => 'inst@example.com', 'password' => 'pass', 'gender' => 'male']);
        $this->student1 = User::create(['name' => 'Student 1', 'email' => 's1@example.com', 'password' => 'pass', 'gender' => 'male']);
        $this->student2 = User::create(['name' => 'Student 2', 'email' => 's2@example.com', 'password' => 'pass', 'gender' => 'male']);

        // Setup program
        $this->program = Academy::program()
            ->titled('Analytics Test Program')
            ->withInstructors([(int) $this->instructor->id])
            ->create();

        // Setup enrolment for student 1
        $this->enrolment1 = AcademyEnrolment::create([
            'program_id' => $this->program->id,
            'user_id' => $this->student1->id,
            'status' => EnrolmentStatus::ACTIVE,
            'progress_percent' => 50,
            'enrolled_at' => DateTimeHelper::now()->subDays(5)
        ]);

        // Setup enrolment for student 2 (Completed)
        $this->enrolment2 = AcademyEnrolment::create([
            'program_id' => $this->program->id,
            'user_id' => $this->student2->id,
            'status' => EnrolmentStatus::COMPLETED,
            'progress_percent' => 100,
            'enrolled_at' => DateTimeHelper::now()->subDays(10),
            'completed_at' => DateTimeHelper::now()
        ]);
    });

    test('getProgramMetrics returns correct stats', function () {
        // Add a rating
        AcademyRating::create([
            'program_id' => $this->program->id,
            'user_id' => $this->student1->id,
            'rating' => 4,
            'comment' => 'Good'
        ]);

        $metrics = Academy::analytics()->getProgramMetrics((int) $this->program->id);

        expect($metrics['total_enrolments'])->toBe(2);
        expect($metrics['active_students'])->toBe(1);
        expect($metrics['completion_rate'])->toBe(50); // 1 out of 2
        expect($metrics['average_progress'])->toBe(75); // (50 + 100) / 2
        expect($metrics['average_rating'])->toBe(4.0);
    });

    test('getRevenueInsights calculates paid amounts', function () {
        // Create paid instalments
        AcademyInstalment::create([
            'enrolment_id' => $this->enrolment1->id,
            'amount' => 5000,
            'status' => PaymentStatus::PAID,
            'paid_at' => DateTimeHelper::now()
        ]);

        AcademyInstalment::create([
            'enrolment_id' => $this->enrolment2->id,
            'amount' => 10000,
            'status' => PaymentStatus::PAID,
            'paid_at' => DateTimeHelper::now()
        ]);

        // Create pending instalment (should not be counted)
        AcademyInstalment::create([
            'enrolment_id' => $this->enrolment1->id,
            'amount' => 2000,
            'status' => PaymentStatus::PENDING
        ]);

        $revenue = Academy::analytics()->getRevenueInsights((int) $this->program->id);

        expect($revenue['total_revenue'])->toBe(15000);
    });

    test('getHistory returns trend data for enrolments', function () {
        $history = Academy::analytics()->getHistory('enrolments', '30d', ['program_id' => $this->program->id]);

        expect($history['labels'])->not->toBeEmpty();
        expect(array_sum($history['values']))->toBe(2);
    });

    test('getLearnerPerformance returns scores trend', function () {
        $module = AcademyModule::create(['program_id' => $this->program->id, 'title' => 'M1']);
        $lesson = AcademyLesson::create(['module_id' => $module->id, 'title' => 'L1', 'slug' => 'l1']);
        $assessment = AcademyAssessment::create(['lesson_id' => $lesson->id, 'type' => 'quiz']);

        $sub = AcademySubmission::create([
            'assessment_id' => $assessment->id,
            'enrolment_id' => $this->enrolment1->id,
            'status' => 'graded',
            'submitted_at' => DateTimeHelper::now()
        ]);

        AcademyGrade::create([
            'submission_id' => $sub->id,
            'percent_score' => 85,
            'graded_at' => DateTimeHelper::now()
        ]);

        $perf = Academy::analytics()->getLearnerPerformance((int) $this->enrolment1->id);

        expect($perf['values'])->toContain(85);
        expect($perf['labels'])->not->toBeEmpty();
    });

    test('getInstructorDashboard returns aggregated data', function () {
        $dashboard = Academy::analytics()->getInstructorDashboard((int) $this->instructor->id);

        expect($dashboard)->toHaveKey('total_students');
        expect($dashboard['total_students'])->toBe(2);
        expect($dashboard['average_completion'])->toBe(50);
    });

    test('getRatingSummary returns breakdown', function () {
        // Create ratings from distinct users to avoid unique constraint
        $studentX = User::create(['name' => 'X', 'email' => 'x@example.com', 'password' => 'pass', 'gender' => 'male']);
        AcademyRating::create(['program_id' => $this->program->id, 'user_id' => $studentX->id, 'rating' => 5, 'comment' => 'Great']);

        $studentY = User::create(['name' => 'Y', 'email' => 'y@example.com', 'password' => 'pass', 'gender' => 'male']);
        AcademyRating::create(['program_id' => $this->program->id, 'user_id' => $studentY->id, 'rating' => 4, 'comment' => 'Good']);

        $summary = Academy::analytics()->getRatingSummary((int) $this->program->id);

        expect($summary['total'])->toBe(2);
        expect($summary['breakdown'][5])->toBe(1);
        expect($summary['breakdown'][4])->toBe(1);
    });

    test('getLeaderboard returns ranked students', function () {
        $leaderboard = Academy::analytics()->getLeaderboard((int) $this->program->id);

        expect($leaderboard)->toHaveCount(2);
        expect($leaderboard[0]['progress_percent'])->toBe(100);
        expect($leaderboard[1]['progress_percent'])->toBe(50);
    });

    test('getAssessmentLeaderboard returns graded submissions', function () {
        $module = AcademyModule::create(['program_id' => $this->program->id, 'title' => 'M1']);
        $lesson = AcademyLesson::create(['module_id' => $module->id, 'title' => 'L1', 'slug' => 'l1']);
        $assessment = AcademyAssessment::create(['lesson_id' => $lesson->id, 'type' => 'quiz']);

        $sub = AcademySubmission::create([
            'assessment_id' => $assessment->id,
            'enrolment_id' => $this->enrolment1->id,
            'status' => 'graded',
            'submitted_at' => DateTimeHelper::now()
        ]);

        AcademyGrade::create([
            'submission_id' => $sub->id,
            'percent_score' => 90,
            'graded_at' => DateTimeHelper::now()
        ]);

        $leaderboard = Academy::analytics()->getAssessmentLeaderboard((int) $assessment->id);

        expect($leaderboard)->toHaveCount(1);
        expect($leaderboard[0]['percent_score'])->toBe(90);
    });
});
