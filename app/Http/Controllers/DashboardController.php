<?php

namespace App\Http\Controllers;

use App\Models\IncidentReport;
use App\Models\TripRequest;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $metrics = $this->buildMetrics($user);

        return view('dashboard', [
            ...$metrics,
        ]);
    }

    public function metrics(Request $request): \Illuminate\Http\JsonResponse
    {
        $metrics = $this->buildMetrics($request->user());

        return response()->json($metrics);
    }

    private function buildMetrics(User $user): array
    {
        $role = $user->role;
        $branchId = $user->branch_id;

        $availableVehicles = Vehicle::where('status', 'available')->count();

        $personalTripRequests = null;
        $driversOnDuty = null;
        $tripsThisWeek = null;
        $pendingApproval = null;
        $incidentReports = null;
        $maintenanceDue = null;

        if (in_array($role, [User::ROLE_BRANCH_ADMIN, User::ROLE_BRANCH_HEAD], true)) {
            $personalTripRequests = TripRequest::where('requested_by_user_id', $user->id)
                ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
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
        }

        return [
            'availableVehicles' => $availableVehicles,
            'personalTripRequests' => $personalTripRequests,
            'driversOnDuty' => $driversOnDuty,
            'tripsThisWeek' => $tripsThisWeek,
            'pendingApproval' => $pendingApproval,
            'incidentReports' => $incidentReports,
            'maintenanceDue' => $maintenanceDue,
        ];
    }
}
