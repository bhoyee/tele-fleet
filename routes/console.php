<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('telefleet:snapshot-availability')->dailyAt('00:10');
Schedule::command('telefleet:notify-overdue-trips')->hourly();
Schedule::command('telefleet:notify-unassigned-trips')->everyFifteenMinutes();
Schedule::command('telefleet:check-maintenance-mileage')->hourly();
Schedule::command('telefleet:check-driver-license-expiry')->dailyAt('08:00');
