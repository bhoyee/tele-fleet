<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trip\AssignTripRequest;
use App\Http\Requests\Trip\LogTripRequest;
use App\Http\Requests\Trip\StoreTripRequest;
use App\Models\Branch;
use App\Models\Driver;
use App\Models\TripLog;
use App\Models\TripRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Events\TripRequestChanged;
use App\Notifications\TripRequestApproved;
use App\Notifications\TripRequestAssigned;
use App\Notifications\TripRequestCreated;
use App\Notifications\TripRequestCancelled;
use App\Notifications\TripRequestRejected;
use App\Services\AuditLogService;
use App\Services\SmsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\JsonResponse;
use Throwable;

class TripRequestController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $showArchived = $request->boolean('archived') && $user->role === User::ROLE_SUPER_ADMIN;

        $query = TripRequest::with([
            'branch',
            'requestedBy',
            'approvedBy',
            'assignedVehicle',
            'assignedDriver',
        ])->orderByDesc('created_at');

        if ($showArchived) {
            $query->onlyTrashed();
        } elseif (in_array($user->role, [User::ROLE_BRANCH_ADMIN, User::ROLE_BRANCH_HEAD], true)) {
            $query->where('branch_id', $user->branch_id);
        }

        $trips = $query->get();

        $analytics = null;
        $historyTrips = collect();
        if ($user->role === User::ROLE_SUPER_ADMIN) {
            $now = Carbon::now();
            $monthStart = $now->copy()->startOfMonth();
            $monthEnd = $now->copy()->endOfMonth();

            $monthlyQuery = TripRequest::whereBetween('trip_date', [$monthStart, $monthEnd]);
            $totalTrips = (clone $monthlyQuery)->count();
            $allTimeTrips = TripRequest::count();
            $pendingTrips = (clone $monthlyQuery)->where('status', 'pending')->count();
            $approvedTrips = (clone $monthlyQuery)->whereIn('status', ['approved', 'assigned', 'completed'])->count();
            $assignedTrips = (clone $monthlyQuery)->where('status', 'assigned')->count();
            $completedTrips = (clone $monthlyQuery)->where('status', 'completed')->count();
            $rejectedTrips = (clone $monthlyQuery)->where('status', 'rejected')->count();
            $cancelledTrips = (clone $monthlyQuery)->where('status', 'cancelled')->count();

            $approvalRate = $totalTrips > 0 ? round(($approvedTrips / $totalTrips) * 100, 1) : 0;
            $completionRate = $totalTrips > 0 ? round(($completedTrips / $totalTrips) * 100, 1) : 0;

            $analytics = [
                'total' => $totalTrips,
                'all_time' => $allTimeTrips,
                'pending' => $pendingTrips,
                'approved' => $approvedTrips,
                'assigned' => $assignedTrips,
                'completed' => $completedTrips,
                'rejected' => $rejectedTrips,
                'cancelled' => $cancelledTrips,
                'approval_rate' => $approvalRate,
                'completion_rate' => $completionRate,
                'range_label' => $monthStart->format('M Y'),
            ];

            $historyTrips = TripRequest::with(['branch', 'requestedBy'])
                ->whereIn('status', ['completed', 'cancelled', 'rejected'])
                ->orderByDesc('trip_date')
                ->limit(30)
                ->get();
        }

        return view('trips.index', compact('trips', 'showArchived', 'analytics', 'historyTrips'));
    }

    public function indexData(Request $request): JsonResponse
    {
        $user = $request->user();
        $showArchived = $request->boolean('archived') && $user->role === User::ROLE_SUPER_ADMIN;

        $query = TripRequest::query()->orderByDesc('created_at');

        if ($showArchived) {
            $query->onlyTrashed();
        } elseif (in_array($user->role, [User::ROLE_BRANCH_ADMIN, User::ROLE_BRANCH_HEAD], true)) {
            $query->where('branch_id', $user->branch_id);
        }

        $trips = $query->get();

        $payload = $trips->map(function (TripRequest $trip): array {
            return [
                'id' => $trip->id,
                'branch_id' => $trip->branch_id,
                'requested_by_user_id' => $trip->requested_by_user_id,
                'request_number' => $trip->request_number,
                'purpose' => $trip->purpose,
                'trip_date' => $trip->trip_date?->format('M d, Y') ?? '',
                'trip_time' => $trip->trip_time ? Carbon::createFromFormat('H:i', $trip->trip_time)->format('g:i A') : 'N/A',
                'trip_date_raw' => $trip->trip_date?->format('Y-m-d') ?? '',
                'trip_time_raw' => $trip->trip_time ?? null,
                'status' => $trip->status,
                'assigned' => (bool) ($trip->assigned_vehicle_id && $trip->assigned_driver_id),
                'due_status' => $trip->dueStatus(),
                'is_archived' => $trip->trashed(),
            ];
        });

        return response()->json([
            'data' => $payload,
        ]);
    }

    public function myRequests(Request $request): View
    {
        $trips = TripRequest::with(['branch'])
            ->where('requested_by_user_id', $request->user()->id)
            ->latest()
            ->get();

        return view('trips.my-requests', compact('trips'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $branches = in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER], true)
            ? Branch::orderBy('name')->get()
            : collect();

        return view('trips.create', compact('branches', 'user'));
    }

    public function store(StoreTripRequest $request, AuditLogService $auditLog): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $branchId = $user->branch_id ?? $data['branch_id'] ?? null;
        if (! $branchId) {
            return redirect()
                ->back()
                ->withErrors(['branch_id' => 'Branch is required for this request.'])
                ->withInput();
        }

        $tripRequest = TripRequest::create([
            'request_number' => $this->generateRequestNumber(),
            'branch_id' => $branchId,
            'requested_by_user_id' => $user->id,
            'purpose' => $data['purpose'],
            'destination' => $data['destination'],
            'trip_date' => $data['trip_date'],
            'trip_time' => $data['trip_time'] ?? null,
            'estimated_distance_km' => $data['estimated_distance_km'] ?? null,
            'number_of_passengers' => $data['number_of_passengers'] ?? 1,
            'additional_notes' => $data['additional_notes'] ?? null,
            'status' => 'pending',
        ]);

        $auditLog->log('trip_request.created', $tripRequest, [], $tripRequest->toArray());

        $recipients = $this->buildNotificationRecipients($tripRequest);
        try {
            Notification::send($recipients, new TripRequestCreated($tripRequest));
        } catch (Throwable $exception) {
            Log::warning('Trip request create notification failed.', [
                'trip_request_id' => $tripRequest->id,
                'error' => $exception->getMessage(),
            ]);
        }
        $this->broadcastTripChange($tripRequest, 'created');

        return redirect()
            ->route('trips.show', $tripRequest)
            ->with('success', 'Trip request submitted successfully.');
    }

    public function show(TripRequest $tripRequest): View
    {
        $this->authorizeTripView(request()->user(), $tripRequest);
        $tripRequest->load([
            'branch',
            'requestedBy',
            'approvedBy',
            'assignedVehicle',
            'assignedDriver',
            'log.enteredBy',
            'log.editedBy',
            'updatedBy',
        ]);

        $vehicles = collect();
        $drivers = collect();

        if (in_array(auth()->user()?->role, [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER], true)) {
            $vehicles = $this->availableVehiclesNow();
            $drivers = Driver::where('status', 'active')
                ->orderBy('full_name')
                ->get();
        }

        return view('trips.show', compact('tripRequest', 'vehicles', 'drivers'));
    }

    public function cancel(Request $request, TripRequest $tripRequest, AuditLogService $auditLog): RedirectResponse
    {
        $this->authorizeTripMutation($request->user(), $tripRequest);

        if (! $this->canCancelTrip($tripRequest)) {
            return redirect()
                ->route('trips.show', $tripRequest)
                ->with('error', 'This trip can no longer be cancelled.');
        }

        if ($tripRequest->assignedVehicle) {
            if ($tripRequest->assignedVehicle->status === 'in_use') {
                $tripRequest->assignedVehicle->update(['status' => 'available']);
            }
        }


        $tripRequest->update([
            'status' => 'cancelled',
            'assigned_vehicle_id' => null,
            'assigned_driver_id' => null,
            'assigned_at' => null,
            'updated_by_user_id' => $request->user()->id,
        ]);

        $auditLog->log('trip_request.cancelled', $tripRequest, [], $tripRequest->toArray());
        $this->broadcastTripChange($tripRequest, 'cancelled');

        $tripRequest->load(['requestedBy']);
        $recipients = $this->buildCancellationRecipients($tripRequest);
        try {
            Notification::send($recipients, new TripRequestCancelled($tripRequest, $request->user()));
        } catch (Throwable $exception) {
            Log::warning('Trip request cancellation notification failed.', [
                'trip_request_id' => $tripRequest->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('trips.index')
            ->with('success', 'Trip cancelled.');
    }

    public function approve(TripRequest $tripRequest, AuditLogService $auditLog): RedirectResponse
    {
        $tripRequest->update([
            'status' => 'approved',
            'approved_by_user_id' => request()->user()->id,
            'approved_at' => now(),
            'rejection_reason' => null,
            'updated_by_user_id' => request()->user()->id,
        ]);

        $auditLog->log('trip_request.approved', $tripRequest, [], $tripRequest->toArray());

        $recipients = $this->buildNotificationRecipients($tripRequest, $tripRequest->requestedBy);
        try {
            Notification::send($recipients, new TripRequestApproved($tripRequest));
        } catch (Throwable $exception) {
            Log::warning('Trip request approval notification failed.', [
                'trip_request_id' => $tripRequest->id,
                'error' => $exception->getMessage(),
            ]);
        }
        $this->broadcastTripChange($tripRequest, 'approved');

        return redirect()
            ->route('trips.show', $tripRequest)
            ->with('success', 'Trip request approved.');
    }

    public function reject(Request $request, TripRequest $tripRequest, AuditLogService $auditLog): RedirectResponse
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $tripRequest->update([
            'status' => 'rejected',
            'approved_by_user_id' => request()->user()->id,
            'approved_at' => now(),
            'rejection_reason' => $request->rejection_reason,
            'updated_by_user_id' => request()->user()->id,
        ]);

        $auditLog->log('trip_request.rejected', $tripRequest, [], $tripRequest->toArray());

        if ($tripRequest->requestedBy) {
            try {
                $tripRequest->requestedBy->notify(new TripRequestRejected($tripRequest));
            } catch (Throwable $exception) {
                Log::warning('Trip request rejection notification failed.', [
                    'trip_request_id' => $tripRequest->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
        $this->broadcastTripChange($tripRequest, 'rejected');

        return redirect()
            ->route('trips.show', $tripRequest)
            ->with('success', 'Trip request rejected.');
    }

    public function assign(AssignTripRequest $request, TripRequest $tripRequest, AuditLogService $auditLog, SmsService $sms): RedirectResponse
    {
        $vehicle = Vehicle::findOrFail($request->assigned_vehicle_id);
        $driver = Driver::findOrFail($request->assigned_driver_id);

        if (in_array($vehicle->status, ['maintenance', 'offline'], true)) {
            return redirect()
                ->back()
                ->withErrors(['assigned_vehicle_id' => 'Selected vehicle is not available.'])
                ->withInput();
        }

        if ($driver->status !== 'active') {
            return redirect()
                ->back()
                ->withErrors(['assigned_driver_id' => 'Selected driver is not available.'])
                ->withInput();
        }

        if (! $this->isVehicleAvailableNow($vehicle->id)) {
            return redirect()
                ->back()
                ->withErrors(['assigned_vehicle_id' => 'Selected vehicle is currently in use.'])
                ->withInput();
        }

        $tripRequest->update([
            'status' => 'assigned',
            'assigned_vehicle_id' => $request->assigned_vehicle_id,
            'assigned_driver_id' => $request->assigned_driver_id,
            'assigned_at' => now(),
            'requires_reassignment' => false,
            'assignment_conflict_reason' => null,
            'assignment_conflict_at' => null,
            'updated_by_user_id' => request()->user()->id,
        ]);

        if ($this->tripHasStarted($tripRequest)) {
            $vehicle->update(['status' => 'in_use']);
        }

        $auditLog->log('trip_request.assigned', $tripRequest, [], $tripRequest->toArray());

        $tripRequest->load(['assignedVehicle', 'assignedDriver', 'requestedBy']);
        $recipients = $this->buildNotificationRecipients($tripRequest, $tripRequest->requestedBy);
        try {
            Notification::send($recipients, new TripRequestAssigned($tripRequest));
        } catch (Throwable $exception) {
            Log::warning('Trip request assignment notification failed.', [
                'trip_request_id' => $tripRequest->id,
                'error' => $exception->getMessage(),
            ]);
        }

        if ($tripRequest->assignedDriver?->phone) {
            $sms->send($tripRequest->assignedDriver->phone, sprintf(
                'Trip %s assigned. Vehicle %s. Destination: %s. Date: %s.',
                $tripRequest->request_number,
                $tripRequest->assignedVehicle?->registration_number ?? 'N/A',
                $tripRequest->destination,
                $tripRequest->trip_date?->format('Y-m-d') ?? ''
            ));
        }
        $this->broadcastTripChange($tripRequest, 'assigned');

        return redirect()
            ->route('trips.show', $tripRequest)
            ->with('success', 'Vehicle and driver assigned.');
    }

    public function logbook(TripRequest $tripRequest): View
    {
        $tripRequest->load(['assignedDriver', 'log']);

        return view('trips.logbook', compact('tripRequest'));
    }

    public function logbookIndex(): View
    {
        $trips = TripRequest::with(['branch', 'assignedVehicle', 'assignedDriver', 'log.enteredBy', 'log.editedBy'])
            ->whereIn('status', ['assigned', 'completed'])
            ->latest()
            ->get();

        return view('trips.logbook-index', compact('trips'));
    }

    public function manageLogbooks(Request $request): View
    {
        $showArchived = $request->boolean('archived') && $request->user()?->role === User::ROLE_SUPER_ADMIN;

        $query = TripLog::with([
            'tripRequest.branch',
            'tripRequest.assignedVehicle',
            'tripRequest.assignedDriver',
            'enteredBy',
            'editedBy',
        ])->orderByDesc('log_date');

        if ($showArchived) {
            $query->onlyTrashed();
        }

        $logs = $query->get();

        return view('trips.logbook-manage', compact('logs', 'showArchived'));
    }

    public function showLogbook(Request $request, int $tripLog): View
    {
        $query = TripLog::with([
            'tripRequest.assignedDriver',
            'tripRequest.assignedVehicle',
        ]);

        if ($request->user()?->role === User::ROLE_SUPER_ADMIN) {
            $query->withTrashed();
        }

        $log = $query->findOrFail($tripLog);
        $tripRequest = $log->tripRequest;
        $tripRequest->setRelation('log', $log);

        $backUrl = route('logbooks.manage', $request->boolean('archived') ? ['archived' => 1] : []);

        return view('trips.logbook', [
            'tripRequest' => $tripRequest,
            'viewOnly' => true,
            'backUrl' => $backUrl,
        ]);
    }

    public function edit(TripRequest $tripRequest): View
    {
        $this->authorizeTripMutation(request()->user(), $tripRequest);

        if ($this->isBranchUserRestricted(request()->user(), $tripRequest)) {
            return redirect()
                ->route('trips.show', $tripRequest)
                ->with('error', 'This trip can only be edited before approval or after rejection.');
        }

        if ($tripRequest->status === 'completed') {
            return redirect()
                ->route('trips.show', $tripRequest)
                ->with('error', 'Completed trips cannot be edited.');
        }

        $branches = Branch::orderBy('name')->get();

        return view('trips.edit', compact('tripRequest', 'branches'));
    }

    public function update(StoreTripRequest $request, TripRequest $tripRequest, AuditLogService $auditLog): RedirectResponse
    {
        $this->authorizeTripMutation($request->user(), $tripRequest);

        if ($this->isBranchUserRestricted($request->user(), $tripRequest)) {
            return redirect()
                ->route('trips.show', $tripRequest)
                ->with('error', 'This trip can only be edited before approval or after rejection.');
        }

        if ($tripRequest->status === 'completed') {
            return redirect()
                ->route('trips.show', $tripRequest)
                ->with('error', 'Completed trips cannot be edited.');
        }

        $data = $request->validated();

        $tripRequest->update(array_merge($data, [
            'trip_time' => $data['trip_time'] ?? null,
            'updated_by_user_id' => $request->user()->id,
        ]));

        $auditLog->log('trip_request.updated', $tripRequest, [], $data);
        $this->broadcastTripChange($tripRequest, 'updated');

        return redirect()
            ->route('trips.show', $tripRequest)
            ->with('success', 'Trip updated successfully.');
    }

    public function editLogbook(TripRequest $tripRequest): View
    {
        $tripRequest->load(['assignedDriver', 'log']);

        if (! $tripRequest->log) {
            return redirect()
                ->route('trips.logbook', $tripRequest)
                ->with('error', 'No logbook found for this trip yet.');
        }

        return view('trips.logbook', compact('tripRequest'));
    }

    public function updateLogbook(LogTripRequest $request, TripRequest $tripRequest, AuditLogService $auditLog): RedirectResponse
    {
        $tripRequest->load(['log']);

        if (! $tripRequest->log) {
            return redirect()
                ->route('trips.logbook', $tripRequest)
                ->with('error', 'No logbook found for this trip yet.');
        }

        $data = $request->validated();

        $distance = $data['end_mileage'] - $data['start_mileage'];
        $fuelConsumed = null;
        if ($data['fuel_before_trip'] !== null && $data['fuel_after_trip'] !== null) {
            $fuelConsumed = max(0, $data['fuel_before_trip'] - $data['fuel_after_trip']);
        }

        $durationHours = null;
        if (! empty($data['actual_start_time']) && ! empty($data['actual_end_time'])) {
            $start = Carbon::parse($data['actual_start_time']);
            $end = Carbon::parse($data['actual_end_time']);
            $durationHours = round($start->diffInMinutes($end) / 60, 2);
        }

        $tripRequest->log->update([
            'start_mileage' => $data['start_mileage'],
            'end_mileage' => $data['end_mileage'],
            'distance_traveled' => $distance,
            'fuel_before_trip' => $data['fuel_before_trip'] ?? null,
            'fuel_after_trip' => $data['fuel_after_trip'] ?? null,
            'fuel_consumed' => $fuelConsumed,
            'actual_start_time' => $data['actual_start_time'] ?? null,
            'actual_end_time' => $data['actual_end_time'] ?? null,
            'trip_duration_hours' => $durationHours,
            'driver_name' => $data['driver_name'],
            'driver_license_number' => $data['driver_license_number'],
            'paper_logbook_ref_number' => $data['paper_logbook_ref_number'] ?? null,
            'driver_notes' => $data['driver_notes'] ?? null,
            'entered_by_user_id' => $tripRequest->log->entered_by_user_id,
            'edited_by_user_id' => $request->user()->id,
            'log_date' => $data['log_date'],
            'remarks' => $data['remarks'] ?? null,
        ]);

        $tripRequest->update([
            'status' => 'completed',
            'is_completed' => true,
            'logbook_entered_by' => $request->user()->id,
            'logbook_entered_at' => now(),
            'updated_by_user_id' => $request->user()->id,
        ]);

        $auditLog->log('trip_request.logbook_updated', $tripRequest, [], [
            'trip_log_id' => $tripRequest->log->id,
        ]);
        $this->broadcastTripChange($tripRequest, 'completed');

        return redirect()
            ->route('trips.show', $tripRequest)
            ->with('success', 'Trip logbook updated.');
    }

    public function destroy(TripRequest $tripRequest, AuditLogService $auditLog): RedirectResponse
    {
        $this->authorizeTripMutation(request()->user(), $tripRequest);

        $tripRequest->load('log');
        $tripRequest->update([
            'updated_by_user_id' => request()->user()->id,
        ]);
        $oldValues = $tripRequest->toArray();

        if ($tripRequest->log) {
            $tripRequest->log->delete();
        }

        $tripRequest->delete();

        $auditLog->log('trip_request.deleted', $tripRequest, $oldValues, [
            'trip_request_id' => $tripRequest->id,
        ]);
        $this->broadcastTripChangeData($tripRequest->id, $tripRequest->branch_id, $tripRequest->requested_by_user_id, 'deleted');

        return redirect()
            ->route('trips.index')
            ->with('success', 'Trip deleted.');
    }

    public function restore(int $tripRequest, AuditLogService $auditLog): RedirectResponse
    {
        $trip = TripRequest::onlyTrashed()->findOrFail($tripRequest);

        $trip->restore();
        $auditLog->log('trip_request.restored', $trip, [], $trip->toArray());

        return redirect()
            ->route('trips.index', ['archived' => 1])
            ->with('success', 'Trip restored.');
    }

    public function forceDelete(int $tripRequest, AuditLogService $auditLog): RedirectResponse
    {
        $trip = TripRequest::onlyTrashed()->findOrFail($tripRequest);

        $auditLog->log('trip_request.force_deleted', $trip, [], $trip->toArray());
        $trip->forceDelete();

        return redirect()
            ->route('trips.index', ['archived' => 1])
            ->with('success', 'Trip permanently deleted.');
    }

    private function authorizeTripMutation(?User $user, TripRequest $tripRequest): void
    {
        if (! $user) {
            abort(403);
        }

        if (in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER], true)) {
            return;
        }

        if ($user->role === User::ROLE_BRANCH_ADMIN && $tripRequest->requested_by_user_id === $user->id) {
            return;
        }

        if ($user->role === User::ROLE_BRANCH_HEAD && $user->branch_id && $tripRequest->branch_id === $user->branch_id) {
            return;
        }

        abort(403);
    }

    private function authorizeTripView(?User $user, TripRequest $tripRequest): void
    {
        if (! $user) {
            abort(403);
        }

        if (in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER], true)) {
            return;
        }

        if ($user->role === User::ROLE_BRANCH_HEAD && $user->branch_id && $tripRequest->branch_id === $user->branch_id) {
            return;
        }

        if ($user->role === User::ROLE_BRANCH_ADMIN && $tripRequest->requested_by_user_id === $user->id) {
            return;
        }

        abort(403);
    }

    private function isBranchUserRestricted(?User $user, TripRequest $tripRequest): bool
    {
        if (! $user) {
            return true;
        }

        if (in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER], true)) {
            return false;
        }

        return $tripRequest->status !== 'pending';
    }

    public function destroyLogbook(Request $request, TripRequest $tripRequest, AuditLogService $auditLog): RedirectResponse
    {
        $tripRequest->load('log');

        if (! $tripRequest->log) {
            return redirect()
                ->route('logbooks.index')
                ->with('error', 'No logbook found to delete.');
        }

        $this->archiveLogEntry($tripRequest->log, $request, $auditLog);

        return redirect()
            ->route('logbooks.index')
            ->with('success', 'Logbook archived.');
    }

    public function archiveLogbook(Request $request, int $tripLog, AuditLogService $auditLog): RedirectResponse
    {
        $log = TripLog::with('tripRequest')->findOrFail($tripLog);

        $this->archiveLogEntry($log, $request, $auditLog);

        return redirect()
            ->route('logbooks.manage')
            ->with('success', 'Logbook archived.');
    }

    public function restoreLogbook(Request $request, int $tripLog, AuditLogService $auditLog): RedirectResponse
    {
        $log = TripLog::withTrashed()->with('tripRequest')->findOrFail($tripLog);

        if (! $log->trashed()) {
            return redirect()
                ->route('logbooks.manage')
                ->with('error', 'Logbook is already active.');
        }

        $log->restore();

        $tripRequest = $log->tripRequest;
        if ($tripRequest) {
            $tripRequest->update([
                'status' => 'completed',
                'is_completed' => true,
                'logbook_entered_by' => $log->entered_by_user_id ?? $request->user()->id,
                'logbook_entered_at' => $log->created_at ?? now(),
                'updated_by_user_id' => $request->user()->id,
            ]);
        }

        $auditLog->log('trip_request.logbook_restored', $tripRequest, [], [
            'trip_log_id' => $log->id,
        ]);
        $this->broadcastTripChange($tripRequest, 'logbook_restored');

        return redirect()
            ->route('logbooks.manage', ['archived' => 1])
            ->with('success', 'Logbook restored.');
    }

    public function forceDeleteLogbook(Request $request, int $tripLog, AuditLogService $auditLog): RedirectResponse
    {
        $log = TripLog::withTrashed()->with('tripRequest')->findOrFail($tripLog);
        $tripRequest = $log->tripRequest;
        $logId = $log->id;

        if ($tripRequest) {
            $this->resetTripAfterLogRemoval($tripRequest, $request);
        }

        $log->forceDelete();

        $auditLog->log('trip_request.logbook_deleted_permanently', $tripRequest, [], [
            'trip_log_id' => $logId,
        ]);
        $this->broadcastTripChange($tripRequest, 'logbook_deleted');

        return redirect()
            ->route('logbooks.manage', ['archived' => 1])
            ->with('success', 'Logbook deleted permanently.');
    }

    public function storeLogbook(LogTripRequest $request, TripRequest $tripRequest, AuditLogService $auditLog): RedirectResponse
    {
        $data = $request->validated();

        $distance = $data['end_mileage'] - $data['start_mileage'];
        $fuelConsumed = null;
        if ($data['fuel_before_trip'] !== null && $data['fuel_after_trip'] !== null) {
            $fuelConsumed = max(0, $data['fuel_before_trip'] - $data['fuel_after_trip']);
        }

        $durationHours = null;
        if (! empty($data['actual_start_time']) && ! empty($data['actual_end_time'])) {
            $start = Carbon::parse($data['actual_start_time']);
            $end = Carbon::parse($data['actual_end_time']);
            $durationHours = round($start->diffInMinutes($end) / 60, 2);
        }

        $tripLog = TripLog::create([
            'trip_request_id' => $tripRequest->id,
            'start_mileage' => $data['start_mileage'],
            'end_mileage' => $data['end_mileage'],
            'distance_traveled' => $distance,
            'fuel_before_trip' => $data['fuel_before_trip'] ?? null,
            'fuel_after_trip' => $data['fuel_after_trip'] ?? null,
            'fuel_consumed' => $fuelConsumed,
            'actual_start_time' => $data['actual_start_time'] ?? null,
            'actual_end_time' => $data['actual_end_time'] ?? null,
            'trip_duration_hours' => $durationHours,
            'driver_name' => $data['driver_name'],
            'driver_license_number' => $data['driver_license_number'],
            'paper_logbook_ref_number' => $data['paper_logbook_ref_number'] ?? null,
            'driver_notes' => $data['driver_notes'] ?? null,
            'entered_by_user_id' => $request->user()->id,
            'log_date' => $data['log_date'],
            'remarks' => $data['remarks'] ?? null,
        ]);

        $tripRequest->update([
            'status' => 'completed',
            'is_completed' => true,
            'logbook_entered_by' => $request->user()->id,
            'logbook_entered_at' => now(),
            'updated_by_user_id' => $request->user()->id,
        ]);

        $tripRequest->load(['assignedVehicle', 'assignedDriver']);

        if ($tripRequest->assignedVehicle) {
            if ($tripRequest->assignedVehicle->status === 'in_use') {
                $tripRequest->assignedVehicle->update(['status' => 'available']);
            }
        }


        $auditLog->log('trip_request.logbook_entered', $tripRequest, [], [
            'trip_log_id' => $tripLog->id,
        ]);
        $this->broadcastTripChange($tripRequest, 'completed');

        return redirect()
            ->route('trips.show', $tripRequest)
            ->with('success', 'Trip logbook saved.');
    }

    public function assignmentForm(TripRequest $tripRequest): View
    {
        $vehicles = $this->availableVehiclesNow();
        $drivers = Driver::where('status', 'active')->orderBy('full_name')->get();

        return view('trips.assign', compact('tripRequest', 'vehicles', 'drivers'));
    }

    private function generateRequestNumber(): string
    {
        $today = now()->format('Ymd');
        $count = TripRequest::whereDate('created_at', now()->toDateString())->count() + 1;

        return sprintf('TR-%s-%03d', $today, $count);
    }

    private function availableVehiclesNow()
    {
        $activeAssignedIds = $this->activeAssignedVehicleIds();

        return Vehicle::where('status', 'available')
            ->when($activeAssignedIds->isNotEmpty(), function ($query) use ($activeAssignedIds): void {
                $query->whereNotIn('id', $activeAssignedIds);
            })
            ->orderBy('registration_number')
            ->get();
    }

    private function isVehicleAvailableNow(int $vehicleId): bool
    {
        return ! $this->activeAssignedVehicleIds()->contains($vehicleId);
    }

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

    private function tripHasStarted(TripRequest $tripRequest): bool
    {
        if (! $tripRequest->trip_date) {
            return false;
        }

        $time = $tripRequest->trip_time ?? '00:00';
        try {
            $start = \Illuminate\Support\Carbon::createFromFormat('Y-m-d H:i', $tripRequest->trip_date->format('Y-m-d').' '.$time);
        } catch (\Exception $exception) {
            $start = \Illuminate\Support\Carbon::parse($tripRequest->trip_date->format('Y-m-d').' '.$time);
        }

        return now()->greaterThanOrEqualTo($start);
    }

    private function buildNotificationRecipients(TripRequest $tripRequest, ?User $requester = null)
    {
        $recipients = collect();

        $fleetManagers = User::where('role', User::ROLE_FLEET_MANAGER)->get();
        $superAdmins = User::where('role', User::ROLE_SUPER_ADMIN)->get();
        $branchHeads = User::where('role', User::ROLE_BRANCH_HEAD)
            ->where('branch_id', $tripRequest->branch_id)
            ->get();

        $recipients = $recipients->merge($fleetManagers)->merge($superAdmins)->merge($branchHeads);

        if ($requester) {
            $recipients->push($requester);
        }

        return $recipients->unique('id')->values();
    }

    private function buildCancellationRecipients(TripRequest $tripRequest)
    {
        $recipients = collect();

        $fleetManagers = User::where('role', User::ROLE_FLEET_MANAGER)->get();
        $superAdmins = User::where('role', User::ROLE_SUPER_ADMIN)->get();
        $branchHeads = User::where('role', User::ROLE_BRANCH_HEAD)
            ->where('branch_id', $tripRequest->branch_id)
            ->get();

        $recipients = $recipients->merge($fleetManagers)->merge($superAdmins)->merge($branchHeads);

        if ($tripRequest->requestedBy) {
            $recipients->push($tripRequest->requestedBy);
        }

        return $recipients->unique('id')->values();
    }

    private function canCancelTrip(TripRequest $tripRequest): bool
    {
        if (! $tripRequest->trip_date) {
            return false;
        }

        $tripMoment = $tripRequest->trip_time
            ? Carbon::createFromFormat('Y-m-d H:i', $tripRequest->trip_date->format('Y-m-d') . ' ' . $tripRequest->trip_time)
            : $tripRequest->trip_date->copy()->startOfDay();

        $status = strtolower((string) $tripRequest->status);
        if ($status === 'pending') {
            return true;
        }

        return $status !== 'completed' && now()->lt($tripMoment);
    }

    private function archiveLogEntry(TripLog $log, Request $request, AuditLogService $auditLog): void
    {
        $tripRequest = $log->tripRequest;
        $logId = $log->id;

        $log->delete();

        if ($tripRequest) {
            $this->resetTripAfterLogRemoval($tripRequest, $request);
            $auditLog->log('trip_request.logbook_archived', $tripRequest, [], [
                'trip_log_id' => $logId,
            ]);
            $this->broadcastTripChange($tripRequest, 'logbook_deleted');
        }
    }

    private function resetTripAfterLogRemoval(TripRequest $tripRequest, Request $request): void
    {
        $tripRequest->update([
            'status' => 'assigned',
            'is_completed' => false,
            'logbook_entered_by' => null,
            'logbook_entered_at' => null,
            'updated_by_user_id' => $request->user()->id,
        ]);
    }

    private function broadcastTripChange(TripRequest $tripRequest, string $action): void
    {
        event(new TripRequestChanged($tripRequest->id, $tripRequest->branch_id, $tripRequest->requested_by_user_id, $action));
    }

    private function broadcastTripChangeData(int $tripId, ?int $branchId, ?int $requesterId, string $action): void
    {
        event(new TripRequestChanged($tripId, $branchId, $requesterId, $action));
    }
}
