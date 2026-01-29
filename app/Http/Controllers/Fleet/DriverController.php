<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreDriverRequest;
use App\Http\Requests\Fleet\UpdateDriverRequest;
use App\Models\Driver;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DriverController extends Controller
{
    public function index(Request $request): View
    {
        $showArchived = $request->boolean('archived') && $request->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN;
        $driversQuery = Driver::orderBy('full_name');
        if ($showArchived) {
            $driversQuery->onlyTrashed();
        }
        $drivers = $driversQuery->get();

        return view('drivers.index', compact('drivers', 'showArchived'));
    }

    public function create(): View
    {
        return view('drivers.create');
    }

    public function store(StoreDriverRequest $request, AuditLogService $auditLog): RedirectResponse
    {
        $driver = Driver::create($request->validated());
        $auditLog->log('driver.created', $driver, [], $driver->toArray());

        return redirect()
            ->route('drivers.index')
            ->with('success', 'Driver created successfully.');
    }

    public function edit(Driver $driver): View
    {
        return view('drivers.edit', compact('driver'));
    }

    public function show(Driver $driver): View
    {
        $driver->load('branch');
        $activeTrips = \App\Models\TripRequest::with(['branch', 'requestedBy'])
            ->where('assigned_driver_id', $driver->id)
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

            $tripQuery = \App\Models\TripRequest::where('assigned_driver_id', $driver->id)
                ->whereBetween('trip_date', [$start, $end]);

            $totalTrips = (clone $tripQuery)->count();
            $completedTrips = (clone $tripQuery)->where('status', 'completed')->count();
            $assignedTrips = (clone $tripQuery)->whereIn('status', ['approved', 'assigned'])->count();

            $completionRate = $totalTrips > 0 ? round(($completedTrips / $totalTrips) * 100, 1) : 0;

            $lastTripDate = \App\Models\TripRequest::where('assigned_driver_id', $driver->id)
                ->whereIn('status', ['approved', 'assigned', 'completed'])
                ->orderByDesc('trip_date')
                ->value('trip_date');

            $nextTripDate = \App\Models\TripRequest::where('assigned_driver_id', $driver->id)
                ->whereIn('status', ['approved', 'assigned'])
                ->whereDate('trip_date', '>=', Carbon::now()->toDateString())
                ->orderBy('trip_date')
                ->value('trip_date');

            $analytics = [
                'range_days' => $rangeDays,
                'total_trips' => $totalTrips,
                'completed_trips' => $completedTrips,
                'assigned_trips' => $assignedTrips,
                'completion_rate' => $completionRate,
                'last_trip_date' => $lastTripDate,
                'next_trip_date' => $nextTripDate,
            ];
        }

        return view('drivers.show', compact('driver', 'analytics', 'activeTrips'));
    }

    public function update(UpdateDriverRequest $request, Driver $driver, AuditLogService $auditLog): RedirectResponse
    {
        $oldValues = $driver->getOriginal();
        $data = $request->validated();
        if (! empty($data['license_expiry']) && $driver->license_expiry?->format('Y-m-d') !== $data['license_expiry']) {
            $data['license_expiry_notified_at'] = null;
        }
        $driver->update($data);
        $auditLog->log('driver.updated', $driver, $oldValues, $driver->getChanges());

        return redirect()
            ->route('drivers.index')
            ->with('success', 'Driver updated successfully.');
    }

    public function destroy(Driver $driver, AuditLogService $auditLog): RedirectResponse
    {
        $driver->delete();
        $auditLog->log('driver.deleted', $driver);

        return redirect()
            ->route('drivers.index')
            ->with('success', 'Driver archived successfully.');
    }

    public function restore(int $driver, AuditLogService $auditLog): RedirectResponse
    {
        $driverModel = Driver::onlyTrashed()->findOrFail($driver);
        $driverModel->restore();
        $auditLog->log('driver.restored', $driverModel);

        return redirect()
            ->route('drivers.index', ['archived' => 1])
            ->with('success', 'Driver restored successfully.');
    }

    public function forceDelete(int $driver, AuditLogService $auditLog): RedirectResponse
    {
        $driverModel = Driver::onlyTrashed()->findOrFail($driver);
        $driverModel->forceDelete();
        $auditLog->log('driver.force_deleted', $driverModel);

        return redirect()
            ->route('drivers.index', ['archived' => 1])
            ->with('success', 'Driver permanently deleted.');
    }

    public function indexData(Request $request): JsonResponse
    {
        $showArchived = $request->boolean('archived') && $request->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN;
        $driversQuery = Driver::orderBy('full_name');
        if ($showArchived) {
            $driversQuery->onlyTrashed();
        }
        $drivers = $driversQuery->get();

        return response()->json([
            'data' => $drivers->map(function (Driver $driver): array {
                return [
                    'id' => $driver->id,
                    'full_name' => $driver->full_name,
                    'license_number' => $driver->license_number,
                    'license_expiry' => $driver->license_expiry?->format('M d, Y') ?? 'N/A',
                    'phone' => $driver->phone,
                    'status' => $driver->status,
                    'is_archived' => $driver->trashed(),
                ];
            }),
        ]);
    }
}
