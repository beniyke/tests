<?php

declare(strict_types=1);

namespace Tests\Packages\Academy\Feature;

use Academy\Academy;
use Academy\Models\AcademyLesson;
use Academy\Models\AcademyModule;
use Academy\Models\AcademyProgram;
use App\Models\User;
use Core\Event;

describe('Advanced Academy Features', function () {
    beforeEach(function () {
        $this->refreshDatabase();
        Event::clearListeners();
        $this->bootPackage('Academy', null, true);
        $this->bootPackage('Audit', null, true);

        // Disable optional integrations to avoid missing table errors
        config()->set('academy.integrations.blish', false);
        config()->set('academy.integrations.wave', false);
        config()->set('academy.integrations.wallet', false);
        config()->set('academy.integrations.hub', false);
        config()->set('academy.integrations.pay', false);
        config()->set('academy.integrations.rank', false);
        config()->set('academy.integrations.refer', false);
        config()->set('academy.integrations.link', false);
        config()->set('academy.integrations.verify', false);

        // Create a few users
        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password', 'gender' => 'male']);
        $this->student = User::create(['name' => 'Student', 'email' => 'student@example.com', 'password' => 'password', 'gender' => 'male']);
        $this->instructor = User::create(['name' => 'Instructor', 'email' => 'instructor@example.com', 'password' => 'password', 'gender' => 'male']);
    });

    test('fluent program builder creates program with instructors and metadata', function () {
        $program = Academy::program()
            ->titled('Advanced Architecture')
            ->withInstructors([(int) $this->instructor->id])
            ->withMetadata('topic', 'Laravel')
            ->create();

        expect($program)->toBeInstanceOf(AcademyProgram::class);
        expect($program->title)->toBe('Advanced Architecture');
        expect($program->metadata['topic'])->toBe('Laravel');

        // Check instructor assignment
        $isInstructor = Academy::programs()->canManage((int) $this->instructor->id, (int) $program->id);
        expect($isInstructor)->toBeTrue();
    });

    test('smart search v2 finds items across associations', function () {
        $program = Academy::program()->titled('Searchable Program')->create();
        Academy::programs()->publish($program);

        $module = AcademyModule::create([
            'program_id' => $program->id,
            'title' => 'Deep Module',
            'metadata' => ['drip_delay' => 0]
        ]);

        $lesson = AcademyLesson::create([
            'module_id' => $module->id,
            'title' => 'Hidden Secret Lesson',
            'slug' => 'secret',
            'content' => 'The magic word is Antigravity'
        ]);

        // Search by lesson content
        $results = Academy::programs()->search('Antigravity');
        expect($results)->not->toBeEmpty();
        expect($results[0]['title'])->toBe('Searchable Program');
    });

    test('admission id is generated upon enrolment', function () {
        $program = Academy::program()->titled('Admission Test')->create();
        $enrolment = Academy::enrol((int) $this->student->id, (int) $program->id);

        expect($enrolment->admission_id)->not->toBeNull();
        expect($enrolment->admission_id)->toStartWith('ADM-');
    });

    test('deep fetching returns full data tree', function () {
        $program = Academy::program()->titled('Tree Program')->create();
        $module = AcademyModule::create(['program_id' => $program->id, 'title' => 'Tree Module']);
        $lesson = AcademyLesson::create(['module_id' => $module->id, 'title' => 'Tree Lesson', 'slug' => 'tree']);

        $details = Academy::getProgramDetails((int) $program->id);

        expect($details)->toHaveKey('program');
        expect($details)->toHaveKey('modules');
        expect($details['modules'])->not->toBeEmpty();
        expect($details['modules'][0]->title)->toBe('Tree Module');
    });

    test('granular permissions and instructor overrides', function () {
        $program = Academy::program()->titled('Permission Test')->create();
        $module = AcademyModule::create([
            'program_id' => $program->id,
            'title' => 'Gated Module',
            'metadata' => ['drip_delay' => 7] // 7 days drip
        ]);
        $lesson = AcademyLesson::create(['module_id' => $module->id, 'title' => 'Gated Lesson', 'slug' => 'gated']);

        // Assign instructor
        Academy::programs()->addMember($program, (int) $this->instructor->id, 'instructor');

        // Instructor should have access immediately
        $canAccessInstructor = Academy::programs()->canAccess((int) $this->instructor->id, (int) $lesson->id);
        expect($canAccessInstructor)->toBeTrue();

        // Student without enrolment should not have access
        $canAccessStudent = Academy::programs()->canAccess((int) $this->student->id, (int) $lesson->id);
        expect($canAccessStudent)->toBeFalse();

        // Enrolled student should still be blocked by drip
        $enrolment = Academy::enrol((int) $this->student->id, (int) $program->id);
        Academy::enrolments()->activate($enrolment);

        $canAccessEnrolled = Academy::programs()->canAccess((int) $this->student->id, (int) $lesson->id);
        expect($canAccessEnrolled)->toBeFalse(); // Still blocked by 7 days drip
    });

    test('specific learner restriction via metadata', function () {
        $program = Academy::program()->titled('Restricted Program')->create();
        $module = AcademyModule::create(['program_id' => $program->id, 'title' => 'Module']);
        $lesson = AcademyLesson::create([
            'module_id' => $module->id,
            'title' => 'Restricted Lesson',
            'slug' => 'restricted',
            'metadata' => ['allowed_user_ids' => [(int) $this->student->id]]
        ]);

        $enrolment = Academy::enrol((int) $this->student->id, (int) $program->id);
        Academy::enrolments()->activate($enrolment);

        $otherStudent = User::create(['name' => 'Other', 'email' => 'other@example.com', 'password' => 'password', 'gender' => 'male']);
        $enrolmentOther = Academy::enrol((int) $otherStudent->id, (int) $program->id);
        Academy::enrolments()->activate($enrolmentOther);

        expect(Academy::programs()->canAccess((int) $this->student->id, (int) $lesson->id))->toBeTrue();
        expect(Academy::programs()->canAccess((int) $otherStudent->id, (int) $lesson->id))->toBeFalse();
    });
});
