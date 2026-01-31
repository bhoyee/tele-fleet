<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('telefleet:snapshot-availability')->dailyAt('00:10');
Schedule::command('telefleet:notify-overdue-trips')->hourly()->withoutOverlapping();
Schedule::command('telefleet:notify-unassigned-trips')->everyFifteenMinutes();
Schedule::command('telefleet:check-maintenance-mileage')->hourly();
Schedule::command('telefleet:check-driver-license-expiry')->dailyAt('08:00');
Schedule::command('telefleet:backup-database')->dailyAt(env('BACKUP_SCHEDULE_TIME', '02:00'));
Schedule::command('telefleet:check-system-health')->everyFiveMinutes();
Schedule::command('telefleet:clean-logs --days=14')->dailyAt('01:30');
Schedule::call(function (): void {
    Cache::put('telefleet.scheduler_heartbeat', now()->format('M d, Y H:i:s'), now()->addMinutes(10));
})->everyMinute();
