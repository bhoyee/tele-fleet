<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreVehicleRequest;
use App\Http\Requests\Fleet\UpdateVehicleRequest;
use App\Models\Vehicle;
use App\Models\TripRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        Vehicle::create($data);

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

        return view('vehicles.show', compact('vehicle', 'maintenanceTimeline'));
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($request->validated());

        return redirect()
            ->route('vehicles.index')
            ->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->delete();

        return redirect()
            ->route('vehicles.index')
            ->with('success', 'Vehicle deleted successfully.');
    }

    public function restore(int $vehicle): RedirectResponse
    {
        $vehicleModel = Vehicle::onlyTrashed()->findOrFail($vehicle);
        $vehicleModel->restore();

        return redirect()
            ->route('vehicles.index', ['archived' => 1])
            ->with('success', 'Vehicle restored successfully.');
    }

    public function forceDelete(int $vehicle): RedirectResponse
    {
        $vehicleModel = Vehicle::onlyTrashed()->findOrFail($vehicle);
        $vehicleModel->forceDelete();

        return redirect()
            ->route('vehicles.index', ['archived' => 1])
            ->with('success', 'Vehicle permanently deleted.');
    }
}
