<?php

namespace App\Http\Controllers;

use App\Models\IncidentReport;
use App\Models\Driver;
use App\Models\TripRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAvailabilitySnapshot;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $metrics = $this->buildMetrics($user);
        $upcomingTrips = $this->buildUpcomingTrips($user);
        $calendarBounds = $this->calendarBounds();

        return view('dashboard', [
            ...$metrics,
            'upcomingTrips' => $upcomingTrips,
            'calendarMinYear' => $calendarBounds['min_year'],
            'calendarMaxYear' => $calendarBounds['max_year'],
        ]);
    }

    public function metrics(Request $request): \Illuminate\Http\JsonResponse
    {
        $metrics = $this->buildMetrics($request->user());

        return response()->json($metrics);
    }

    public function calendar(Request $request): JsonResponse
    {
        $data = $this->buildCalendar($request->user(), $request);

        return response()->json($data);
    }

    public function tripStatus(Request $request): JsonResponse
    {
        $data = $this->buildTripStatus($request->user());

        return response()->json($data);
    }

    public function upcomingTrips(Request $request): JsonResponse
    {
        $trips = $this->buildUpcomingTrips($request->user());

        $payload = $trips->map(function (TripRequest $trip): array {
            return [
                'request_number' => $trip->request_number,
                'trip_date' => $trip->trip_date?->format('M d') ?? '',
                'trip_day' => $trip->trip_date?->format('D') ?? '',
                'trip_time' => $trip->trip_time ? Carbon::createFromFormat('H:i', $trip->trip_time)->format('g:i A') : 'N/A',
                'destination' => $trip->destination ?? '',
                'status' => $trip->status ?? '',
                'vehicle' => $trip->assignedVehicle?->registration_number ?? '—',
                'vehicle_model' => $trip->assignedVehicle?->model ?? '',
                'driver' => $trip->assignedDriver?->full_name ?? '—',
            ];
        });

        return response()->json([
            'data' => $payload,
        ]);
    }

    private function buildMetrics(User $user): array
    {
        $now = Carbon::now();
        $role = $user->role;
        $branchId = $user->branch_id;

        $totalVehicles = Vehicle::count();
        $maintenanceVehicles = Vehicle::where('status', 'maintenance')->count();
        $assignedNow = TripRequest::whereNotNull('assigned_vehicle_id')
            ->whereIn('status', ['approved', 'assigned'])
            ->where(function ($query): void {
                $query->whereNull('is_completed')->orWhere('is_completed', false);
            })
            ->whereDate('trip_date', '<=', Carbon::today())
            ->distinct('assigned_vehicle_id')
            ->count('assigned_vehicle_id');
        $availableVehicles = max(0, $totalVehicles - $maintenanceVehicles - $assignedNow);

        $personalTripRequests = null;
        $branchTripRequests = null;
        $branchCompletedTrips = null;
        $branchRejectedTrips = null;
        $driversOnDuty = null;
        $driversAssignedToday = null;
        $driversUnassignedToday = null;
        $totalDriversRegistered = null;
        $tripsThisWeek = null;
        $pendingApproval = null;
        $incidentReports = null;
        $maintenanceDue = null;
        $todayActiveTrips = null;
        $futureTrips = null;
        $unassignedTrips = null;

        if ($role === User::ROLE_BRANCH_ADMIN) {
            $personalTripRequests = TripRequest::where('requested_by_user_id', $user->id)
                ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                ->count();
        }

        if ($role === User::ROLE_BRANCH_HEAD && $branchId) {
            $branchTripRequests = TripRequest::where('branch_id', $branchId)
                ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                ->count();

            $monthStart = Carbon::now()->startOfMonth();
            $monthEnd = Carbon::now()->endOfMonth();
            $branchCompletedTrips = TripRequest::where('branch_id', $branchId)
                ->whereBetween('trip_date', [$monthStart, $monthEnd])
                ->where('status', 'completed')
                ->count();

            $branchRejectedTrips = TripRequest::where('branch_id', $branchId)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->where('status', 'rejected')
                ->count();
        }

        if (in_array($role, [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER], true)) {
            $driversOnDuty = TripRequest::whereNotNull('assigned_driver_id')
                ->where('status', 'assigned')
                ->where(function ($query): void {
                    $query->whereNull('is_completed')->orWhere('is_completed', false);
                })
                ->distinct('assigned_driver_id')
                ->count('assigned_driver_id');

            $driversAssignedToday = TripRequest::whereDate('trip_date', Carbon::today())
                ->whereIn('status', ['approved', 'assigned'])
                ->whereNotNull('assigned_driver_id')
                ->where(function ($query): void {
                    $query->whereNull('is_completed')->orWhere('is_completed', false);
                })
                ->where(function ($query) use ($now): void {
                    $query->whereNull('trip_time')
                        ->orWhere('trip_time', '<=', $now->format('H:i'));
                })
                ->distinct('assigned_driver_id')
                ->count('assigned_driver_id');

            $totalDriversRegistered = Driver::where('status', '!=', 'suspended')->count();
            $driversUnavailableToday = TripRequest::whereIn('status', ['approved', 'assigned'])
                ->whereNotNull('assigned_driver_id')
                ->where(function ($query): void {
                    $query->whereNull('is_completed')->orWhere('is_completed', false);
                })
                ->where(function ($query) use ($now): void {
                    $query->whereDate('trip_date', '<', $now->toDateString())
                        ->orWhere(function ($sub) use ($now): void {
                            $sub->whereDate('trip_date', $now->toDateString())
                                ->where(function ($timeQuery) use ($now): void {
                                    $timeQuery->whereNull('trip_time')
                                        ->orWhere('trip_time', '<=', $now->format('H:i'));
                                });
                        });
                })
                ->distinct('assigned_driver_id')
                ->count('assigned_driver_id');

            $driversUnassignedToday = max(0, $totalDriversRegistered - $driversUnavailableToday);
        }

        if (in_array($role, [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER, User::ROLE_BRANCH_HEAD], true)) {
            $weekStart = Carbon::now()->startOfWeek();
            $weekEnd = Carbon::now()->endOfWeek();
            $tripsQuery = TripRequest::whereBetween('trip_date', [$weekStart, $weekEnd]);
            if ($role === User::ROLE_BRANCH_HEAD && $branchId) {
                $tripsQuery->where('branch_id', $branchId);
            }
            $tripsThisWeek = $tripsQuery->count();
        }

        if (in_array($role, [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER, User::ROLE_BRANCH_ADMIN, User::ROLE_BRANCH_HEAD], true)) {
            $pendingQuery = TripRequest::where('status', 'pending');
            if ($role === User::ROLE_BRANCH_ADMIN) {
                $pendingQuery->where('requested_by_user_id', $user->id);
            }
            if ($role === User::ROLE_BRANCH_HEAD && $branchId) {
                $pendingQuery->where('branch_id', $branchId);
            }
            $pendingApproval = $pendingQuery->count();
        }

        if (in_array($role, [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER], true)) {
            $incidentReports = IncidentReport::whereIn('status', [
                IncidentReport::STATUS_OPEN,
                IncidentReport::STATUS_REVIEW,
            ])->count();

            $dueDate = Carbon::now()->addDays(30);
            $maintenanceDue = Vehicle::where(function ($query) use ($dueDate): void {
                $query->whereDate('insurance_expiry', '<=', $dueDate)
                    ->orWhereDate('registration_expiry', '<=', $dueDate);
            })->count();

            $todayActiveTrips = TripRequest::whereDate('trip_date', Carbon::today())
                ->whereIn('status', ['approved', 'assigned'])
                ->whereNotNull('assigned_vehicle_id')
                ->whereNotNull('assigned_driver_id')
                ->where(function ($query): void {
                    $query->whereNull('is_completed')->orWhere('is_completed', false);
                })
                ->count();

            $futureTrips = TripRequest::whereDate('trip_date', '>', Carbon::today())
                ->whereIn('status', ['approved', 'assigned'])
                ->where(function ($query): void {
                    $query->whereNull('is_completed')->orWhere('is_completed', false);
                })
                ->count();

            $unassignedTrips = TripRequest::whereDate('trip_date', '>=', Carbon::today())
                ->whereIn('status', ['approved', 'assigned'])
                ->where(function ($query): void {
                    $query->whereNull('is_completed')->orWhere('is_completed', false);
                })
                ->where(function ($query): void {
                    $query->whereNull('assigned_vehicle_id')
                        ->orWhereNull('assigned_driver_id');
                })
                ->count();
        }

        return [
            'availableVehicles' => $availableVehicles,
            'totalVehicles' => $totalVehicles,
            'personalTripRequests' => $personalTripRequests,
            'branchTripRequests' => $branchTripRequests,
            'branchCompletedTrips' => $branchCompletedTrips,
            'branchRejectedTrips' => $branchRejectedTrips,
            'driversOnDuty' => $driversOnDuty,
            'driversAssignedToday' => $driversAssignedToday,
            'driversUnassignedToday' => $driversUnassignedToday,
            'totalDriversRegistered' => $totalDriversRegistered,
            'tripsThisWeek' => $tripsThisWeek,
            'pendingApproval' => $pendingApproval,
            'incidentReports' => $incidentReports,
            'maintenanceDue' => $maintenanceDue,
            'todayActiveTrips' => $todayActiveTrips,
            'futureTrips' => $futureTrips,
            'unassignedTrips' => $unassignedTrips,
        ];
    }

    private function buildCalendar(User $user, Request $request): array
    {
        $bounds = $this->calendarBounds();
        $now = $bounds['now'];
        $earliestDate = $bounds['earliest_date'];
        $earliestMonth = $bounds['earliest_month'];
        $year = (int) $request->query('year', $now->year);
        $month = (int) $request->query('month', $now->month);

        $minYear = $bounds['min_year'];
        $maxYear = $bounds['max_year'];
        $year = min(max($year, $minYear), $maxYear);
        $month = min(max($month, 1), 12);

        if ($year === $minYear && $month < $earliestMonth->month) {
            $month = $earliestMonth->month;
        }

        $selected = Carbon::createFromDate($year, $month, 1);
        $monthStart = $selected->copy()->startOfMonth();
        $monthEnd = $selected->copy()->endOfMonth();
        $windowEnd = $now->copy()->addDays(6);

        $totalVehicles = Vehicle::count();
        $maintenanceVehicles = Vehicle::where('status', 'maintenance')->count();
        $baseAvailable = max(0, $totalVehicles - $maintenanceVehicles);

        $activeAssignedVehicles = TripRequest::whereNotNull('assigned_vehicle_id')
            ->whereIn('status', ['approved', 'assigned'])
            ->where(function ($query): void {
                $query->whereNull('is_completed')->orWhere('is_completed', false);
            })
            ->distinct('assigned_vehicle_id')
            ->count('assigned_vehicle_id');

        $calendarStart = $monthStart->copy()->subDays(7);
        $calendarEnd = $monthEnd->copy();

        $assignedTrips = TripRequest::whereNotNull('assigned_vehicle_id')
            ->whereIn('status', ['approved', 'assigned'])
            ->where(function ($query): void {
                $query->whereNull('is_completed')->orWhere('is_completed', false);
            })
            ->whereBetween('trip_date', [$calendarStart, $calendarEnd])
            ->get()
            ->filter(fn (TripRequest $trip): bool => ! empty($trip->trip_date));

        $assignedByDate = $assignedTrips
            ->groupBy(fn (TripRequest $trip) => $trip->trip_date?->toDateString() ?? '')
            ->map(fn ($group): int => $group->pluck('assigned_vehicle_id')->unique()->count());

        $windowAssigned = [];
        foreach ($assignedTrips as $trip) {
            $tripMoment = Carbon::parse($trip->trip_date);
            if ($tripMoment->gt($now->copy()->subDay())) {
                continue;
            }
            $tripDate = $tripMoment->copy()->startOfDay();
            $windowEndDate = $tripDate->copy()->addDays(7);
            $rangeStart = $tripDate->greaterThan($monthStart) ? $tripDate : $monthStart->copy();
            $rangeEnd = $windowEndDate->lessThan($monthEnd) ? $windowEndDate : $monthEnd->copy();

            $cursor = $rangeStart->copy();
            while ($cursor->lte($rangeEnd)) {
                $key = $cursor->toDateString();
                $windowAssigned[$key] ??= [];
                $windowAssigned[$key][$trip->assigned_vehicle_id] = true;
                $cursor->addDay();
            }
        }

        $snapshots = VehicleAvailabilitySnapshot::whereBetween('snapshot_date', [$monthStart, $monthEnd])
            ->get()
            ->keyBy(fn (VehicleAvailabilitySnapshot $snapshot) => $snapshot->snapshot_date->toDateString());

        $days = [];
        $cursor = $monthStart->copy();
        while ($cursor->lte($monthEnd)) {
            $dateKey = $cursor->toDateString();
            $snapshot = $snapshots->get($dateKey);

            if ($cursor->lt($earliestDate)) {
                $available = null;
            } elseif ($snapshot && $cursor->isBefore($now->copy()->startOfDay())) {
                $available = $snapshot->available_vehicles;
            } else {
                $assignedOnDate = (int) ($assignedByDate->get($dateKey) ?? 0);
                $windowAssignedCount = isset($windowAssigned[$dateKey]) ? count($windowAssigned[$dateKey]) : 0;
                $assignedCount = max($assignedOnDate, $windowAssignedCount);
                $available = max(0, $baseAvailable - $assignedCount);
            }
            $days[] = [
                'date' => $dateKey,
                'available' => $available,
            ];
            $cursor->addDay();
        }

        return [
            'month' => $selected->format('F'),
            'year' => $selected->year,
            'month_number' => $selected->month,
            'min_year' => $minYear,
            'max_year' => $maxYear,
            'days' => $days,
            'window_days' => $now->diffInDays($windowEnd) + 1,
            'max_available' => $baseAvailable,
        ];
    }

    private function calendarBounds(): array
    {
        $now = Carbon::now();
        $earliestTripDate = TripRequest::whereNotNull('trip_date')->min('trip_date');
        $earliestDate = $earliestTripDate
            ? Carbon::parse($earliestTripDate)->startOfDay()
            : $now->copy()->startOfDay();
        $earliestMonth = $earliestDate->copy()->startOfMonth();

        return [
            'now' => $now,
            'earliest_date' => $earliestDate,
            'earliest_month' => $earliestMonth,
            'min_year' => $earliestMonth->year,
            'max_year' => $now->year + 1,
        ];
    }

    private function buildTripStatus(User $user): array
    {
        $role = $user->role;
        $branchId = $user->branch_id;
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $query = TripRequest::query();
        if ($role === User::ROLE_BRANCH_ADMIN) {
            $query->where('requested_by_user_id', $user->id);
        }
        if ($role === User::ROLE_BRANCH_HEAD && $branchId) {
            $query->where('branch_id', $branchId);
        }
        $query->whereBetween('trip_date', [$monthStart, $monthEnd]);

        return [
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'assigned' => (clone $query)->where('status', 'assigned')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
        ];
    }

    private function buildUpcomingTrips(User $user)
    {
        if (! in_array($user->role, [User::ROLE_BRANCH_ADMIN, User::ROLE_BRANCH_HEAD], true)) {
            return collect();
        }

        $query = TripRequest::query()
            ->whereDate('trip_date', '>=', Carbon::today())
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->orderBy('trip_date')
            ->with(['assignedVehicle', 'assignedDriver']);

        if ($user->role === User::ROLE_BRANCH_ADMIN) {
            $query->where('requested_by_user_id', $user->id);
        }

        if ($user->role === User::ROLE_BRANCH_HEAD && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query->limit(100)->get();
    }
}
