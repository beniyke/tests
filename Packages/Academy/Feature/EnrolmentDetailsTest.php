<?php

declare(strict_types=1);

namespace Tests\Packages\Academy\Feature;

use Academy\Academy;
use App\Models\User;

describe('Enrolment Details', function () {
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
    });

    test('AcademyManager can retrieve enrolment details by ID', function () {
        $program = Academy::program()
            ->titled('Test Program')
            ->create();

        $enrolment = Academy::enrol(1, (int) $program->id);

        $details = Academy::getEnrolmentDetails($enrolment->id);

        expect($details)->toBeArray();
        expect($details)->toHaveKeys(['enrolment', 'program', 'user', 'progress', 'notes', 'certificate', 'instalments', 'submissions']);
        expect($details['enrolment']->id)->toBe($enrolment->id);
        expect($details['program']->id)->toBe($program->id);
        expect($details['user']->id)->toBe(1);
    });

    test('AcademyManager can retrieve enrolment details by refid', function () {
        $program = Academy::program()
            ->titled('Refid Program')
            ->create();

        $enrolment = Academy::enrol(1, (int) $program->id);

        $details = Academy::getEnrolmentDetails($enrolment->refid);

        expect($details)->toBeArray();
        expect($details['enrolment']->refid)->toBe($enrolment->refid);
    });

    test('throws exception for non-existent enrolment', function () {
        expect(fn () => Academy::getEnrolmentDetails(999))
            ->toThrow(\Academy\Exceptions\AcademyException::class, 'Enrolment not found.');
    });
});
