<?php

namespace App\Console\Commands;

use App\Models\TripRequest;
use App\Models\User;
use App\Notifications\OverdueTripNotification;
use App\Notifications\TripCompletionReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class NotifyOverdueTrips extends Command
{
    protected $signature = 'telefleet:notify-overdue-trips';

    protected $description = 'Notify fleet manager and super admin about overdue assigned trips';

    public function handle(): int
    {
        $cutoff = Carbon::now()->subDays(7);

        $reminderCutoff = Carbon::now()->subDay();

        $reminderTrips = TripRequest::whereNotNull('assigned_at')
            ->whereIn('status', ['approved', 'assigned'])
            ->where(function ($query): void {
                $query->whereNull('is_completed')->orWhere('is_completed', false);
            })
            ->whereDate('trip_date', '<=', $reminderCutoff)
            ->whereNull('reminder_notified_at')
            ->get();

        $trips = TripRequest::whereNotNull('assigned_at')
            ->whereIn('status', ['approved', 'assigned'])
            ->where(function ($query): void {
                $query->whereNull('is_completed')->orWhere('is_completed', false);
            })
            ->where('assigned_at', '<=', $cutoff)
            ->whereNull('overdue_notified_at')
            ->get();

        $recipients = User::whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER])->get();

        foreach ($reminderTrips as $trip) {
            $updated = TripRequest::whereKey($trip->id)
                ->whereNull('reminder_notified_at')
                ->update(['reminder_notified_at' => Carbon::now()]);

            if ($updated > 0) {
                Notification::send($recipients, new TripCompletionReminderNotification($trip));
            }
        }

        foreach ($trips as $trip) {
            $updated = TripRequest::whereKey($trip->id)
                ->whereNull('overdue_notified_at')
                ->update(['overdue_notified_at' => Carbon::now()]);

            if ($updated > 0) {
                Notification::send($recipients, new OverdueTripNotification($trip));
            }
        }

        $this->info('Trip reminder notifications processed.');

        return Command::SUCCESS;
    }
}
