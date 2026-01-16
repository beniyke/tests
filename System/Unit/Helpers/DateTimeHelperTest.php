<?php

declare(strict_types=1);

use Helpers\DateTimeHelper;

beforeEach(function () {
    // Set a fixed time for consistent testing
    DateTimeHelper::setTestNow('2024-01-15 12:00:00');
});

afterEach(function () {
    DateTimeHelper::setTestNow();
});

describe('DateTimeHelper - Creation and Parsing', function () {
    test('createFrom creates instance from string', function () {
        $date = DateTimeHelper::createFrom('2024-01-15');

        expect($date)->toBeInstanceOf(DateTimeHelper::class);
        expect($date->format('Y-m-d'))->toBe('2024-01-15');
    });

    test('safeParse returns instance for valid date', function () {
        $date = DateTimeHelper::safeParse('2024-01-15 10:30:00');

        expect($date)->toBeInstanceOf(DateTimeHelper::class);
        expect($date->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:30:00');
    });

    test('safeParse returns null for invalid date', function () {
        expect(DateTimeHelper::safeParse('invalid-date'))->toBeNull();
    });

    test('safeParse returns null for empty string', function () {
        expect(DateTimeHelper::safeParse(''))->toBeNull();
        expect(DateTimeHelper::safeParse(null))->toBeNull();
    });
});

describe('DateTimeHelper - Timezone Conversion', function () {
    test('convert changes timezone correctly', function () {
        $result = DateTimeHelper::convert('2024-01-15 12:00:00', 'UTC', 'America/New_York');

        expect($result)->toContain('2024-01-15 07:00:00');
    });

    test('toUtc converts to UTC', function () {
        $result = DateTimeHelper::toUtc('2024-01-15 12:00:00', 'America/New_York');

        expect($result)->toContain('2024-01-15 17:00:00');
    });

    test('getTimezones returns array of timezones', function () {
        $timezones = DateTimeHelper::getTimezones();

        expect($timezones)->toBeArray();
        expect($timezones)->toContain('UTC');
        expect($timezones)->toContain('America/New_York');
    });
});

describe('DateTimeHelper - Date Range Preparation', function () {
    test('prepareDate handles single date', function () {
        $result = DateTimeHelper::prepareDate('2024-01-15');

        expect($result)->toBeArray();
        expect($result)->toHaveKeys(['start', 'end']);
        expect($result['start'])->toContain('2024-01-15 00:00:00');
        expect($result['end'])->toContain('2024-01-15 23:59:59');
    });

    test('prepareDate handles date range with "to"', function () {
        $result = DateTimeHelper::prepareDate('2024-01-15 to 2024-01-20');

        expect($result)->toBeArray();
        expect($result)->toHaveKeys(['start', 'end']);
        expect($result['start'])->toContain('2024-01-15');
        expect($result['end'])->toContain('2024-01-20');
    });

    test('startAndEndOfDay returns correct timestamps', function () {
        $result = DateTimeHelper::startAndEndOfDay('2024-01-15');

        expect($result)->toBeArray();
        expect($result['start'])->toContain('2024-01-15 00:00:00');
        expect($result['end'])->toContain('2024-01-15 23:59:59');
    });
});

describe('DateTimeHelper - Week, Month, Year Ranges', function () {
    test('startAndEndOfWeek returns current week range', function () {
        $result = DateTimeHelper::startAndEndOfWeek();

        expect($result)->toBeArray();
        expect($result)->toHaveKeys(['start', 'end']);
    });

    test('startAndEndOfWeek returns specific week range', function () {
        $result = DateTimeHelper::startAndEndOfWeek('2024-01-15');

        expect($result)->toBeArray();
        expect($result['start'])->toContain('2024-01');
        expect($result['end'])->toContain('2024-01');
    });

    test('startAndEndOfYear returns correct year range', function () {
        $result = DateTimeHelper::startAndEndOfYear(2024);

        expect($result)->toBeArray();
        expect($result['start'])->toContain('2024-01-01 00:00:00');
        expect($result['end'])->toContain('2024-12-31 23:59:59');
    });

    test('startAndEndOfQuarter returns Q1 range', function () {
        $result = DateTimeHelper::startAndEndOfQuarter(1, 2024);

        expect($result)->toBeArray();
        expect($result['start'])->toContain('2024-01-01');
        expect($result['end'])->toContain('2024-03-31');
    });

    test('startAndEndOfQuarter returns Q4 range', function () {
        $result = DateTimeHelper::startAndEndOfQuarter(4, 2024);

        expect($result)->toBeArray();
        expect($result['start'])->toContain('2024-10-01');
        expect($result['end'])->toContain('2024-12-31');
    });

    test('startAndNowOfThisMonth returns current month range', function () {
        $result = DateTimeHelper::startAndNowOfThisMonth();

        expect($result)->toBeArray();
        expect($result['start'])->toContain('2024-01-01 00:00:00');
        expect($result['end'])->toContain('2024-01-15 12:00:00');
    });

    test('startAndEndOfThisMonth returns current month full range', function () {
        $result = DateTimeHelper::startAndEndOfThisMonth();

        expect($result)->toBeArray();
        expect($result['start'])->toContain('2024-01-01 00:00:00');
        expect($result['end'])->toContain('2024-01-31 23:59:59');
    });
});

describe('DateTimeHelper - Formatting', function () {
    test('formatShortDate formats date correctly', function () {
        $result = DateTimeHelper::formatShortDate('2024-01-15');

        expect($result)->toBeString();
        expect($result)->toContain('Jan');
        expect($result)->toContain('15');
    });

    test('formatFriendlyDatetime formats datetime correctly', function () {
        $result = DateTimeHelper::formatFriendlyDatetime('2024-01-15 14:30:00');

        expect($result)->toBeString();
        expect($result)->toContain('Jan');
        expect($result)->toContain('15');
    });

    test('interpreteDate formats single date', function () {
        $result = DateTimeHelper::interpreteDate('2024-01-15');

        expect($result)->toBeString();
    });

    test('interpreteDate formats date range', function () {
        $result = DateTimeHelper::interpreteDate('2024-01-15 to 2024-01-20');

        expect($result)->toBeString();
        expect($result)->toContain(' to ');
    });
});

describe('DateTimeHelper - Time Ago', function () {
    test('timeAgo returns "just now" for very recent dates', function () {
        $result = DateTimeHelper::timeAgo('2024-01-15 12:00:00');

        expect($result)->toBe('just now');
    });

    test('timeAgo returns relative time for past dates', function () {
        $result = DateTimeHelper::timeAgo('2024-01-14 12:00:00');

        expect($result)->toBeString();
        expect($result)->toContain('day');
    });

    test('timeAgo without tense returns time difference only', function () {
        $result = DateTimeHelper::timeAgo('2024-01-14 12:00:00', false);

        expect($result)->toBeString();
    });

    test('diffForHumansShort returns compact format', function () {
        $result = DateTimeHelper::diffForHumansShort('2024-01-14 12:00:00');

        expect($result)->toBeString();
        expect($result)->toMatch('/\d+[dhms]/');
    });
});

describe('DateTimeHelper - Date Comparisons', function () {
    test('checkIfFuture returns true for future dates', function () {
        expect(DateTimeHelper::checkIfFuture('2024-01-20 12:00:00'))->toBeTrue();
    });

    test('checkIfFuture returns false for past dates', function () {
        expect(DateTimeHelper::checkIfFuture('2024-01-10 12:00:00'))->toBeFalse();
    });

    test('checkIfPast returns true for past dates', function () {
        expect(DateTimeHelper::checkIfPast('2024-01-10 12:00:00'))->toBeTrue();
    });

    test('checkIfPast returns false for future dates', function () {
        expect(DateTimeHelper::checkIfPast('2024-01-20 12:00:00'))->toBeFalse();
    });

    test('isDateToday returns true for today', function () {
        expect(DateTimeHelper::isDateToday('2024-01-15 14:00:00'))->toBeTrue();
    });

    test('isDateToday returns false for other days', function () {
        expect(DateTimeHelper::isDateToday('2024-01-14 14:00:00'))->toBeFalse();
    });

    test('isDateYesterday returns true for yesterday', function () {
        expect(DateTimeHelper::isDateYesterday('2024-01-14 14:00:00'))->toBeTrue();
    });

    test('isDateYesterday returns false for other days', function () {
        expect(DateTimeHelper::isDateYesterday('2024-01-15 14:00:00'))->toBeFalse();
    });

    test('isDateTomorrow returns true for tomorrow', function () {
        expect(DateTimeHelper::isDateTomorrow('2024-01-16 14:00:00'))->toBeTrue();
    });

    test('isDateTomorrow returns false for other days', function () {
        expect(DateTimeHelper::isDateTomorrow('2024-01-15 14:00:00'))->toBeFalse();
    });
});

describe('DateTimeHelper - Special Date Checks', function () {
    test('isDateBirthday returns true when month and day match', function () {
        expect(DateTimeHelper::isDateBirthday('1990-01-15'))->toBeTrue();
    });

    test('isDateBirthday returns false when month and day do not match', function () {
        expect(DateTimeHelper::isDateBirthday('1990-01-16'))->toBeFalse();
    });

    test('isDateWeekend returns true for Saturday', function () {
        expect(DateTimeHelper::isDateWeekend('2024-01-13'))->toBeTrue(); // Saturday
    });

    test('isDateWeekend returns true for Sunday', function () {
        expect(DateTimeHelper::isDateWeekend('2024-01-14'))->toBeTrue(); // Sunday
    });

    test('isDateWeekend returns false for weekdays', function () {
        expect(DateTimeHelper::isDateWeekend('2024-01-15'))->toBeFalse(); // Monday
    });

    test('isDateBusinessDay returns true for weekdays', function () {
        expect(DateTimeHelper::isDateBusinessDay('2024-01-15'))->toBeTrue(); // Monday
    });

    test('isDateBusinessDay returns false for weekends', function () {
        expect(DateTimeHelper::isDateBusinessDay('2024-01-13'))->toBeFalse(); // Saturday
    });

    test('isHoliday returns true for holiday dates', function () {
        $holidays = ['2024-01-15', '2024-12-25'];

        expect(DateTimeHelper::isHoliday('2024-01-15', $holidays))->toBeTrue();
    });

    test('isHoliday returns false for non-holiday dates', function () {
        $holidays = ['2024-12-25'];

        expect(DateTimeHelper::isHoliday('2024-01-15', $holidays))->toBeFalse();
    });

    test('getAge calculates correct age', function () {
        $age = DateTimeHelper::getAge('2000-01-15');

        expect($age)->toBe(24);
    });

    test('getAge calculates age for birthday not yet occurred this year', function () {
        $age = DateTimeHelper::getAge('2000-01-16');

        expect($age)->toBe(23);
    });
});
