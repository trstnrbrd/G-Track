<?php

use App\Console\Commands\CloseStaleSessions;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
|
| These only run if something invokes `php artisan schedule:run` every minute.
| On Windows that means a Task Scheduler entry — see the README. Without it,
| nothing here ever fires.
|
*/

// Close a day the operator forgot to end, so tomorrow's transactions don't get
// swallowed by yesterday's session. Runs hourly rather than once at a fixed
// time, so the cutoff still applies if the PC was asleep at the appointed hour.
Schedule::command(CloseStaleSessions::class)
    ->hourly()
    ->withoutOverlapping();
