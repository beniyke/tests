<?php

declare(strict_types=1);

use Academy\Academy;
use Academy\Models\AcademyProgram;
use App\Models\User;
use Helpers\DateTimeHelper;

describe('Reporting & Refinements', function () {
    beforeEach(function () {
        $this->refreshDatabase();
        $this->bootPackage('Academy', null, true);
        $this->bootPackage('Audit', null, true);

        $this->user = User::create([
            'name' => 'Test Student',
            'email' => 'student@example.com',
            'password' => 'password',
            'gender' => 'male',
        ]);

        $this->program = AcademyProgram::create([
            'title' => 'Reporting Refinement',
            'slug' => 'reporting-refinement',
            'status' => 'published'
        ]);

        // Disable optional integrations
        config()->set('academy.integrations.wave', false);
        config()->set('academy.integrations.blish', false);
    });

    test('it can generate transcript', function () {
        $enrolment = Academy::enrol($this->user->id, $this->program->id);

        $transcript = Academy::reports()->getTranscript($enrolment->id);

        expect($transcript['program_title'])->toBe('Reporting Refinement');
        expect($transcript['assessments'])->toBeArray();
    });

    test('it can generate detailed progress report', function () {
        $enrolment = Academy::enrol($this->user->id, $this->program->id);

        $report = Academy::reports()->getProgressReport($enrolment->id);

        expect($report['percentage'])->toBe(0);
        expect($report['lessons'])->toBeArray();
    });

    test('it can extend enrolment duration', function () {
        $enrolment = Academy::enrol($this->user->id, $this->program->id);

        $oldExpiry = DateTimeHelper::now()->addDays(30);
        $enrolment->update(['expires_at' => $oldExpiry]);

        $success = Academy::enrolments()->extend($enrolment, 10);

        expect($success)->toBeTrue();
        expect($enrolment->fresh()->expires_at->format('Y-m-d'))
            ->toBe($oldExpiry->addDays(10)->format('Y-m-d'));
    });
});
