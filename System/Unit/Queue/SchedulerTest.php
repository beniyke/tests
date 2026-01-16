<?php

declare(strict_types=1);

use Helpers\DateTimeHelper;
use Queue\Scheduler;
use Tests\System\Helpers\ConfigStub;

beforeEach(function () {
    $this->config = new ConfigStub(['timezone' => 'UTC']);
});

describe('Scheduler - Time Period Methods', function () {
    test('adds minutes to current time', function () {
        $scheduler = new Scheduler($this->config);
        $result = $scheduler->minute(30)->time();

        $timezone = $this->config->get('timezone');
        $expected = DateTimeHelper::now()->setTimezone($timezone)->addMinutes(30);

        expect($result->format('Y-m-d H:i'))->toBe($expected->format('Y-m-d H:i'));
    });

    test('adds days to current time', function () {
        $scheduler = new Scheduler($this->config);
        $result = $scheduler->day(7)->time();

        $timezone = $this->config->get('timezone');
        $expected = DateTimeHelper::now()->setTimezone($timezone)->addDays(7);

        expect($result->format('Y-m-d'))->toBe($expected->format('Y-m-d'));
    });

    test('chains multiple time periods', function () {
        $scheduler = new Scheduler($this->config);
        $result = $scheduler->month(1)->week(2)->day(3)->time();

        $timezone = $this->config->get('timezone');
        $expected = DateTimeHelper::now()
            ->setTimezone($timezone)
            ->addMonths(1)
            ->addWeeks(2)
            ->addDays(3);

        expect($result->format('Y-m-d'))->toBe($expected->format('Y-m-d'));
    });
});

describe('Scheduler - at() Method', function () {
    test('sets time to specific hour and minute', function () {
        $scheduler = new Scheduler($this->config);
        $result = $scheduler->day(1)->at('09:00')->time();

        expect($result->format('H:i:s'))->toBe('09:00:00');
    });

    test('sets time with seconds', function () {
        $scheduler = new Scheduler($this->config);
        $result = $scheduler->day(1)->at('14:30:45')->time();

        expect($result->format('H:i:s'))->toBe('14:30:45');
    });

    test('schedules tomorrow at specific time', function () {
        $scheduler = new Scheduler($this->config);
        $result = $scheduler->day(1)->at('09:00')->time();

        $timezone = $this->config->get('timezone');
        $tomorrow = DateTimeHelper::now()->setTimezone($timezone)->addDays(1);

        expect($result->format('Y-m-d'))->toBe($tomorrow->format('Y-m-d'));
        expect($result->format('H:i'))->toBe('09:00');
    });

    test('works with initial date', function () {
        $scheduler = new Scheduler($this->config);
        $timezone = $this->config->get('timezone');
        $initialDate = DateTimeHelper::parse('2024-12-25 00:00:00', $timezone);

        $scheduler->setInitialDate($initialDate);
        $result = $scheduler->day(1)->at('09:00')->time();

        expect($result->format('Y-m-d H:i'))->toBe('2024-12-26 09:00');
    });

    test('throws exception for invalid time format', function () {
        $scheduler = new Scheduler($this->config);

        expect(fn () => $scheduler->at('invalid'))
            ->toThrow(InvalidArgumentException::class, 'HH:MM or HH:MM:SS format');
    });

    test('throws exception for invalid hour', function () {
        $scheduler = new Scheduler($this->config);

        expect(fn () => $scheduler->at('25:00'))
            ->toThrow(InvalidArgumentException::class, 'Hour must be between 0 and 23');
    });

    test('throws exception for invalid minute', function () {
        $scheduler = new Scheduler($this->config);

        expect(fn () => $scheduler->at('12:60'))
            ->toThrow(InvalidArgumentException::class, 'Minute must be between 0 and 59');
    });

    test('handles midnight correctly', function () {
        $scheduler = new Scheduler($this->config);
        $result = $scheduler->day(1)->at('00:00')->time();

        expect($result->format('H:i:s'))->toBe('00:00:00');
    });

    test('handles end of day correctly', function () {
        $scheduler = new Scheduler($this->config);
        $result = $scheduler->day(1)->at('23:59:59')->time();

        expect($result->format('H:i:s'))->toBe('23:59:59');
    });
});

describe('Scheduler - Real-World Scenarios', function () {
    test('schedules daily report for tomorrow at 9am', function () {
        $scheduler = new Scheduler($this->config);
        $scheduleTime = $scheduler->day(1)->at('09:00')->time();

        $timezone = $this->config->get('timezone');
        $tomorrow = DateTimeHelper::now()->setTimezone($timezone)->addDays(1);

        expect($scheduleTime->format('Y-m-d'))->toBe($tomorrow->format('Y-m-d'));
        expect($scheduleTime->format('H:i'))->toBe('09:00');
        expect($scheduleTime->getTimestamp())->toBeGreaterThan(time());
    });

    test('schedules weekly backup for next week at 2am', function () {
        $scheduler = new Scheduler($this->config);
        $scheduleTime = $scheduler->week(1)->at('02:00')->time();

        expect($scheduleTime->format('H:i'))->toBe('02:00');
        expect($scheduleTime->getTimestamp())->toBeGreaterThan(time());
    });

    test('can set time after using initial date', function () {
        $scheduler = new Scheduler($this->config);
        $initialDate = DateTimeHelper::parse('2024-12-25 00:00:00');

        $scheduler->setInitialDate($initialDate);
        $result = $scheduler->day(1)->at('15:30')->time();

        $timezone = $this->config->get('timezone');
        $expected = DateTimeHelper::parse('2024-12-25 00:00:00')
            ->setTimezone($timezone)
            ->addDays(1)
            ->setTime(15, 30);

        expect($result->format('Y-m-d H:i'))->toBe($expected->format('Y-m-d H:i'));
    });
});

describe('Scheduler - Edge Cases', function () {
    test('at() can be called before time period methods', function () {
        $scheduler = new Scheduler($this->config);
        $result = $scheduler->at('10:00')->day(1)->time();

        // at() sets time on current date, then day(1) adds a day
        $timezone = $this->config->get('timezone');
        $tomorrow = DateTimeHelper::now()->setTimezone($timezone)->addDays(1);
        expect($result->format('Y-m-d'))->toBe($tomorrow->format('Y-m-d'));
        expect($result->format('H:i'))->toBe('10:00');
    });

    test('multiple at() calls use the last one', function () {
        $scheduler = new Scheduler($this->config);
        $result = $scheduler->day(1)->at('09:00')->at('14:00')->time();

        expect($result->format('H:i'))->toBe('14:00');
    });
});
