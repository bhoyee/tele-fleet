<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreDriverRequest;
use App\Http\Requests\Fleet\UpdateDriverRequest;
use App\Models\Driver;
use App\Models\TripRequest;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DriverController extends Controller
{
    private function driverDutyIds(Carbon $now): array
    {
        $today = $now->toDateString();

        // "Assigned today" should include upcoming trips later today (not only those already started),
        // so the dashboard and drivers list stay consistent with the "Assigned Future" trip log.
        $assignedToday = TripRequest::whereDate('trip_date', $today)
            ->whereIn('status', ['approved', 'assigned'])
            ->whereNotNull('assigned_driver_id')
            ->where(function ($query): void {
                $query->whereNull('is_completed')->orWhere('is_completed', false);
            })
            ->distinct('assigned_driver_id')
            ->pluck('assigned_driver_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $unavailable = TripRequest::whereIn('status', ['approved', 'assigned'])
            ->whereNotNull('assigned_driver_id')
            ->where(function ($query): void {
                $query->whereNull('is_completed')->orWhere('is_completed', false);
            })
            ->where(function ($query) use ($today): void {
                $query->whereDate('trip_date', '<=', $today);
            })
            ->distinct('assigned_driver_id')
            ->pluck('assigned_driver_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return [
            'assigned_today' => $assignedToday,
            'unavailable' => $unavailable,
        ];
    }

    private function buildDriverStats($drivers): array
    {
        $stats = [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'suspended' => 0,
        ];

        foreach ($drivers as $driver) {
            $stats['total'] += 1;
            $status = strtolower((string) ($driver->status ?? ''));
            if (isset($stats[$status])) {
                $stats[$status] += 1;
            }
        }

        return $stats;
    }

    public function index(Request $request): View
    {
        $showArchived = $request->boolean('archived') && $request->user()?->role === User::ROLE_SUPER_ADMIN;
        $driversQuery = Driver::orderBy('full_name');
        if ($showArchived) {
            $driversQuery->onlyTrashed();
        }
        $drivers = $driversQuery->get();
        $driverStats = $this->buildDriverStats($drivers);

        $driverTripLogs = collect();
        $assignedTodayDriverIds = [];
        $unavailableDriverIds = [];
        if (in_array($request->user()?->role, [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER], true)) {
            $now = Carbon::now();
            $duty = $this->driverDutyIds($now);
            $assignedTodayDriverIds = $duty['assigned_today'];
            $unavailableDriverIds = $duty['unavailable'];

            $driverTripLogs = TripRequest::query()
                ->with(['assignedDriver', 'branch'])
                ->whereNotNull('assigned_driver_id')
                ->whereIn('status', ['approved', 'assigned'])
                ->where(function ($query): void {
                    $query->whereNull('is_completed')->orWhere('is_completed', false);
                })
                ->orderBy('trip_date')
                ->orderBy('trip_time')
                ->get();
        }

        return view('drivers.index', compact(
            'drivers',
            'showArchived',
            'driverTripLogs',
            'driverStats',
            'assignedTodayDriverIds',
            'unavailableDriverIds',
        ));
    }

    public function create(): View
    {
        return view('drivers.create');
    }

    public function checkEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'ignore' => ['nullable', 'integer'],
        ]);

        $email = $validated['email'] ?? null;
        if (! is_string($email) || trim($email) === '') {
            return response()->json(['available' => true]);
        }

        $email = mb_strtolower(trim($email), 'UTF-8');

        $query = Driver::withTrashed()->where('email', $email);
        if (! empty($validated['ignore'])) {
            $query->where('id', '!=', (int) $validated['ignore']);
        }

        return response()->json([
            'available' => ! $query->exists(),
        ]);
    }

    public function store(StoreDriverRequest $request, AuditLogService $auditLog): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by_user_id'] = $request->user()?->id;
        $data['updated_by_user_id'] = $request->user()?->id;

        $driver = Driver::create($data);
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
        $driver->load('branch', 'createdBy', 'updatedBy');
        $activeTrips = \App\Models\TripRequest::with(['branch', 'requestedBy'])
            ->where('assigned_driver_id', $driver->id)
            ->whereIn('status', ['approved', 'assigned'])
            ->where(function ($query): void {
                $query->whereNull('is_completed')->orWhere('is_completed', false);
            })
            ->orderBy('trip_date')
            ->orderBy('trip_time')
            ->get();

        $pastTrips = \App\Models\TripRequest::with(['branch', 'requestedBy'])
            ->where('assigned_driver_id', $driver->id)
            ->where(function ($query): void {
                $query->where('status', 'completed')
                    ->orWhere('is_completed', true)
                    ->orWhereIn('status', ['cancelled', 'rejected']);
            })
            ->orderByDesc('trip_date')
            ->orderByDesc('trip_time')
            ->limit(300)
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

        return view('drivers.show', compact('driver', 'analytics', 'activeTrips', 'pastTrips'));
    }

    public function update(UpdateDriverRequest $request, Driver $driver, AuditLogService $auditLog): RedirectResponse
    {
        $oldValues = $driver->getOriginal();
        $data = $request->validated();
        $data['updated_by_user_id'] = $request->user()?->id;
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

    public function restore(Driver $driver, AuditLogService $auditLog): RedirectResponse
    {
        if (! $driver->trashed()) {
            return redirect()
                ->route('drivers.index')
                ->with('error', 'Driver is already active.');
        }

        $driver->restore();
        $auditLog->log('driver.restored', $driver);

        return redirect()
            ->route('drivers.index', ['archived' => 1])
            ->with('success', 'Driver restored successfully.');
    }

    public function forceDelete(Driver $driver, AuditLogService $auditLog): RedirectResponse
    {
        $driver->forceDelete();
        $auditLog->log('driver.force_deleted', $driver);

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

        $duty = $this->driverDutyIds(Carbon::now());
        $assignedToday = array_fill_keys($duty['assigned_today'], true);
        $unavailable = array_fill_keys($duty['unavailable'], true);

        return response()->json([
            'data' => $drivers->map(function (Driver $driver) use ($assignedToday, $unavailable): array {
                $publicId = is_string($driver->uuid ?? null) && $driver->uuid !== '' ? $driver->uuid : (string) $driver->id;
                $status = strtolower((string) ($driver->status ?? ''));
                $isActive = $status === 'active';
                $id = (int) $driver->id;

                return [
                    'id' => $driver->id,
                    'public_id' => $publicId,
                    'full_name' => $driver->full_name,
                    'license_number' => $driver->license_number,
                    'license_expiry' => $driver->license_expiry?->format('M d, Y') ?? 'N/A',
                    'phone' => $driver->phone,
                    'status' => $driver->status,
                    'is_archived' => $driver->trashed(),
                    'duty_assigned_today' => isset($assignedToday[$id]),
                    // For "On Duty" metrics/filters, only ACTIVE drivers count as registered/available.
                    'duty_registered' => $isActive,
                    'duty_unassigned_today' => $isActive && !isset($unavailable[$id]),
                ];
            }),
        ]);
    }
}
