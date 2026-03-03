<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreVehicleRequest;
use App\Http\Requests\Fleet\UpdateVehicleRequest;
use App\Models\Vehicle;
use App\Models\TripRequest;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class VehicleController extends Controller
{
    private function activeAssignedVehicleIds()
    {
        $now = now();
        $today = $now->toDateString();

        return TripRequest::whereNotNull('assigned_vehicle_id')
            ->whereIn('status', ['approved', 'assigned'])
            ->where(function ($query): void {
                $query->whereNull('is_completed')->orWhere('is_completed', false);
            })
            ->where(function ($query) use ($today, $now): void {
                $query->whereDate('trip_date', '<', $today)
                    ->orWhere(function ($sub) use ($today, $now): void {
                        $sub->whereDate('trip_date', $today)
                            ->where(function ($timeQuery) use ($now): void {
                                $timeQuery->whereNull('trip_time')
                                    ->orWhere('trip_time', '<=', $now->format('H:i'));
                            });
                    });
            })
            ->pluck('assigned_vehicle_id')
            ->unique();
    }

    private function displayStatus(Vehicle $vehicle, $activeAssignedIds): string
    {
        if (in_array($vehicle->status, ['maintenance', 'offline'], true)) {
            return $vehicle->status;
        }

        return $activeAssignedIds->contains($vehicle->id) ? 'in_use' : 'available';
    }

    private function buildVehicleStats($vehicles, $activeAssignedIds): array
    {
        $stats = [
            'total' => 0,
            'available' => 0,
            'in_use' => 0,
            'offline' => 0,
            'maintenance' => 0,
        ];

        foreach ($vehicles as $vehicle) {
            $stats['total'] += 1;

            $displayStatus = $this->displayStatus($vehicle, $activeAssignedIds);

            if (isset($stats[$displayStatus])) {
                $stats[$displayStatus] += 1;
            }
        }

        return $stats;
    }

    public function index(Request $request): View
    {
        $showArchived = $request->boolean('archived') && $request->user()?->role === User::ROLE_SUPER_ADMIN;
        $activeAssignedIds = $this->activeAssignedVehicleIds();

        $vehiclesQuery = Vehicle::orderBy('registration_number');
        if ($showArchived) {
            $vehiclesQuery->onlyTrashed();
        }
        $vehicles = $vehiclesQuery->get();
        $vehicleStats = $this->buildVehicleStats($vehicles, $activeAssignedIds);

        $vehicleTripLogs = collect();
        if (in_array($request->user()?->role, [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER], true)) {
            $vehicleTripLogs = TripRequest::query()
                ->with(['assignedVehicle', 'branch'])
                ->whereNotNull('assigned_vehicle_id')
                ->whereIn('status', ['approved', 'assigned'])
                ->where(function ($query): void {
                    $query->whereNull('is_completed')->orWhere('is_completed', false);
                })
                ->orderBy('trip_date')
                ->orderBy('trip_time')
                ->get();
        }

        return view('vehicles.index', compact('vehicles', 'activeAssignedIds', 'showArchived', 'vehicleTripLogs', 'vehicleStats'));
    }

    public function indexData(Request $request): JsonResponse
    {
        $showArchived = $request->boolean('archived') && $request->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN;
        $activeAssignedIds = $this->activeAssignedVehicleIds();

        $vehiclesQuery = Vehicle::orderBy('registration_number');
        if ($showArchived) {
            $vehiclesQuery->onlyTrashed();
        }
        $vehicles = $vehiclesQuery->get();

        $payload = $vehicles->map(function (Vehicle $vehicle) use ($activeAssignedIds): array {
            $displayStatus = $this->displayStatus($vehicle, $activeAssignedIds);

            // Auto-correct stale DB status (e.g., manually set to in_use without an active trip).
            if ($vehicle->status === 'in_use' && $displayStatus === 'available') {
                $vehicle->update(['status' => 'available']);
            }

            $publicId = is_string($vehicle->uuid ?? null) && $vehicle->uuid !== '' ? $vehicle->uuid : (string) $vehicle->id;

            return [
                'id' => $vehicle->id,
                'public_id' => $publicId,
                'registration_number' => $vehicle->registration_number,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'current_mileage' => $vehicle->current_mileage,
                'status' => $displayStatus,
                'maintenance_state' => $vehicle->maintenance_state,
                'is_archived' => $vehicle->trashed(),
            ];
        });

        return response()->json(['data' => $payload]);
    }

    public function create(): View
    {
        return view('vehicles.create');
    }

    public function store(StoreVehicleRequest $request, AuditLogService $auditLog): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $vehicle = Vehicle::create($data);
        $auditLog->log('vehicle.created', $vehicle, [], $vehicle->toArray());

        return redirect()
            ->route('vehicles.index')
            ->with('success', 'Vehicle created successfully.');
    }

    public function edit(Vehicle $vehicle): View
    {
        $maintenanceTimeline = $vehicle->maintenances()
            ->orderByDesc('scheduled_for')
            ->orderByDesc('created_at')
            ->get();

        return view('vehicles.edit', compact('vehicle', 'maintenanceTimeline'));
    }

    public function show(Vehicle $vehicle, Request $request, AuditLogService $auditLog): View
    {
        $activeAssignedIds = $this->activeAssignedVehicleIds();
        $currentStatus = $this->displayStatus($vehicle, $activeAssignedIds);
        $statusWasCorrected = false;
        $previousStatus = $vehicle->status;

        if ($vehicle->status === 'in_use' && $currentStatus === 'available') {
            $vehicle->update(['status' => 'available']);
            $statusWasCorrected = true;
            $auditLog->log('vehicle.status_synced', $vehicle, ['status' => $previousStatus], ['status' => $vehicle->status]);
        }

        $maintenanceTimeline = $vehicle->maintenances()
            ->orderByDesc('scheduled_for')
            ->orderByDesc('created_at')
            ->get();
        $activeTrips = TripRequest::with(['branch', 'requestedBy'])
            ->where('assigned_vehicle_id', $vehicle->id)
            ->whereIn('status', ['approved', 'assigned'])
            ->where(function ($query): void {
                $query->whereNull('is_completed')->orWhere('is_completed', false);
            })
            ->orderBy('trip_date')
            ->orderBy('trip_time')
            ->get();

        $analytics = null;
        if (request()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN) {
            $rangeDays = 30;
            $start = Carbon::now()->subDays($rangeDays - 1)->startOfDay();
            $end = Carbon::now()->endOfDay();
            $totalDays = $start->diffInDays($end) + 1;

            $vehicleTripsQuery = TripRequest::where('assigned_vehicle_id', $vehicle->id)
                ->whereBetween('trip_date', [$start, $end])
                ->whereIn('status', ['approved', 'assigned', 'completed']);

            $vehicleTrips = $vehicleTripsQuery->count();
            $assignedDays = $vehicleTripsQuery
                ->select('trip_date')
                ->distinct()
                ->count();

            $utilization = $totalDays > 0 ? round(($assignedDays / $totalDays) * 100, 1) : 0;

            $fleetVehicleCount = Vehicle::count();
            $fleetAssignedDays = TripRequest::whereNotNull('assigned_vehicle_id')
                ->whereBetween('trip_date', [$start, $end])
                ->whereIn('status', ['approved', 'assigned', 'completed'])
                ->selectRaw('assigned_vehicle_id, trip_date')
                ->distinct()
                ->count();
            $fleetUtilization = ($fleetVehicleCount > 0 && $totalDays > 0)
                ? round(($fleetAssignedDays / ($fleetVehicleCount * $totalDays)) * 100, 1)
                : 0;

            $lastTripDate = TripRequest::where('assigned_vehicle_id', $vehicle->id)
                ->whereIn('status', ['approved', 'assigned', 'completed'])
                ->orderByDesc('trip_date')
                ->value('trip_date');

            $nextTripDate = TripRequest::where('assigned_vehicle_id', $vehicle->id)
                ->whereIn('status', ['approved', 'assigned'])
                ->whereDate('trip_date', '>=', Carbon::now()->toDateString())
                ->orderBy('trip_date')
                ->value('trip_date');

            $analytics = [
                'range_days' => $rangeDays,
                'total_trips' => $vehicleTrips,
                'assigned_days' => $assignedDays,
                'utilization' => $utilization,
                'fleet_utilization' => $fleetUtilization,
                'last_trip_date' => $lastTripDate,
                'next_trip_date' => $nextTripDate,
            ];
        }

        return view('vehicles.show', compact('vehicle', 'maintenanceTimeline', 'analytics', 'activeTrips', 'currentStatus', 'statusWasCorrected', 'previousStatus'));
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle, AuditLogService $auditLog): RedirectResponse
    {
        $oldValues = $vehicle->getOriginal();
        $vehicle->update($request->validated());
        $auditLog->log('vehicle.updated', $vehicle, $oldValues, $vehicle->getChanges());

        return redirect()
            ->route('vehicles.index')
            ->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(Vehicle $vehicle, AuditLogService $auditLog): RedirectResponse
    {
        $vehicle->delete();
        $auditLog->log('vehicle.deleted', $vehicle);

        return redirect()
            ->route('vehicles.index')
            ->with('success', 'Vehicle deleted successfully.');
    }

    public function restore(Vehicle $vehicle, AuditLogService $auditLog): RedirectResponse
    {
        if (! $vehicle->trashed()) {
            return redirect()
                ->route('vehicles.index')
                ->with('error', 'Vehicle is already active.');
        }

        $vehicle->restore();
        $auditLog->log('vehicle.restored', $vehicle);

        return redirect()
            ->route('vehicles.index', ['archived' => 1])
            ->with('success', 'Vehicle restored successfully.');
    }

    public function forceDelete(Vehicle $vehicle, AuditLogService $auditLog): RedirectResponse
    {
        $vehicle->forceDelete();
        $auditLog->log('vehicle.force_deleted', $vehicle);

        return redirect()
            ->route('vehicles.index', ['archived' => 1])
            ->with('success', 'Vehicle permanently deleted.');
    }
}
