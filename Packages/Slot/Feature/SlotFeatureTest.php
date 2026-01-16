<?php

declare(strict_types=1);

namespace Tests\Packages\Slot\Feature;

use Core\Ioc\Container;
use Database\Schema\Schema;
use Helpers\DateTimeHelper;
use RuntimeException;
use Slot\Enums\BookingStatus;
use Slot\Enums\ScheduleType;
use Slot\Interfaces\SlotServiceInterface;
use Slot\Models\SlotBooking;
use Slot\Models\SlotSchedule;
use Slot\Period;
use Slot\Providers\SlotServiceProvider;
use Testing\Support\DatabaseTestHelper;
use Tests\Packages\Slot\Helpers\Doctor;
use Tests\Packages\Slot\Helpers\Patient;

beforeEach(function () {
    // Register SlotServiceProvider
    $container = Container::getInstance();
    $provider = new SlotServiceProvider($container);
    $provider->register();
    $provider->boot();

    // Setup Test Environment (Schema + Migrations)
    // This runs App migrations (for User table) and Slot package migrations
    DatabaseTestHelper::setupTestEnvironment(['Slot'], true);
});

afterEach(function () {
    DatabaseTestHelper::cleanupTables(['slot_bookings', 'slot_schedules', 'user']);
});

describe('Slot Feature Tests', function () {
    test('can add availability to a model using HasSlots trait', function () {
        $doctor = Doctor::create([
            'name' => 'Dr. Smith',
            'email' => 'smith@test.com',
            'password' => 'password',
            'gender' => 'male',
            'refid' => 'DOC001',
            'status' => 'active',
        ]);

        $period = new Period(
            DateTimeHelper::parse('2025-01-10 09:00:00'),
            DateTimeHelper::parse('2025-01-10 17:00:00')
        );

        $schedule = $doctor->availability($period, ['title' => 'Morning Shift']);

        expect($schedule)->toBeInstanceOf(SlotSchedule::class);
        expect($schedule->type)->toBe(ScheduleType::Availability);
        expect($schedule->title)->toBe('Morning Shift');
        expect($schedule->schedulable_id)->toBe($doctor->id);
    });

    test('can create appointment schedule', function () {
        $doctor = Doctor::create([
            'name' => 'Dr. Jones',
            'email' => 'jones@test.com',
            'password' => 'password',
            'gender' => 'female',
            'refid' => 'DOC002',
            'status' => 'active',
        ]);

        $period = new Period(
            DateTimeHelper::parse('2025-01-10 10:00:00'),
            DateTimeHelper::parse('2025-01-10 10:30:00')
        );

        $appointment = $doctor->appointment($period, ['title' => 'Patient Consultation']);

        expect($appointment->type)->toBe(ScheduleType::Appointment);
        expect($appointment->title)->toBe('Patient Consultation');
    });

    test('can create blocked period', function () {
        $doctor = Doctor::create([
            'name' => 'Dr. Brown',
            'email' => 'brown@test.com',
            'password' => 'password',
            'gender' => 'male',
            'refid' => 'DOC003',
            'status' => 'active',
        ]);

        $period = new Period(
            DateTimeHelper::parse('2025-01-10 12:00:00'),
            DateTimeHelper::parse('2025-01-10 13:00:00')
        );

        $blocked = $doctor->blocked($period, ['title' => 'Lunch Break']);

        expect($blocked->type)->toBe(ScheduleType::Blocked);
    });

    test('detects conflicts when creating overlapping appointments', function () {
        $doctor = Doctor::create([
            'name' => 'Dr. Wilson',
            'email' => 'wilson@test.com',
            'password' => 'password',
            'gender' => 'female',
            'refid' => 'DOC004',
            'status' => 'active',
        ]);

        // Create first appointment
        $period1 = new Period(
            DateTimeHelper::parse('2025-01-10 10:00:00'),
            DateTimeHelper::parse('2025-01-10 11:00:00')
        );
        $doctor->appointment($period1);

        // Try to create overlapping appointment
        $period2 = new Period(
            DateTimeHelper::parse('2025-01-10 10:30:00'),
            DateTimeHelper::parse('2025-01-10 11:30:00')
        );

        expect(fn () => $doctor->appointment($period2))->toThrow(RuntimeException::class);
    });

    test('generates available time slots', function () {
        $doctor = Doctor::create([
            'name' => 'Dr. Taylor',
            'email' => 'taylor@test.com',
            'password' => 'password',
            'gender' => 'male',
            'refid' => 'DOC005',
            'status' => 'active',
        ]);

        // Add availability
        $availability = new Period(
            DateTimeHelper::parse('2025-01-10 09:00:00'),
            DateTimeHelper::parse('2025-01-10 12:00:00')
        );
        $doctor->availability($availability);

        // Block one hour
        $blocked = new Period(
            DateTimeHelper::parse('2025-01-10 10:00:00'),
            DateTimeHelper::parse('2025-01-10 11:00:00')
        );
        $doctor->blocked($blocked);

        // Get available 30-minute slots
        $range = new Period(
            DateTimeHelper::parse('2025-01-10 09:00:00'),
            DateTimeHelper::parse('2025-01-10 12:00:00')
        );
        $slots = $doctor->getAvailableSlots($range, 30);

        // Should have 2 slots before blocked period and 2 after
        expect($slots)->toBeArray();
        expect(count($slots))->toBeGreaterThan(0);
    });

    test('can create booking for a schedule', function () {
        $doctor = Doctor::create([
            'name' => 'Dr. Davis',
            'email' => 'davis@test.com',
            'password' => 'password',
            'gender' => 'female',
            'refid' => 'DOC006',
            'status' => 'active',
        ]);

        $patient = Patient::create([
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'password' => 'password',
            'gender' => 'male',
            'refid' => 'PAT001',
            'status' => 'active',
        ]);

        // Create availability
        $availabilityPeriod = new Period(
            DateTimeHelper::parse('2025-01-10 09:00:00'),
            DateTimeHelper::parse('2025-01-10 17:00:00')
        );
        $schedule = $doctor->availability($availabilityPeriod);

        // Create booking
        $bookingPeriod = new Period(
            DateTimeHelper::parse('2025-01-10 10:00:00'),
            DateTimeHelper::parse('2025-01-10 10:30:00')
        );

        $slotService = resolve(SlotServiceInterface::class);
        $booking = $slotService->createBooking($schedule, $patient, $bookingPeriod);

        expect($booking)->toBeInstanceOf(SlotBooking::class);
        expect($booking->schedule_id)->toBe($schedule->id);
        expect($booking->bookable_id)->toBe($patient->id);
        expect($booking->status)->toBe(BookingStatus::Pending);
    });

    test('can confirm and cancel bookings', function () {
        $doctor = Doctor::create([
            'name' => 'Dr. Miller',
            'email' => 'miller@test.com',
            'password' => 'password',
            'gender' => 'male',
            'refid' => 'DOC007',
            'status' => 'active',
        ]);

        $patient = Patient::create([
            'name' => 'Jane Doe',
            'email' => 'jane@test.com',
            'password' => 'password',
            'gender' => 'female',
            'refid' => 'PAT002',
            'status' => 'active',
        ]);

        $availabilityPeriod = new Period(
            DateTimeHelper::parse('2025-01-10 09:00:00'),
            DateTimeHelper::parse('2025-01-10 17:00:00')
        );
        $schedule = $doctor->availability($availabilityPeriod);

        $bookingPeriod = new Period(
            DateTimeHelper::parse('2025-01-10 14:00:00'),
            DateTimeHelper::parse('2025-01-10 14:30:00')
        );

        $slotService = resolve(SlotServiceInterface::class);
        $booking = $slotService->createBooking($schedule, $patient, $bookingPeriod);

        // Confirm booking
        $booking->confirm();
        expect($booking->status)->toBe(BookingStatus::Confirmed);

        // Cancel booking
        $booking->cancel();
        expect($booking->status)->toBe(BookingStatus::Cancelled);
    });

    test('supports recurring schedules', function () {
        $doctor = Doctor::create([
            'name' => 'Dr. Anderson',
            'email' => 'anderson@test.com',
            'password' => 'password',
            'gender' => 'female',
            'refid' => 'DOC008',
            'status' => 'active',
        ]);

        $period = new Period(
            DateTimeHelper::parse('2025-01-10 09:00:00'),
            DateTimeHelper::parse('2025-01-10 17:00:00')
        );

        $schedule = $doctor->availability($period, [
            'title' => 'Weekly Availability',
            'recurrence_rule' => [
                'frequency' => 'weekly',
                'interval' => 1,
            ],
            'recurrence_ends_at' => DateTimeHelper::parse('2025-01-31 17:00:00'),
        ]);

        // Generate occurrences for the month
        $rangeStart = DateTimeHelper::parse('2025-01-01 00:00:00');
        $rangeEnd = DateTimeHelper::parse('2025-01-31 23:59:59');
        $occurrences = $schedule->generateOccurrences($rangeStart, $rangeEnd);

        // Should have 4 weekly occurrences (Jan 10, 17, 24, 31)
        expect(count($occurrences))->toBe(4);
    });

    test('checks for conflicts using hasConflict method', function () {
        $doctor = Doctor::create([
            'name' => 'Dr. White',
            'email' => 'white@test.com',
            'password' => 'password',
            'gender' => 'male',
            'refid' => 'DOC009',
            'status' => 'active',
        ]);

        // Create appointment
        $period1 = new Period(
            DateTimeHelper::parse('2025-01-10 10:00:00'),
            DateTimeHelper::parse('2025-01-10 11:00:00')
        );
        $doctor->appointment($period1);

        // Check for conflict
        $period2 = new Period(
            DateTimeHelper::parse('2025-01-10 10:30:00'),
            DateTimeHelper::parse('2025-01-10 11:30:00')
        );

        expect($doctor->hasConflict($period2, ScheduleType::Appointment))->toBeTrue();

        // Check non-conflicting period
        $period3 = new Period(
            DateTimeHelper::parse('2025-01-10 11:00:00'),
            DateTimeHelper::parse('2025-01-10 12:00:00')
        );

        expect($doctor->hasConflict($period3, ScheduleType::Appointment))->toBeFalse();
    });
});
