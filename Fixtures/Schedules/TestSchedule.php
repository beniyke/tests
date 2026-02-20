<?php

declare(strict_types=1);

namespace App\Schedules;

use Cron\Interfaces\Schedulable;
use Cron\Schedule;

class TestSchedule implements Schedulable
{
    public function schedule(Schedule $schedule): void
    {
        $schedule->command('test:fixture')->everyMinute();
    }
}
