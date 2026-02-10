<?php

namespace App\Jobs;

use App\Models\TripRequest;
use App\Models\User;
use App\Notifications\TripRequestAssigned;
use App\Notifications\TripRequestReassigned;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ProcessTripAssignmentSideEffects implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $tripRequestId,
        public bool $isReassignment,
        public ?int $fromVehicleId = null,
        public ?int $fromDriverId = null,
        public ?string $reason = null,
    ) {
    }

    public function handle(SmsService $sms): void
    {
        $tripRequest = TripRequest::query()
            ->with(['assignedVehicle', 'assignedDriver', 'requestedBy'])
            ->find($this->tripRequestId);

        if (! $tripRequest) {
            return;
        }

        $recipients = $this->buildRecipients($tripRequest, $tripRequest->requestedBy);

        try {
            if ($this->isReassignment) {
                Notification::send(
                    $recipients,
                    new TripRequestReassigned(
                        $tripRequest,
                        $this->fromVehicleId,
                        $this->fromDriverId,
                        (string) $this->reason
                    )
                );
            } else {
                Notification::send($recipients, new TripRequestAssigned($tripRequest));
            }
        } catch (\Throwable $exception) {
            Log::warning('Trip assignment notification failed.', [
                'trip_request_id' => $tripRequest->id,
                'error' => $exception->getMessage(),
            ]);
        }

        try {
            if ($tripRequest->assignedDriver?->phone) {
                $sms->send($tripRequest->assignedDriver->phone, sprintf(
                    'Trip %s assigned. Vehicle %s. Destination: %s. Date: %s.',
                    $tripRequest->request_number,
                    $tripRequest->assignedVehicle?->registration_number ?? 'N/A',
                    $tripRequest->destination,
                    $tripRequest->trip_date?->format('Y-m-d') ?? ''
                ));
            }
        } catch (\Throwable $exception) {
            Log::warning('Trip assignment SMS failed.', [
                'trip_request_id' => $tripRequest->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function buildRecipients(TripRequest $tripRequest, ?User $requester = null)
    {
        $recipients = collect();

        $fleetManagers = User::query()->where('role', User::ROLE_FLEET_MANAGER)->get();
        $superAdmins = User::query()->where('role', User::ROLE_SUPER_ADMIN)->get();
        $branchHeads = User::query()
            ->where('role', User::ROLE_BRANCH_HEAD)
            ->where('branch_id', $tripRequest->branch_id)
            ->get();

        $recipients = $recipients->merge($fleetManagers)->merge($superAdmins)->merge($branchHeads);

        if ($requester) {
            $recipients->push($requester);
        }

        return $recipients->unique('id')->values();
    }
}

