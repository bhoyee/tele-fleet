<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use App\Models\Vehicle;
use App\Models\VehicleMaintenance;
use Illuminate\Console\Command;

class RecalculateVehicleMaintenanceState extends Command
{
    protected $signature = 'telefleet:recalc-vehicle-maintenance-state {--fix-last-mileage : Backfill last_maintenance_mileage from latest completed maintenance odometer when missing/0}';

    protected $description = 'Recalculate vehicle maintenance_state based on current mileage and last maintenance mileage.';

    public function handle(): int
    {
        $target = (int) AppSetting::getValue('maintenance_mileage_target', '5000');
        $fixLastMileage = (bool) $this->option('fix-last-mileage');

        $vehicles = Vehicle::query()->get();

        $updated = 0;
        $backfilled = 0;

        foreach ($vehicles as $vehicle) {
            if ($fixLastMileage && ((int) ($vehicle->last_maintenance_mileage ?? 0)) <= 0) {
                $completed = VehicleMaintenance::query()
                    ->where('vehicle_id', $vehicle->id)
                    ->where('status', VehicleMaintenance::STATUS_COMPLETED)
                    ->orderByDesc('completed_at')
                    ->orderByDesc('created_at')
                    ->first();

                if ($completed && $completed->odometer !== null) {
                    $vehicle->last_maintenance_mileage = (int) $completed->odometer;
                    $backfilled++;
                }
            }

            $last = (int) ($vehicle->last_maintenance_mileage ?? 0);
            $current = (int) ($vehicle->current_mileage ?? 0);

            $dueThreshold = (int) ceil($last + ($target * 0.98));
            $overdueThreshold = (int) ($last + $target);

            $state = 'ok';
            if ($current >= $overdueThreshold) {
                $state = 'overdue';
            } elseif ($current >= $dueThreshold) {
                $state = 'due';
            }

            if ($vehicle->maintenance_state !== $state) {
                $vehicle->maintenance_state = $state;
                if ($state === 'ok') {
                    $vehicle->maintenance_due_notified_at = null;
                    $vehicle->maintenance_overdue_notified_at = null;
                }
                $updated++;
            }

            if ($vehicle->isDirty()) {
                $vehicle->save();
            }
        }

        $this->info('Vehicle maintenance state recalculation complete.');
        $this->line('Maintenance target: '.$target);
        $this->line('Backfilled last_maintenance_mileage: '.$backfilled);
        $this->line('Updated maintenance_state: '.$updated);

        return self::SUCCESS;
    }
}

