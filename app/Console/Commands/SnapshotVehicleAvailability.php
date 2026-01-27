<?php

namespace App\Console\Commands;

use App\Models\TripRequest;
use App\Models\Vehicle;
use App\Models\VehicleAvailabilitySnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SnapshotVehicleAvailability extends Command
{
    protected $signature = 'telefleet:snapshot-availability {--date=}';

    protected $description = 'Snapshot daily vehicle availability metrics';

    public function handle(): int
    {
        $dateInput = $this->option('date');
        $snapshotDate = $dateInput
            ? Carbon::parse($dateInput)->startOfDay()
            : Carbon::now()->startOfDay();

        $totalVehicles = Vehicle::count();
        $maintenanceVehicles = Vehicle::where('status', 'maintenance')->count();

        $assignedVehicles = TripRequest::whereNotNull('assigned_vehicle_id')
            ->whereIn('status', ['approved', 'assigned'])
            ->where(function ($query): void {
                $query->whereNull('is_completed')->orWhere('is_completed', false);
            })
            ->distinct('assigned_vehicle_id')
            ->count('assigned_vehicle_id');

        $availableVehicles = max(0, $totalVehicles - $maintenanceVehicles - $assignedVehicles);

        VehicleAvailabilitySnapshot::updateOrCreate(
            ['snapshot_date' => $snapshotDate->toDateString()],
            [
                'total_vehicles' => $totalVehicles,
                'available_vehicles' => $availableVehicles,
                'maintenance_vehicles' => $maintenanceVehicles,
                'assigned_vehicles' => $assignedVehicles,
            ]
        );

        $this->info('Vehicle availability snapshot saved for ' . $snapshotDate->toDateString());

        return Command::SUCCESS;
    }
}
