<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('storage:clean-temp')->daily();

Schedule::command('db:backup')
    ->dailyAt(config('backup.daily_time'))
    ->timezone(config('backup.timezone'))
    ->withoutOverlapping(1440);
