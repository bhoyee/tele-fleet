<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreVehicleRequest;
use App\Http\Requests\Fleet\UpdateVehicleRequest;
use App\Models\Vehicle;
use App\Models\TripRequest;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $showArchived = $request->boolean('archived') && $request->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN;
        $now = now();
        $today = $now->toDateString();
        $activeAssignedIds = TripRequest::whereNotNull('assigned_vehicle_id')
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

        $vehiclesQuery = Vehicle::orderBy('registration_number');
        if ($showArchived) {
            $vehiclesQuery->onlyTrashed();
        }
        $vehicles = $vehiclesQuery->get();

        return view('vehicles.index', compact('vehicles', 'activeAssignedIds', 'showArchived'));
    }

    public function indexData(Request $request): JsonResponse
    {
        $showArchived = $request->boolean('archived') && $request->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN;
        $now = now();
        $today = $now->toDateString();
        $activeAssignedIds = TripRequest::whereNotNull('assigned_vehicle_id')
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

        $vehiclesQuery = Vehicle::orderBy('registration_number');
        if ($showArchived) {
            $vehiclesQuery->onlyTrashed();
        }
        $vehicles = $vehiclesQuery->get();

        $payload = $vehicles->map(function (Vehicle $vehicle) use ($activeAssignedIds): array {
            $displayStatus = $vehicle->status;
            if (! in_array($vehicle->status, ['maintenance', 'offline'], true)) {
                $displayStatus = $activeAssignedIds->contains($vehicle->id) ? 'in_use' : 'available';
            }

            return [
                'id' => $vehicle->id,
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

    public function show(Vehicle $vehicle): View
    {
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

        return view('vehicles.show', compact('vehicle', 'maintenanceTimeline', 'analytics', 'activeTrips'));
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

    public function restore(int $vehicle, AuditLogService $auditLog): RedirectResponse
    {
        $vehicleModel = Vehicle::onlyTrashed()->findOrFail($vehicle);
        $vehicleModel->restore();
        $auditLog->log('vehicle.restored', $vehicleModel);

        return redirect()
            ->route('vehicles.index', ['archived' => 1])
            ->with('success', 'Vehicle restored successfully.');
    }

    public function forceDelete(int $vehicle, AuditLogService $auditLog): RedirectResponse
    {
        $vehicleModel = Vehicle::onlyTrashed()->findOrFail($vehicle);
        $vehicleModel->forceDelete();
        $auditLog->log('vehicle.force_deleted', $vehicleModel);

        return redirect()
            ->route('vehicles.index', ['archived' => 1])
            ->with('success', 'Vehicle permanently deleted.');
    }
}
