<?php

declare(strict_types=1);

namespace Tests\Packages\Academy\Integration;

use Academy\Academy;
use Academy\Enums\EnrolmentStatus;
use Academy\Enums\VideoProvider;
use Academy\Models\AcademyAnswer;
use Academy\Models\AcademyAssessment;
use Academy\Models\AcademyAttendance;
use Academy\Models\AcademyChoice;
use Academy\Models\AcademyEnrolment;
use Academy\Models\AcademyGrade;
use Academy\Models\AcademyLesson;
use Academy\Models\AcademyLiveSession;
use Academy\Models\AcademyModule;
use Academy\Models\AcademyOfflineClass;
use Academy\Models\AcademyProgram;
use Academy\Models\AcademyProgress;
use Academy\Models\AcademyQuestion;
use Academy\Models\AcademyRating;
use Academy\Models\AcademySubmission;
use Academy\Services\AcademyAnalyticsService;
use App\Models\User;
use Helpers\DateTimeHelper;

/**
 * @property AcademyAnalyticsService $service
 * @property AcademyProgram          $program
 * @property AcademyModule           $module
 * @property AcademyLesson           $lesson1
 * @property AcademyLesson           $lesson2
 * @property AcademyLesson           $lesson3
 * @property User                    $learner1
 * @property User                    $learner2
 * @property AcademyEnrolment        $enrolment1
 * @property AcademyEnrolment        $enrolment2
 * @property AcademyLiveSession      $session1
 * @property AcademyLiveSession      $session2
 */
