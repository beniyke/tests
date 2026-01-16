<?php

declare(strict_types=1);

namespace Tests\Packages\Slot\Unit;

use Helpers\DateTimeHelper;
use InvalidArgumentException;
use Slot\Period;

describe('Period', function () {
    test('creates period with valid start and end', function () {
        $start = DateTimeHelper::parse('2025-01-01 10:00:00');
        $end = DateTimeHelper::parse('2025-01-01 11:00:00');

        $period = new Period($start, $end);

        expect($period->start)->toBe($start);
        expect($period->end)->toBe($end);
    });

    test('throws exception when start is after end', function () {
        $start = DateTimeHelper::parse('2025-01-01 11:00:00');
        $end = DateTimeHelper::parse('2025-01-01 10:00:00');

        new Period($start, $end);
    })->throws(InvalidArgumentException::class);

    test('creates period from duration', function () {
        $start = DateTimeHelper::parse('2025-01-01 10:00:00');

        $period = Period::fromDuration($start, 60);

        expect($period->duration())->toBe(60);
    });

    test('detects overlapping periods', function () {
        $period1 = new Period(
            DateTimeHelper::parse('2025-01-01 10:00:00'),
            DateTimeHelper::parse('2025-01-01 11:00:00')
        );

        $period2 = new Period(
            DateTimeHelper::parse('2025-01-01 10:30:00'),
            DateTimeHelper::parse('2025-01-01 11:30:00')
        );

        expect($period1->overlaps($period2))->toBeTrue();
        expect($period2->overlaps($period1))->toBeTrue();
    });

    test('detects non-overlapping periods', function () {
        $period1 = new Period(
            DateTimeHelper::parse('2025-01-01 10:00:00'),
            DateTimeHelper::parse('2025-01-01 11:00:00')
        );

        $period2 = new Period(
            DateTimeHelper::parse('2025-01-01 11:00:00'),
            DateTimeHelper::parse('2025-01-01 12:00:00')
        );

        expect($period1->overlaps($period2))->toBeFalse();
    });

    test('checks if datetime is contained', function () {
        $period = new Period(
            DateTimeHelper::parse('2025-01-01 10:00:00'),
            DateTimeHelper::parse('2025-01-01 11:00:00')
        );

        $inside = DateTimeHelper::parse('2025-01-01 10:30:00');
        $outside = DateTimeHelper::parse('2025-01-01 11:30:00');

        expect($period->contains($inside))->toBeTrue();
        expect($period->contains($outside))->toBeFalse();
    });

    test('calculates duration correctly', function () {
        $period = new Period(
            DateTimeHelper::parse('2025-01-01 10:00:00'),
            DateTimeHelper::parse('2025-01-01 11:30:00')
        );

        expect($period->duration())->toBe(90);
    });

    test('splits period into slots', function () {
        $period = new Period(
            DateTimeHelper::parse('2025-01-01 10:00:00'),
            DateTimeHelper::parse('2025-01-01 12:00:00')
        );

        $slots = $period->split(30);

        expect($slots)->toHaveCount(4);
        expect($slots[0]->duration())->toBe(30);
    });

    test('splits period with gap', function () {
        $period = new Period(
            DateTimeHelper::parse('2025-01-01 10:00:00'),
            DateTimeHelper::parse('2025-01-01 12:00:00')
        );

        $slots = $period->split(30, 15);

        expect($slots)->toHaveCount(3); // With 15min gaps, 3 slots fit
    });

    test('adds buffer time', function () {
        $period = new Period(
            DateTimeHelper::parse('2025-01-01 10:00:00'),
            DateTimeHelper::parse('2025-01-01 11:00:00')
        );

        $buffered = $period->addBuffer(15, 15);

        expect($buffered->start->format('H:i'))->toBe('09:45');
        expect($buffered->end->format('H:i'))->toBe('11:15');
        expect($buffered->duration())->toBe(90);
    });

    test('calculates intersection of overlapping periods', function () {
        $period1 = new Period(
            DateTimeHelper::parse('2025-01-01 10:00:00'),
            DateTimeHelper::parse('2025-01-01 12:00:00')
        );

        $period2 = new Period(
            DateTimeHelper::parse('2025-01-01 11:00:00'),
            DateTimeHelper::parse('2025-01-01 13:00:00')
        );

        $intersection = $period1->intersection($period2);

        expect($intersection)->not->toBeNull();
        expect($intersection->start->format('H:i'))->toBe('11:00');
        expect($intersection->end->format('H:i'))->toBe('12:00');
    });

    test('returns null for non-overlapping intersection', function () {
        $period1 = new Period(
            DateTimeHelper::parse('2025-01-01 10:00:00'),
            DateTimeHelper::parse('2025-01-01 11:00:00')
        );

        $period2 = new Period(
            DateTimeHelper::parse('2025-01-01 12:00:00'),
            DateTimeHelper::parse('2025-01-01 13:00:00')
        );

        expect($period1->intersection($period2))->toBeNull();
    });

    test('checks if period encompasses another', function () {
        $outer = new Period(
            DateTimeHelper::parse('2025-01-01 10:00:00'),
            DateTimeHelper::parse('2025-01-01 14:00:00')
        );

        $inner = new Period(
            DateTimeHelper::parse('2025-01-01 11:00:00'),
            DateTimeHelper::parse('2025-01-01 12:00:00')
        );

        expect($outer->encompasses($inner))->toBeTrue();
        expect($inner->encompasses($outer))->toBeFalse();
    });

    test('converts to array', function () {
        $period = new Period(
            DateTimeHelper::parse('2025-01-01 10:00:00'),
            DateTimeHelper::parse('2025-01-01 11:00:00')
        );

        $array = $period->toArray();

        expect($array)->toHaveKey('start');
        expect($array)->toHaveKey('end');
        expect($array)->toHaveKey('duration');
        expect($array['duration'])->toBe(60);
    });
});
