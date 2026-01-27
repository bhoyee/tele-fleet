<?php

namespace App\Console\Commands;

use App\Models\TripRequest;
use App\Models\User;
use App\Notifications\TripAssignmentPending;
use App\Notifications\TripRequestRejected;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class NotifyUnassignedTrips extends Command
{
    protected $signature = 'telefleet:notify-unassigned-trips';

    protected $description = 'Notify when approved trips are approaching without assignment';

    public function handle(): int
    {
        $now = Carbon::now();
        $cutoff = $now->copy()->addHours(5);

        $trips = TripRequest::where('status', 'approved')
            ->whereNull('assigned_vehicle_id')
            ->whereNull('assigned_driver_id')
            ->whereNull('assignment_reminder_notified_at')
            ->whereNotNull('trip_date')
            ->get();

        $notifiedIds = [];

        foreach ($trips as $trip) {
            $tripMoment = $trip->trip_time
                ? Carbon::createFromFormat('Y-m-d H:i', $trip->trip_date->format('Y-m-d').' '.$trip->trip_time)
                : $trip->trip_date->copy()->startOfDay();

            if ($tripMoment->lessThanOrEqualTo($now)) {
                $trip->update([
                    'status' => 'rejected',
                    'rejection_reason' => 'System auto rejected since no drive assigned',
                    'updated_by_user_id' => null,
                ]);

                if ($trip->requestedBy) {
                    try {
                        $trip->requestedBy->notify(new TripRequestRejected($trip));
                    } catch (Throwable $exception) {
                        Log::warning('Trip auto-rejection notification failed.', [
                            'trip_request_id' => $trip->id,
                            'error' => $exception->getMessage(),
                        ]);
                    }
                }

                $notifiedIds[] = $trip->id;
                continue;
            }

            if ($tripMoment->greaterThan($cutoff)) {
                continue;
            }

            $recipients = collect();

            $fleetManagers = User::where('role', User::ROLE_FLEET_MANAGER)->get();
            $superAdmins = User::where('role', User::ROLE_SUPER_ADMIN)->get();
            $recipients = $recipients->merge($fleetManagers)->merge($superAdmins);

            if ($trip->requestedBy) {
                $recipients->push($trip->requestedBy);
            }

            try {
                Notification::send($recipients->unique('id'), new TripAssignmentPending($trip));
                $trip->forceFill(['assignment_reminder_notified_at' => $now])->save();
                $notifiedIds[] = $trip->id;
            } catch (Throwable $exception) {
                Log::warning('Trip assignment reminder failed.', [
                    'trip_request_id' => $trip->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $this->info('Notified trips: '.count($notifiedIds));

        return self::SUCCESS;
    }
}