describe('Learner Analytics Service', function () {
    beforeEach(function () {
        $this->refreshDatabase();
        $this->bootPackage('Academy', null, true);
        $this->bootPackage('Audit', null, true);
        $this->bootPackage('Blish', null, true);
        $this->bootPackage('Refer', null, true);
        $this->bootPackage('Wave', null, true);

        $this->service = new AcademyAnalyticsService();

        // Base Data Setup
        $this->program = AcademyProgram::create(['title' => 'Mastering AI', 'slug' => 'mastering-ai']);
        $this->module = AcademyModule::create(['program_id' => $this->program->id, 'title' => 'Intro to Neural Networks', 'order' => 1]);
        $this->lesson1 = AcademyLesson::create(['module_id' => $this->module->id, 'title' => 'What is a Neuron?', 'slug' => 'what-is-a-neuron', 'order' => 1]);
        $this->lesson2 = AcademyLesson::create(['module_id' => $this->module->id, 'title' => 'Activation Functions', 'slug' => 'activation-functions', 'order' => 2]);
        $this->lesson3 = AcademyLesson::create(['module_id' => $this->module->id, 'title' => 'Backpropagation', 'slug' => 'backpropagation', 'order' => 3]);

        $this->learner1 = User::create(['name' => 'John Doe', 'email' => 'john@example.com', 'password' => 'password', 'gender' => 'male']);
        $this->learner2 = User::create(['name' => 'Jane Smith', 'email' => 'jane@example.com', 'password' => 'password', 'gender' => 'female']);

        $this->enrolment1 = Academy::enrol((int) $this->learner1->id, (int) $this->program->id);
        $this->enrolment2 = Academy::enrol((int) $this->learner2->id, (int) $this->program->id);

        $this->enrolment1->update(['progress_percent' => 50, 'status' => 'active']);
        $this->enrolment2->update(['progress_percent' => 10, 'status' => 'active']);

        $this->session1 = AcademyLiveSession::create([
            'program_id' => $this->program->id,
            'lesson_id' => $this->lesson1->id,
            'provider' => VideoProvider::CUSTOM,
            'meeting_url' => 'https://meet.google.com/abc',
            'starts_at' => DateTimeHelper::now()->subDays(2)->format('Y-m-d H:i:s')
        ]);

        $this->session2 = AcademyLiveSession::create([
            'program_id' => $this->program->id,
            'lesson_id' => $this->lesson2->id,
            'provider' => VideoProvider::CUSTOM,
            'meeting_url' => 'https://meet.google.com/def',
            'starts_at' => DateTimeHelper::now()->subDays(1)->format('Y-m-d H:i:s')
        ]);
    });

    test('it can fetch an individual learner attendance compliance report', function () {
        // Learner 1 fully attended session 1
        AcademyAttendance::create([
            'attendable_type' => get_class($this->session1),
            'attendable_id' => $this->session1->id,
            'enrolment_id' => $this->enrolment1->id,
            'joined_at' => DateTimeHelper::now()->format('Y-m-d H:i:s'),
            'duration' => 60 * 60 // 60 mins
        ]);

        // Learner 1 partially attended session 2
        AcademyAttendance::create([
            'attendable_type' => get_class($this->session2),
            'attendable_id' => $this->session2->id,
            'enrolment_id' => $this->enrolment1->id,
            'joined_at' => DateTimeHelper::now()->format('Y-m-d H:i:s'),
            'duration' => 20 * 60 // 20 mins
        ]);

        // Require 45 mins
        $report = $this->service->getLearnerAttendanceReport((int) $this->enrolment1->id, AcademyLiveSession::class, 45);

        expect($report['summary']['total_sessions'])->toBe(2);
        expect($report['summary']['attended_sessions'])->toBe(2);
        expect($report['summary']['compliant_sessions'])->toBe(1);
        expect($report['summary']['compliance_rate'])->toBe(50); // 1 out of 2 is compliant
    });

    test('it can generate a robust grid of all program learners', function () {
        $report = $this->service->getProgramLearnersReport((int) $this->program->id);

        expect(count($report))->toBe(2);

        $johnFilter = array_values(array_filter($report, fn ($r) => $r['user_id'] === $this->learner1->id));
        $janeFilter = array_values(array_filter($report, fn ($r) => $r['user_id'] === $this->learner2->id));

        $john = $johnFilter[0] ?? null;
        $jane = $janeFilter[0] ?? null;

        expect($john)->not->toBeNull();
        expect($john['refid'])->not->toBeNull();
        expect($john['name'])->toBe('John Doe');
        expect($john['progress_percent'])->toBe(50);

        expect($jane)->not->toBeNull();
        expect($jane['refid'])->not->toBeNull();
        expect($jane['name'])->toBe('Jane Smith');
        expect($jane['progress_percent'])->toBe(10);
    });

    test('it can map out common program bottlenecks', function () {
        // Mocking user 1 stuck at lesson 1
        AcademyProgress::create([
            'enrolment_id' => $this->enrolment1->id,
            'lesson_id' => $this->lesson1->id,
            'completed_at' => DateTimeHelper::now()->format('Y-m-d H:i:s')
        ]);

        // Mocking user 2 stuck at lesson 1
        AcademyProgress::create([
            'enrolment_id' => $this->enrolment2->id,
            'lesson_id' => $this->lesson1->id,
            'completed_at' => DateTimeHelper::now()->format('Y-m-d H:i:s')
        ]);

        $bottlenecks = $this->service->getProgramBottlenecks((int) $this->program->id);

        expect(count($bottlenecks))->toBe(1);
        expect($bottlenecks[0]['lesson_id'])->toBe((int) $this->lesson1->id);
        expect($bottlenecks[0]['stalled_count'])->toBe(2);
    });

    test('it identifies severely at-risk learners', function () {
        // Move enrolment back 8 days so they missed the 7-day grace period for new students
        $this->enrolment2->update(['enrolled_at' => DateTimeHelper::now()->subDays(20)->format('Y-m-d H:i:s')]);

        // Mock last activity as 15 days ago
        AcademyProgress::create([
            'enrolment_id' => $this->enrolment2->id,
            'lesson_id' => $this->lesson1->id,
            'completed_at' => DateTimeHelper::now()->subDays(15)->format('Y-m-d H:i:s')
        ]);

        $atRisk = $this->service->getAtRiskLearners((int) $this->program->id, 14, 20);

        expect(count($atRisk))->toBe(1);
        expect($atRisk[0]['name'])->toBe('Jane Smith');
        expect($atRisk[0]['days_inactive'])->toBeGreaterThanOrEqual(15);
        expect($atRisk[0]['progress_percent'])->toBe(10); // Less than the 20 max threshold
    });

    test('it can fetch all attendance records for a learner', function () {
        // Add a live session attendance
        AcademyAttendance::create([
            'attendable_type' => AcademyLiveSession::class,
            'attendable_id' => $this->session1->id,
            'enrolment_id' => $this->enrolment1->id,
            'joined_at' => DateTimeHelper::now()->format('Y-m-d H:i:s'),
            'duration' => 60 * 60
        ]);

        // Add a general/other attendance (Offline Class)
        $offlineClass = AcademyOfflineClass::create([
            'lesson_id' => $this->lesson3->id,
            'location' => 'Lagos Office',
            'starts_at' => DateTimeHelper::now()->format('Y-m-d H:i:s'),
            'status' => 'scheduled'
        ]);

        AcademyAttendance::create([
            'attendable_type' => AcademyOfflineClass::class,
            'attendable_id' => $offlineClass->id,
            'enrolment_id' => $this->enrolment1->id,
            'joined_at' => DateTimeHelper::now()->format('Y-m-d H:i:s'),
            'duration' => 30 * 60
        ]);

        $report = $this->service->getLearnerAttendanceReport((int) $this->enrolment1->id, attendableType: null);

        expect($report['summary']['total_count'])->toBe(2);
        expect($report['summary']['total_duration_minutes'])->toBe(90.0);
        expect($report['summary']['filtered_by'])->toBe('all');
        expect(count($report['records']))->toBe(2);
    });

    test('it can filter attendance records by specific type', function () {
        // Add a live session attendance
        AcademyAttendance::create([
            'attendable_type' => AcademyLiveSession::class,
            'attendable_id' => $this->session1->id,
            'enrolment_id' => $this->enrolment1->id,
            'joined_at' => DateTimeHelper::now()->format('Y-m-d H:i:s'),
            'duration' => 60 * 60
        ]);

        // Add a general/other attendance (Offline Class)
        $offlineClass = AcademyOfflineClass::create([
            'lesson_id' => $this->lesson3->id,
            'location' => 'Lagos Office',
            'starts_at' => DateTimeHelper::now()->format('Y-m-d H:i:s'),
            'status' => 'scheduled'
        ]);

        AcademyAttendance::create([
            'attendable_type' => AcademyOfflineClass::class,
            'attendable_id' => $offlineClass->id,
            'enrolment_id' => $this->enrolment1->id,
            'joined_at' => DateTimeHelper::now()->format('Y-m-d H:i:s'),
            'duration' => 30 * 60
        ]);

        $report = $this->service->getLearnerAttendanceReport((int) $this->enrolment1->id, AcademyOfflineClass::class);

        expect($report['summary']['total_count'])->toBe(1);
        expect($report['summary']['filtered_by'])->toBe(AcademyOfflineClass::class);
        expect((int) $report['records'][0]['target_id'])->toBe((int) $offlineClass->id);
    });

    test('it can analyze assessment question difficulty', function () {
        $assessment = AcademyAssessment::create([
            'lesson_id' => $this->lesson1->id,
            'title' => 'Quiz 1',
            'pass_mark' => 50
        ]);

        $q1 = AcademyQuestion::create([
            'assessment_id' => $assessment->id,
            'text' => 'Question 1',
            'type' => 'choice'
        ]);

        $c1 = AcademyChoice::create(['question_id' => $q1->id, 'text' => 'Correct', 'is_correct' => true]);
        $c2 = AcademyChoice::create(['question_id' => $q1->id, 'text' => 'Wrong', 'is_correct' => false]);

        // Submission 1: Correct
        $s1 = AcademySubmission::create(['assessment_id' => $assessment->id, 'enrolment_id' => $this->enrolment1->id, 'status' => 'graded']);
        AcademyAnswer::create(['submission_id' => $s1->id, 'question_id' => $q1->id, 'choice_id' => $c1->id]);

        // Submission 2: Wrong
        $s2 = AcademySubmission::create(['assessment_id' => $assessment->id, 'enrolment_id' => $this->enrolment2->id, 'status' => 'graded']);
        AcademyAnswer::create(['submission_id' => $s2->id, 'question_id' => $q1->id, 'choice_id' => $c2->id]);

        $metrics = $this->service->getAssessmentQuestionMetrics((int) $assessment->id);

        expect(count($metrics))->toBe(1);
        expect($metrics[0]['question_id'])->toBe((int) $q1->id);
        expect($metrics[0]['total_attempts'])->toBe(2);
        expect($metrics[0]['failure_rate_percent'])->toBe(50.0);
    });

    test('it calculates a unified learner engagement score', function () {
        // 1. Progress: 50%
        $this->enrolment1->update(['progress_percent' => 50]);

        // 2. Assessment: 80% avg
        $assessment = AcademyAssessment::create(['lesson_id' => $this->lesson1->id, 'title' => 'Quiz', 'pass_mark' => 50]);
        $submission = AcademySubmission::create(['assessment_id' => $assessment->id, 'enrolment_id' => $this->enrolment1->id, 'status' => 'graded']);
        AcademyGrade::create(['submission_id' => $submission->id, 'percent_score' => 80, 'grader_id' => 1]);

        // 3. Attendance: 100% (2 sessions, both attended)
        AcademyAttendance::create([
            'attendable_type' => AcademyLiveSession::class,
            'attendable_id' => $this->session1->id,
            'enrolment_id' => $this->enrolment1->id,
            'joined_at' => DateTimeHelper::now()->format('Y-m-d H:i:s'),
            'duration' => 60 * 60
        ]);

        AcademyAttendance::create([
            'attendable_type' => AcademyLiveSession::class,
            'attendable_id' => $this->session2->id,
            'enrolment_id' => $this->enrolment1->id,
            'joined_at' => DateTimeHelper::now()->format('Y-m-d H:i:s'),
            'duration' => 60 * 60
        ]);

        $score = $this->service->getLearnerEngagementScore((int) $this->enrolment1->id);

        // (50 * 0.4) + (80 * 0.4) + (100 * 0.2) = 20 + 32 + 20 = 72
        expect($score)->toBe(72);
    });

    it('can generate a program leaderboard', function () {
        $this->enrolment2->update(['status' => EnrolmentStatus::ACTIVE, 'progress_percent' => 90]);
        $this->enrolment1->update(['status' => EnrolmentStatus::ACTIVE, 'progress_percent' => 80]);

        $leaderboard = $this->service->getLeaderboard((int) $this->program->id);

        expect($leaderboard)->toHaveCount(2);
        expect($leaderboard[0]['refid'])->toBe($this->learner2->refid);
        expect($leaderboard[0]['progress_percent'])->toBe(90);
        expect($leaderboard[1]['refid'])->toBe($this->learner1->refid);
        expect($leaderboard[1]['progress_percent'])->toBe(80);
    });

    it('can generate a learner transcript with refid', function () {
        $reportService = new \Academy\Services\ReportingService();
        $transcript = $reportService->getTranscript((int) $this->enrolment1->id);

        expect($transcript['learner_refid'])->toBe($this->learner1->refid);
        expect($transcript['learner_name'])->toBe('John Doe');
    });

    it('can generate a rating summary', function () {
        AcademyRating::create(['user_id' => $this->learner1->id, 'program_id' => $this->program->id, 'rating' => 5]);
        AcademyRating::create(['user_id' => $this->learner2->id, 'program_id' => $this->program->id, 'rating' => 4]);

        $summary = $this->service->getRatingSummary((int) $this->program->id);

        expect($summary['average'])->toBe(4.5);
        expect($summary['total'])->toBe(2);
        expect($summary['breakdown'][5])->toBe(1);
        expect($summary['breakdown'][4])->toBe(1);
    });
})->covers(AcademyAnalyticsService::class);
