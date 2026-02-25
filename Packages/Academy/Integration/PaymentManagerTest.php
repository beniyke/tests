<?php

declare(strict_types=1);

namespace Tests\Packages\Academy\Integration;

use Academy\Enums\PaymentPlanType;
use Academy\Enums\PaymentStatus;
use Academy\Models\AcademyEnrolment;
use Academy\Models\AcademyInstalment;
use Academy\Models\AcademyPaymentPlan;
use Academy\Models\AcademyProgram;
use Academy\Services\PaymentManagerService;

describe('Payment Instalments', function () {
    beforeEach(function () {
        $this->refreshDatabase();
        $this->bootPackage('Academy', null, true);
        $this->bootPackage('Audit', null, true);
    });

    test('it initializes instalments for a paid plan', function () {
        $program = AcademyProgram::create(['title' => 'Test', 'slug' => 'test']);
        $plan = AcademyPaymentPlan::create([
            'program_id' => $program->id,
            'name' => '3 Months',
            'type' => PaymentPlanType::INSTALMENT,
            'price' => 30000,
            'instalment_count' => 3,
            'instalment_interval' => 30,
        ]);

        $enrolment = AcademyEnrolment::create([
            'user_id' => 1,
            'program_id' => $program->id,
            'payment_plan_id' => $plan->id,
        ]);

        $service = new PaymentManagerService();
        $service->initializeInstalments($enrolment);

        expect(AcademyInstalment::where('enrolment_id', $enrolment->id)->count())->toBe(3);

        $first = AcademyInstalment::first();
        expect($first->amount)->toBe(10000);
        expect($first->status)->toBe(PaymentStatus::PENDING);
    });

    test('it processes payment successfully and updates status', function () {
        $program = AcademyProgram::create(['title' => 'Test', 'slug' => 'test']);
        $enrolment = AcademyEnrolment::create(['user_id' => 1, 'program_id' => $program->id]);
        $instalment = AcademyInstalment::create([
            'enrolment_id' => $enrolment->id,
            'amount' => 5000,
            'sequence' => 1,
            'status' => PaymentStatus::PENDING,
        ]);

        $service = new PaymentManagerService();
        $result = $service->processPayment($enrolment, 'REF-TEST-123', 5000);

        expect($result)->toBeTrue();
        expect($instalment->fresh()->status)->toBe(PaymentStatus::PAID);
        expect($instalment->fresh()->payment_reference)->toBe('REF-TEST-123');
    });
})->covers(PaymentManagerService::class);
