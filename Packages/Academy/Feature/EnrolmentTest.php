<?php

declare(strict_types=1);

namespace Tests\Packages\Academy\Feature;

use Academy\Academy;
use Academy\Enums\EnrolmentStatus;
use Academy\Models\AcademyEnrolment;
use Academy\Models\AcademyProgram;
use App\Models\User;

describe('Enrolment Process', function () {
    beforeEach(function () {
        $this->refreshDatabase();
        $this->bootPackage('Academy', null, true);
        $this->bootPackage('Audit', null, true);

        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'gender' => 'male',
        ]);

        // Disable optional integrations
        config()->set('academy.integrations.blish', false);
        config()->set('academy.integrations.wave', false);
        config()->set('academy.integrations.wallet', false);
    });

    test('user can enrol in a program via facade', function () {
        $program = Academy::program()
            ->titled('Pest Feature Program')
            ->create();

        $userId = 1;
        $enrolment = Academy::enrol($userId, (int) $program->id);

        expect($enrolment)->toBeInstanceOf(AcademyEnrolment::class);
        expect((int) $enrolment->user_id)->toBe((int) $userId);
        expect((int) $enrolment->program_id)->toBe((int) $program->id);
        expect($enrolment->status)->toBe(EnrolmentStatus::PENDING);
    });

    test('enrolment can be activated by enrolment manager', function () {
        $program = AcademyProgram::create(['title' => 'Test', 'slug' => 'test']);
        $enrolment = Academy::enrol(1, (int) $program->id);

        Academy::enrolments()->activate($enrolment);

        expect($enrolment->fresh()->status)->toBe(EnrolmentStatus::ACTIVE);
    });
});
