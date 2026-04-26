<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('reviews:send-due')->dailyAt('07:00');
Schedule::command('app:send-due-reminders')->dailyAt('07:00');
Schedule::command('app:cleanup-audit-log')->dailyAt('03:00');
