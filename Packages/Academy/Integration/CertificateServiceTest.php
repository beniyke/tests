<?php

declare(strict_types=1);

namespace Tests\Packages\Academy\Integration;

use Academy\Enums\CertificateStatus;
use Academy\Models\AcademyCertificate;
use Academy\Models\AcademyEnrolment;
use Academy\Models\AcademyProgram;
use Academy\Services\CertificateService;

describe('Certificate Service', function () {
    beforeEach(function () {
        $this->refreshDatabase();
        $this->bootPackage('Academy', null, true);
        $this->bootPackage('Audit', null, true);
    });

    test('it issues a certificate for an enrolment', function () {
        $program = AcademyProgram::create(['title' => 'Test', 'slug' => 'test']);
        $enrolment = AcademyEnrolment::create(['user_id' => 1, 'program_id' => $program->id]);

        $service = new CertificateService();
        $certificate = $service->issue($enrolment);

        expect($certificate)->toBeInstanceOf(AcademyCertificate::class);
        expect($certificate->status)->toBe(CertificateStatus::ISSUED);
        expect($certificate->certificate_number)->not->toBeEmpty();
        expect($certificate->issued_at)->not->toBeNull();
    });
})->covers(CertificateService::class);
