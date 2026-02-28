<?php

declare(strict_types=1);

namespace Tests\Packages\Academy\Integration;

use Academy\Academy;
use Academy\Enums\VideoProvider;
use Academy\Models\AcademyAttendance;
use Academy\Models\AcademyLesson;
use Academy\Models\AcademyLiveSession;
use Academy\Models\AcademyModule;
use Academy\Models\AcademyProgram;
use Academy\Services\AcademyAnalyticsService;
use App\Models\User;
use Helpers\DateTimeHelper;

describe('Attendance Analytics Service', function () {
    beforeEach(function () {
        $this->refreshDatabase();
        $this->bootPackage('Academy', null, true);
        $this->bootPackage('Audit', null, true);
        $this->bootPackage('Blish', null, true);
        $this->bootPackage('Refer', null, true);
        $this->bootPackage('Wave', null, true);

        $this->instructor = User::create(['name' => 'Instructor', 'email' => 'instructor@test.com', 'password' => 'password', 'gender' => 'male']);

        $this->program = AcademyProgram::create(['title' => 'Test Program', 'slug' => 'test-program']);
        $this->module = AcademyModule::create(['program_id' => $this->program->id, 'title' => 'Mod 1', 'order' => 1]);
        $this->lesson = AcademyLesson::create(['module_id' => $this->module->id, 'title' => 'L1', 'slug' => 'l1', 'order' => 1]);

        $this->learner1 = User::create(['name' => 'Alice', 'email' => 'alice@test.com', 'password' => 'password', 'gender' => 'female']);
        $this->learner2 = User::create(['name' => 'Bob', 'email' => 'bob@test.com', 'password' => 'password', 'gender' => 'male']);
        $this->learner3 = User::create(['name' => 'Charlie', 'email' => 'charlie@test.com', 'password' => 'password', 'gender' => 'male']);

        $this->enrolment1 = Academy::enrol($this->learner1->id, (int) $this->program->id);
        $this->enrolment2 = Academy::enrol($this->learner2->id, (int) $this->program->id);
        $this->enrolment3 = Academy::enrol($this->learner3->id, (int) $this->program->id);

        $this->session = AcademyLiveSession::create([
            'program_id' => $this->program->id,
            'lesson_id' => $this->lesson->id,
            'provider' => VideoProvider::CUSTOM,
            'meeting_url' => 'https://zoom.us/j/12345',
            'starts_at' => DateTimeHelper::now()->format('Y-m-d H:i:s')
        ]);

        $this->service = new AcademyAnalyticsService();
    });

    test('it can get active attendees', function () {
        Academy::attendance()->record($this->session, (int) $this->enrolment1->id);
        Academy::attendance()->record($this->session, (int) $this->enrolment2->id);
        Academy::attendance()->recordLeave($this->session, (int) $this->enrolment2->id);

        $active = $this->service->getActiveAttendees($this->session);

        expect($active)->toHaveCount(1);
        expect($active[0]->name)->toBe('Alice');
    });

    test('it can determine attendance compliance', function () {
        $baseTime = DateTimeHelper::now()->subMinutes(60);

        AcademyAttendance::create([
            'attendable_type' => get_class($this->session),
            'attendable_id' => $this->session->id,
            'enrolment_id' => $this->enrolment1->id,
            'joined_at' => $baseTime->format('Y-m-d H:i:s'),
            'left_at' => $baseTime->addMinutes(50)->format('Y-m-d H:i:s'),
            'duration' => 50 * 60
        ]);

        AcademyAttendance::create([
            'attendable_type' => get_class($this->session),
            'attendable_id' => $this->session->id,
            'enrolment_id' => $this->enrolment2->id,
            'joined_at' => $baseTime->subMinutes(50)->format('Y-m-d H:i:s'),
            'left_at' => $baseTime->addMinutes(20)->format('Y-m-d H:i:s'),
            'duration' => 20 * 60
        ]);

        $compliance = $this->service->getAttendanceCompliance($this->session, 45);

        expect($compliance['summary']['total_compliant'])->toBe(1);
        expect($compliance['summary']['total_non_compliant'])->toBe(1);
        expect($compliance['compliant'][0]['name'])->toBe('Alice');
        expect($compliance['non_compliant'][0]['name'])->toBe('Bob');
    });

    test('it can generate a drop-off trend chart', function () {
        $sessionStart = DateTimeHelper::now();

        AcademyAttendance::create([
            'attendable_type' => get_class($this->session),
            'attendable_id' => $this->session->id,
            'enrolment_id' => $this->enrolment1->id,
            'joined_at' => clone $sessionStart,
            'left_at' => (clone $sessionStart)->addMinutes(3),
            'duration' => 180
        ]);

        AcademyAttendance::create([
            'attendable_type' => get_class($this->session),
            'attendable_id' => $this->session->id,
            'enrolment_id' => $this->enrolment2->id,
            'joined_at' => clone $sessionStart,
            'left_at' => (clone $sessionStart)->addMinutes(8),
            'duration' => 480
        ]);

        AcademyAttendance::create([
            'attendable_type' => get_class($this->session),
            'attendable_id' => $this->session->id,
            'enrolment_id' => $this->enrolment3->id,
            'joined_at' => clone $sessionStart,
            'left_at' => null,
            'duration' => 0
        ]);

        $trend = $this->service->getDropOffTrend($this->session, 5);

        expect($trend['labels'])->toHaveCount(2);
        expect($trend['labels'][0])->toBe('0-5 min');
        expect($trend['labels'][1])->toBe('5-10 min');
        expect($trend['values'][0])->toBe(1);
        expect($trend['values'][1])->toBe(1);
        expect($trend['summary']['active_users'])->toBe(1);
        expect($trend['summary']['total_dropoffs'])->toBe(2);
    });
})->covers(AcademyAnalyticsService::class);
