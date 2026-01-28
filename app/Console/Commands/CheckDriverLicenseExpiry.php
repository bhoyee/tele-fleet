<?php

namespace App\Console\Commands;

use App\Models\Driver;
use App\Models\User;
use App\Notifications\DriverLicenseExpiring;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class CheckDriverLicenseExpiry extends Command
{
    protected $signature = 'telefleet:check-driver-license-expiry';
    protected $description = 'Notify fleet managers and super admins about driver licenses expiring within six months.';

    public function handle(): int
    {
        $now = Carbon::now();
        $threshold = $now->copy()->addMonths(6);

        $drivers = Driver::whereNotNull('license_expiry')
            ->whereBetween('license_expiry', [$now->toDateString(), $threshold->toDateString()])
            ->whereNull('license_expiry_notified_at')
            ->get();

        if ($drivers->isEmpty()) {
            return self::SUCCESS;
        }

        $recipients = User::whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER])->get();

        if ($recipients->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($drivers as $driver) {
            try {
                Notification::send($recipients, new DriverLicenseExpiring($driver));
                $driver->forceFill(['license_expiry_notified_at' => $now])->save();
            } catch (Throwable $exception) {
                Log::warning('Driver license expiry notification failed.', [
                    'driver_id' => $driver->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return self::SUCCESS;
    }
}
