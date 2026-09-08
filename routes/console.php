<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('wpu:maintenance')->dailyAt('03:00');
Schedule::command('wpu:maintenance --optimize-db')->weeklyOn(0, '04:00');
Schedule::command('appointments:send-reminders')->hourly();
