<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\VehicleMaintenanceMileageAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class CheckVehicleMaintenanceMileage extends Command
{
    protected $signature = 'telefleet:check-maintenance-mileage';

    protected $description = 'Check vehicle mileage against maintenance targets and notify when due/overdue.';

    public function handle(): int
    {
        $target = (int) AppSetting::getValue('maintenance_mileage_target', '5000');
        $dueThresholdRatio = 0.98;

        $recipients = User::whereIn('role', [
            User::ROLE_FLEET_MANAGER,
            User::ROLE_SUPER_ADMIN,
        ])->get();

        Vehicle::query()->chunkById(100, function ($vehicles) use ($target, $dueThresholdRatio, $recipients): void {
            foreach ($vehicles as $vehicle) {
                if ($vehicle->status === 'maintenance') {
                    if ($vehicle->maintenance_state !== 'ok') {
                        $vehicle->maintenance_state = 'ok';
                        $vehicle->maintenance_due_notified_at = null;
                        $vehicle->maintenance_overdue_notified_at = null;
                        $vehicle->save();
                    }
                    continue;
                }
                $lastMileage = (int) ($vehicle->last_maintenance_mileage ?? 0);
                $currentMileage = (int) ($vehicle->current_mileage ?? 0);
                $threshold = $lastMileage + $target;
                $dueThreshold = (int) ceil($lastMileage + ($target * $dueThresholdRatio));

                $newState = 'ok';
                if ($currentMileage >= $threshold) {
                    $newState = 'overdue';
                } elseif ($currentMileage >= $dueThreshold) {
                    $newState = 'due';
                }

                if ($vehicle->maintenance_state !== $newState) {
                    $vehicle->maintenance_state = $newState;
                }

                if ($newState === 'overdue' && ! $vehicle->maintenance_overdue_notified_at) {
                    $this->sendAlert($recipients, $vehicle, 'overdue', $threshold);
                    $vehicle->maintenance_overdue_notified_at = now();
                } elseif ($newState === 'due' && ! $vehicle->maintenance_due_notified_at) {
                    $this->sendAlert($recipients, $vehicle, 'due', $threshold);
                    $vehicle->maintenance_due_notified_at = now();
                }

                if ($newState === 'ok') {
                    $vehicle->maintenance_due_notified_at = null;
                    $vehicle->maintenance_overdue_notified_at = null;
                }

                $vehicle->save();
            }
        });

        return Command::SUCCESS;
    }

    private function sendAlert($recipients, Vehicle $vehicle, string $state, int $threshold): void
    {
        try {
            Notification::send($recipients, new VehicleMaintenanceMileageAlert($vehicle, $state, $threshold));
        } catch (Throwable $exception) {
            Log::warning('Maintenance mileage alert failed.', [
                'vehicle_id' => $vehicle->id,
                'state' => $state,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
