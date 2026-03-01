<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trip\AssignTripRequest;
use App\Jobs\ProcessTripAssignmentSideEffects;
use App\Http\Requests\Trip\LogTripRequest;
use App\Http\Requests\Trip\StoreTripRequest;
use App\Models\Branch;
use App\Models\Driver;
use App\Models\TripLog;
use App\Models\TripRequest;
use App\Models\TripAssignment;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleMaintenance;
use App\Events\TripRequestChanged;
use App\Notifications\TripRequestApproved;
use App\Notifications\TripRequestAssigned;
use App\Notifications\TripRequestCreated;
use App\Notifications\TripRequestCreatedInApp;
use App\Notifications\TripRequestCancelled;
use App\Notifications\TripRequestReassigned;
use App\Notifications\TripRequestRejected;
use App\Services\AuditLogService;
use App\Services\SmsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Throwable;

class TripRequestController extends Controller
{
    private function applyCreatedFilter(Request $request, $query): void
    {
        $created = strtolower(trim((string) $request->query('created', '')));
        if ($created === '') {
            return;
        }

        $now = Carbon::now();

        if ($created === 'today') {
            $query->whereDate('created_at', $now->toDateString());
            return;
        }

        if ($created === 'week') {
            $query->whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
            return;
        }

        if ($created === 'month') {
            $query->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]);
        }
    }

    private function tripStartForAssignment(TripRequest $tripRequest): ?Carbon
    {
        if (! $tripRequest->trip_date) {
            return null;
        }

        $time = $tripRequest->trip_time;
        if (! is_string($time) || trim($time) === '') {
            $time = '23:59';
        }

        try {
            return Carbon::createFromFormat('Y-m-d H:i', $tripRequest->trip_date->format('Y-m-d').' '.$time);
        } catch (\Throwable) {
            return Carbon::parse($tripRequest->trip_date->format('Y-m-d').' '.$time);
        }
    }

    private function tripAssignmentWindowHasStarted(TripRequest $tripRequest): bool
    {
        $start = $this->tripStartForAssignment($tripRequest);
        if (! $start) {
            return false;
        }

        return now()->greaterThanOrEqualTo($start);
    }

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
        } elseif ($user->role === User::ROLE_BRANCH_ADMIN) {
            $query->where('requested_by_user_id', $user->id);
        } elseif ($user->role === User::ROLE_BRANCH_HEAD) {
            $query->where('branch_id', $user->branch_id);
        }

        $this->applyCreatedFilter($request, $query);
        // Note: due/overdue filter is handled in view via client-side tokens to keep this endpoint lightweight.

        $trips = $query->get();

        $analytics = null;
        $historyTrips = collect();
        if (in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER], true)) {
            $now = Carbon::now();
            $today = $now->toDateString();
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
            $allFutureTrips = TripRequest::whereDate('trip_date', '>', $today)
                ->whereNotIn('status', ['completed', 'cancelled', 'rejected'])
                ->count();

            $approvalRate = $totalTrips > 0 ? round(($approvedTrips / $totalTrips) * 100, 1) : 0;
            $completionRate = $totalTrips > 0 ? round(($completedTrips / $totalTrips) * 100, 1) : 0;

            $analytics = [
                'total' => $totalTrips,
                'all_time' => $allTimeTrips,
                'all_future' => $allFutureTrips,
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
        } elseif ($user->role === User::ROLE_BRANCH_ADMIN) {
            $query->where('requested_by_user_id', $user->id);
        } elseif ($user->role === User::ROLE_BRANCH_HEAD) {
            $query->where('branch_id', $user->branch_id);
        }

        $this->applyCreatedFilter($request, $query);

        $trips = $query->get();

        $payload = $trips->map(function (TripRequest $trip): array {
            $tripTime = 'N/A';
            if ($trip->trip_time) {
                try {
                    $tripTime = Carbon::parse($trip->trip_time)->format('g:i A');
                } catch (\Throwable) {
                    $tripTime = (string) $trip->trip_time;
                }
            }

            return [
                'id' => $trip->id,
                'uuid' => $trip->uuid ?? null,
                'public_id' => $trip->uuid ?: (string) $trip->id,
                'branch_id' => $trip->branch_id,
                'requested_by_user_id' => $trip->requested_by_user_id,
                'request_number' => $trip->request_number,
                'purpose' => $trip->purpose,
                'trip_date' => $trip->trip_date?->format('M d, Y') ?? '',
                'trip_time' => $tripTime,
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

        if (in_array($user->role, [User::ROLE_BRANCH_ADMIN, User::ROLE_BRANCH_HEAD], true)) {
            $branchId = $user->branch_id;
        } else {
            $branchId = $data['branch_id'] ?? $user->branch_id ?? null;
        }
        if (! $branchId) {
            return redirect()
                ->back()
                ->withErrors(['branch_id' => 'Branch is required for this request.'])
                ->withInput();
        }

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments', []) as $file) {
                $attachments[] = $file->store('trips', 'public');
            }
        }

        $tripRequest = $this->createTripRequestWithUniqueRequestNumber([
            'branch_id' => $branchId,
            'requested_by_user_id' => $user->id,
            'purpose' => $data['purpose'],
            'destination' => $data['destination'],
            'trip_date' => $data['trip_date'],
            'trip_time' => $data['trip_time'] ?? null,
            'estimated_distance_km' => $data['estimated_distance_km'] ?? null,
            'number_of_passengers' => $data['number_of_passengers'] ?? 1,
            'additional_notes' => $data['additional_notes'] ?? null,
            'attachments' => $attachments ?: null,
            'status' => 'pending',
            'updated_by_user_id' => $user->id,
        ]);

        $auditLog->log('trip_request.created', $tripRequest, [], $tripRequest->toArray());

        $recipients = $this->buildNotificationRecipients($tripRequest, $user);
        try {
            Notification::send($recipients, TripRequestCreatedInApp::fromTripRequest($tripRequest));
            Notification::send($recipients, TripRequestCreated::fromTripRequest($tripRequest));
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

    public function showById(int $tripRequest): View|RedirectResponse
    {
        $trip = TripRequest::withTrashed()->findOrFail($tripRequest);

        if (is_string($trip->uuid ?? null) && $trip->uuid !== '') {
            return redirect()->route('trips.show', $trip->uuid);
        }

        return $this->show($trip);
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
            'assignments.changedBy',
            'assignments.fromVehicle',
            'assignments.toVehicle',
            'assignments.fromDriver',
            'assignments.toDriver',
            'log.enteredBy',
            'log.editedBy',
            'updatedBy',
        ]);

        $vehicles = collect();
        $drivers = collect();

        if (in_array(auth()->user()?->role, [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER], true)) {
            [$conflictingDriverIds, $conflictingVehicleIds] = $this->tripAssignmentConflictIdsForTripWindow($tripRequest);

            $vehicles = $this->availableVehiclesForTripWindow($tripRequest)
                ->when($conflictingVehicleIds->isNotEmpty(), function ($collection) use ($conflictingVehicleIds) {
                    return $collection->reject(fn ($vehicle) => $conflictingVehicleIds->contains((int) $vehicle->id));
                });

            $drivers = Driver::where('status', 'active')
                ->when($conflictingDriverIds->isNotEmpty(), function ($query) use ($conflictingDriverIds): void {
                    $query->whereNotIn('id', $conflictingDriverIds->all());
                })
                ->orderBy('full_name')
                ->get();

            if ($tripRequest->assignedVehicle && ! $vehicles->contains('id', $tripRequest->assignedVehicle->id)) {
                $vehicles = $vehicles->prepend($tripRequest->assignedVehicle);
            }

            if ($tripRequest->assignedDriver && ! $drivers->contains('id', $tripRequest->assignedDriver->id)) {
                $drivers = $drivers->prepend($tripRequest->assignedDriver);
            }
        }

        $user = request()->user();
        $hasExistingAssignment = (bool) ($tripRequest->assigned_vehicle_id || $tripRequest->assigned_driver_id);
        $assignmentWindowStarted = $this->tripAssignmentWindowHasStarted($tripRequest);
        $assignmentBlocked = $assignmentWindowStarted
            && ! $hasExistingAssignment
            && $user
            && $user->role !== User::ROLE_SUPER_ADMIN;

        $assignmentOverrideAvailable = $assignmentWindowStarted
            && ! $hasExistingAssignment
            && $user
            && $user->role === User::ROLE_SUPER_ADMIN;

        $assignmentAlert = null;
        if ($assignmentBlocked) {
            $assignmentAlert = 'Trip date/time has passed. Please reschedule the trip before assigning a driver and vehicle.';
        } elseif ($assignmentOverrideAvailable) {
            $assignmentAlert = 'Trip date/time has passed. You can still assign this trip using an override, but a reason is required.';
        }

        return view('trips.show', [
            'tripRequest' => $tripRequest,
            'vehicles' => $vehicles,
            'drivers' => $drivers,
            'assignmentBlocked' => $assignmentBlocked,
            'assignmentOverrideAvailable' => $assignmentOverrideAvailable,
            'assignmentAlert' => $assignmentAlert,
        ]);
    }

    public function statusData(Request $request, TripRequest $tripRequest): JsonResponse
    {
        $this->authorizeTripView($request->user(), $tripRequest);

        $tripRequest->load([
            'updatedBy',
            'requestedBy',
            'assignedVehicle',
            'assignedDriver',
        ]);

        return response()->json([
            'id' => $tripRequest->id,
            'status' => $tripRequest->status,
            'updated_at' => $tripRequest->updated_at?->toIso8601String(),
            'updated_at_human' => $tripRequest->updated_at?->diffForHumans(),
            'updated_at_formatted' => $tripRequest->updated_at?->format('M d, Y H:i'),
            'updated_by' => $tripRequest->updatedBy?->name ?? $tripRequest->requestedBy?->name,
            'condition_notes' => $tripRequest->condition_notes,
            'cancellation_reason' => $tripRequest->cancellation_reason,
            'rejection_reason' => $tripRequest->rejection_reason,
            'assigned_vehicle' => $tripRequest->assignedVehicle?->registration_number,
            'assigned_driver' => $tripRequest->assignedDriver?->full_name,
        ]);
    }

    public function downloadAttachment(TripRequest $tripRequest, string $filename)
    {
        $this->authorizeTripView(request()->user(), $tripRequest);
        $path = collect($tripRequest->attachments ?? [])
            ->first(fn ($item) => basename($item) === $filename);

        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->download($path);
    }

    public function previewAttachment(TripRequest $tripRequest, string $filename)
    {
        $this->authorizeTripView(request()->user(), $tripRequest);
        $path = collect($tripRequest->attachments ?? [])
            ->first(fn ($item) => basename($item) === $filename);

        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->response($path, $filename, [
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function cancel(Request $request, TripRequest $tripRequest, AuditLogService $auditLog): RedirectResponse
    {
        $this->authorizeTripMutation($request->user(), $tripRequest);

        if (! $this->canCancelTrip($tripRequest)) {
            return redirect()
                ->route('trips.show', $tripRequest)
                ->with('error', 'This trip can no longer be cancelled.');
        }

        $data = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:1000'],
        ]);

        $previousVehicleId = $tripRequest->assigned_vehicle_id ? (int) $tripRequest->assigned_vehicle_id : null;

        $tripRequest->update([
            'status' => 'cancelled',
            'assigned_vehicle_id' => null,
            'assigned_driver_id' => null,
            'assigned_at' => null,
            'cancellation_reason' => $data['cancellation_reason'],
            'updated_by_user_id' => $request->user()->id,
        ]);

        if ($previousVehicleId) {
            $this->releaseVehicleIfNotNeededNow($previousVehicleId);
        }

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
            Notification::send($recipients, TripRequestApproved::fromTripRequest($tripRequest));
        } catch (Throwable $exception) {
            Log::warning('Trip request approval notification failed.', [
                'trip_request_id' => $tripRequest->id,
                'error' => $exception->getMessage(),
            ]);
        }
        $this->broadcastTripChange($tripRequest, 'approved');

        return redirect()
            ->route('trips.show', ['tripRequest' => $tripRequest, 'condition_prompt' => 1])
            ->with('success', 'Trip request approved.');
    }

    public function setCondition(Request $request, TripRequest $tripRequest, AuditLogService $auditLog): RedirectResponse
    {
        if (! in_array($tripRequest->status, ['approved', 'assigned'], true)) {
            return redirect()
                ->route('trips.show', $tripRequest)
                ->with('error', 'Trip conditions can only be added before the trip is completed or cancelled.');
        }

        $request->validate([
            'condition_notes' => ['required', 'string', 'max:2000'],
        ]);

        $tripRequest->update([
            'condition_notes' => $request->condition_notes,
            'condition_set_by_user_id' => $request->user()->id,
            'condition_set_at' => now(),
            'updated_by_user_id' => $request->user()->id,
        ]);

        $auditLog->log('trip_request.condition_set', $tripRequest, [], [
            'condition_notes' => $tripRequest->condition_notes,
            'condition_set_by_user_id' => $tripRequest->condition_set_by_user_id,
            'condition_set_at' => $tripRequest->condition_set_at?->toDateTimeString(),
        ]);

        $this->broadcastTripChange($tripRequest, 'condition');

        return redirect()
            ->route('trips.show', $tripRequest)
            ->with('success', 'Trip condition saved.');
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
        $status = strtolower((string) $tripRequest->status);
        if (in_array($status, ['completed', 'cancelled'], true)) {
            return redirect()
                ->route('trips.show', $tripRequest)
                ->with('error', 'This trip cannot be reassigned.');
        }

        if (! in_array($status, ['approved', 'assigned'], true)) {
            return redirect()
                ->route('trips.show', $tripRequest)
                ->with('error', 'This trip cannot be assigned in its current status.');
        }

        $fromVehicleId = $tripRequest->assigned_vehicle_id;
        $fromDriverId = $tripRequest->assigned_driver_id;
        $hasExistingAssignment = (bool) ($fromVehicleId || $fromDriverId);
        $assignmentWindowStarted = $this->tripAssignmentWindowHasStarted($tripRequest);
        $forceAssign = $request->boolean('force_assign') && request()->user()?->role === User::ROLE_SUPER_ADMIN;
        $overrideReason = trim((string) $request->input('reason', ''));

        if ($assignmentWindowStarted && ! $hasExistingAssignment) {
            if (! $forceAssign) {
                $message = request()->user()?->role === User::ROLE_SUPER_ADMIN
                    ? 'Trip date/time has passed. To assign anyway, enable override and provide a reason.'
                    : 'Trip date/time has passed. Please reschedule the trip before assigning a driver and vehicle.';

                return redirect()
                    ->back()
                    ->with('error', $message);
            }

            if ($overrideReason === '') {
                return redirect()
                    ->back()
                    ->withErrors(['reason' => 'Reason is required when assigning a past trip.'])
                    ->withInput();
            }
        }

        $toVehicleId = $request->filled('assigned_vehicle_id')
            ? (int) $request->assigned_vehicle_id
            : (int) ($fromVehicleId ?? 0);
        $toDriverId = $request->filled('assigned_driver_id')
            ? (int) $request->assigned_driver_id
            : (int) ($fromDriverId ?? 0);

        if (! $toVehicleId || ! $toDriverId) {
            return redirect()
                ->back()
                ->withErrors([
                    'assigned_vehicle_id' => 'Vehicle is required to complete assignment.',
                    'assigned_driver_id' => 'Driver is required to complete assignment.',
                ])
                ->withInput();
        }

        $isChangingVehicle = ! $fromVehicleId || (int) $fromVehicleId !== $toVehicleId;
        $isChangingDriver = ! $fromDriverId || (int) $fromDriverId !== $toDriverId;

        if ($isChangingVehicle) {
            $vehicle = Vehicle::findOrFail($toVehicleId);

            if (in_array($vehicle->status, ['maintenance', 'offline'], true)) {
                return redirect()
                    ->back()
                    ->withErrors(['assigned_vehicle_id' => 'Selected vehicle is not available.'])
                    ->withInput();
            }

            if ($this->isVehicleBlockedByScheduledMaintenanceForTrip($vehicle->id, $tripRequest)) {
                return redirect()
                    ->back()
                    ->withErrors(['assigned_vehicle_id' => 'Selected vehicle has scheduled maintenance due and cannot be assigned.'])
                    ->withInput();
            }

            if (! $this->isVehicleAvailableForTripWindow($vehicle->id, $tripRequest)) {
                return redirect()
                    ->back()
                    ->withErrors(['assigned_vehicle_id' => 'Selected vehicle is assigned to another trip during this trip time window.'])
                    ->withInput();
            }
        } else {
            $vehicle = $fromVehicleId ? Vehicle::find($fromVehicleId) : null;
        }

        if ($isChangingDriver) {
            $driver = Driver::findOrFail($toDriverId);

            if ($driver->status !== 'active') {
                return redirect()
                    ->back()
                    ->withErrors(['assigned_driver_id' => 'Selected driver is not available.'])
                    ->withInput();
            }

            if (! $this->isDriverAvailableForTripWindow($driver->id, $tripRequest)) {
                return redirect()
                    ->back()
                    ->withErrors(['assigned_driver_id' => 'Selected driver is assigned to another trip during this trip time window.'])
                    ->withInput();
            }
        } else {
            $driver = $fromDriverId ? Driver::find($fromDriverId) : null;
        }

        $hasExistingAssignment = (bool) ($fromVehicleId || $fromDriverId);
        $isReassignment = $hasExistingAssignment && ($isChangingVehicle || $isChangingDriver);
        $hasAnyChange = (! $hasExistingAssignment) || $isReassignment;

        $assignmentReason = null;
        if ($isReassignment) {
            $reason = (string) $request->input('reason', '');
            if (trim($reason) === '') {
                return redirect()
                    ->back()
                    ->withErrors(['reason' => 'Reason is required for reassignment.'])
                    ->withInput();
            }
            $assignmentReason = (string) $request->input('reason');
        } elseif ($forceAssign) {
            $assignmentReason = $overrideReason !== '' ? $overrideReason : null;
        }

        if (! $hasAnyChange) {
            return redirect()
                ->route('trips.show', $tripRequest)
                ->with('success', 'No assignment changes were made.');
        }

        $tripRequest->update([
            'status' => 'assigned',
            'assigned_vehicle_id' => $toVehicleId,
            'assigned_driver_id' => $toDriverId,
            'assigned_at' => now(),
            'requires_reassignment' => false,
            'assignment_conflict_reason' => null,
            'assignment_conflict_at' => null,
            'updated_by_user_id' => request()->user()->id,
        ]);

        if ($isReassignment && $fromVehicleId && (int) $fromVehicleId !== $toVehicleId) {
            $previousVehicle = Vehicle::find($fromVehicleId);
            if ($previousVehicle && $previousVehicle->status === 'in_use' && $this->isVehicleAvailableNow($previousVehicle->id)) {
                $previousVehicle->update(['status' => 'available']);
            }
        }

        TripAssignment::create([
            'trip_request_id' => $tripRequest->id,
            'from_vehicle_id' => $fromVehicleId,
            'to_vehicle_id' => $toVehicleId,
            'from_driver_id' => $fromDriverId,
            'to_driver_id' => $toDriverId,
            'changed_by_user_id' => request()->user()->id,
            'reason' => $assignmentReason,
        ]);

        if ($this->tripHasStarted($tripRequest)) {
            if ($vehicle) {
                $vehicle->update(['status' => 'in_use']);
            }
        }

        $auditAction = $isReassignment
            ? 'trip_request.reassigned'
            : ($forceAssign ? 'trip_request.assigned_override' : 'trip_request.assigned');
        $auditLog->log($auditAction, $tripRequest, [], array_merge($tripRequest->toArray(), [
            'assignment_reason' => $assignmentReason,
            'forced' => $forceAssign,
        ]));

        ProcessTripAssignmentSideEffects::dispatch(
            tripRequestId: (int) $tripRequest->id,
            isReassignment: $isReassignment,
            fromVehicleId: $fromVehicleId ? (int) $fromVehicleId : null,
            fromDriverId: $fromDriverId ? (int) $fromDriverId : null,
            reason: $isReassignment ? (string) $request->input('reason') : null,
        )->afterCommit();
        $this->broadcastTripChange($tripRequest, $isReassignment ? 'reassigned' : 'assigned');

        return redirect()
            ->route('trips.show', $tripRequest)
            ->with('success', $isReassignment ? 'Vehicle and/or driver reassigned.' : 'Vehicle and driver assigned.');
    }

    public function logbook(TripRequest $tripRequest): View
    {
        $this->authorizeTripMutation(request()->user(), $tripRequest);
        $tripRequest->load(['assignedDriver', 'log']);

        if (! $tripRequest->hasStarted()) {
            $startAt = $tripRequest->tripStartAt();
            $startLabel = $startAt ? $startAt->format('M d, Y g:i A') : 'the scheduled start time';

            return redirect()
                ->route('trips.show', $tripRequest)
                ->with('error', "Logbook entry is locked until the trip starts ({$startLabel}).");
        }

        return view('trips.logbook', compact('tripRequest'));
    }

    public function logbookIndex(Request $request): View
    {
        $user = $request->user();

        $query = TripRequest::with(['branch', 'assignedVehicle', 'assignedDriver', 'log.enteredBy', 'log.editedBy'])
            ->whereIn('status', ['assigned', 'completed'])
            ->latest();

        if ($user?->role === User::ROLE_BRANCH_ADMIN) {
            $query->where('requested_by_user_id', $user->id);
        } elseif ($user?->role === User::ROLE_BRANCH_HEAD && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        $trips = $query->get();

        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $monthlyQuery = (clone $query)->whereBetween('trip_date', [$monthStart, $monthEnd]);
        $completedLogbooks = (clone $monthlyQuery)->whereHas('log')->count();
        $pendingLogbooks = (clone $monthlyQuery)->whereDoesntHave('log')->count();

        $stats = [
            'range_label' => $monthStart->format('M Y'),
            'completed' => $completedLogbooks,
            'pending' => $pendingLogbooks,
            'total' => $completedLogbooks + $pendingLogbooks,
        ];

        return view('trips.logbook-index', compact('trips', 'stats'));
    }

    public function manageLogbooks(Request $request): View
    {
        $showArchived = $request->boolean('archived') && $request->user()?->role === User::ROLE_SUPER_ADMIN;
        if ($request->user()?->role !== User::ROLE_SUPER_ADMIN) {
            $showArchived = false;
        }
        $user = $request->user();

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

        if ($user?->role === User::ROLE_BRANCH_ADMIN) {
            $query->whereHas('tripRequest', function ($tripQuery) use ($user): void {
                $tripQuery->where('requested_by_user_id', $user->id);
            });
        } elseif ($user?->role === User::ROLE_BRANCH_HEAD && $user->branch_id) {
            $query->whereHas('tripRequest', function ($tripQuery) use ($user): void {
                $tripQuery->where('branch_id', $user->branch_id);
            });
        }

        $logs = $query->get();

        return view('trips.logbook-manage', compact('logs', 'showArchived'));
    }

    public function showLogbook(Request $request, TripLog $tripLog): View
    {
        if ($tripLog->trashed() && $request->user()?->role !== User::ROLE_SUPER_ADMIN) {
            abort(404);
        }

        $tripLog->load([
            'tripRequest.assignedDriver',
            'tripRequest.assignedVehicle',
            'tripRequest.branch',
            'tripRequest.requestedBy',
        ]);

        $tripRequest = $tripLog->tripRequest;
        if (! $tripRequest) {
            abort(404);
        }
        $this->authorizeTripView($request->user(), $tripRequest);
        $tripRequest->setRelation('log', $tripLog);

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

        $user = request()->user();
        if (in_array($user?->role, [User::ROLE_BRANCH_ADMIN, User::ROLE_BRANCH_HEAD], true)) {
            $branches = Branch::where('id', $user->branch_id)->get();
        } else {
            $branches = Branch::orderBy('name')->get();
        }

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
        if (in_array($request->user()?->role, [User::ROLE_BRANCH_ADMIN, User::ROLE_BRANCH_HEAD], true)) {
            $data['branch_id'] = $tripRequest->branch_id;
        }

        $attachments = $tripRequest->attachments ?? [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments', []) as $file) {
                $attachments[] = $file->store('trips', 'public');
            }
        }
        $data['attachments'] = $attachments ?: null;

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

    public function editLogbook(TripRequest $tripRequest): View|RedirectResponse
    {
        $this->authorizeTripMutation(request()->user(), $tripRequest);
        $tripRequest->load(['assignedDriver', 'log']);

        if (! $tripRequest->hasStarted()) {
            $startAt = $tripRequest->tripStartAt();
            $startLabel = $startAt ? $startAt->format('M d, Y g:i A') : 'the scheduled start time';

            return redirect()
                ->route('trips.show', $tripRequest)
                ->with('error', "Logbook access is locked until the trip starts ({$startLabel}).");
        }

        if (! $tripRequest->log) {
            return redirect()
                ->route('trips.logbook', $tripRequest)
                ->with('error', 'No logbook found for this trip yet.');
        }

        $user = request()->user();
        if (
            in_array($user?->role, [User::ROLE_BRANCH_ADMIN, User::ROLE_BRANCH_HEAD], true)
            && $tripRequest->status === 'completed'
        ) {
            return redirect()
                ->route('trips.show', $tripRequest)
                ->with('error', 'This trip is completed. To edit the logbook, please contact the fleet manager.');
        }

        return view('trips.logbook', compact('tripRequest'));
    }

    public function updateLogbook(LogTripRequest $request, TripRequest $tripRequest, AuditLogService $auditLog): RedirectResponse
    {
        $this->authorizeTripMutation($request->user(), $tripRequest);
        $tripRequest->load(['log']);

        if (! $tripRequest->hasStarted()) {
            $startAt = $tripRequest->tripStartAt();
            $startLabel = $startAt ? $startAt->format('M d, Y g:i A') : 'the scheduled start time';

            return redirect()
                ->route('trips.show', $tripRequest)
                ->with('error', "Logbook updates are locked until the trip starts ({$startLabel}).");
        }

        if (! $tripRequest->log) {
            return redirect()
                ->route('trips.logbook', $tripRequest)
                ->with('error', 'No logbook found for this trip yet.');
        }

        if (
            in_array($request->user()?->role, [User::ROLE_BRANCH_ADMIN, User::ROLE_BRANCH_HEAD], true)
            && $tripRequest->status === 'completed'
        ) {
            return redirect()
                ->route('trips.show', $tripRequest)
                ->with('error', 'This trip is completed. To edit the logbook, please contact the fleet manager.');
        }

        $data = $request->validated();
        $tripRequest->loadMissing('assignedDriver');
        if ($tripRequest->assignedDriver) {
            $data['driver_name'] = $tripRequest->assignedDriver->full_name;
            $data['driver_license_number'] = $tripRequest->assignedDriver->license_number;
        }

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

        $tripRequest->loadMissing(['assignedVehicle']);
        if ($tripRequest->assignedVehicle && $tripRequest->assignedVehicle->status === 'in_use') {
            $tripRequest->assignedVehicle->update(['status' => 'available']);
        }

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
        $previousVehicleId = $tripRequest->assigned_vehicle_id ? (int) $tripRequest->assigned_vehicle_id : null;
        $tripRequest->update([
            'updated_by_user_id' => request()->user()->id,
        ]);
        $oldValues = $tripRequest->toArray();

        if ($tripRequest->log) {
            $tripRequest->log->delete();
        }

        $tripRequest->delete();

        if ($previousVehicleId) {
            $this->releaseVehicleIfNotNeededNow($previousVehicleId);
        }

        $auditLog->log('trip_request.deleted', $tripRequest, $oldValues, [
            'trip_request_id' => $tripRequest->id,
        ]);
        $this->broadcastTripChangeData($tripRequest->id, $tripRequest->branch_id, $tripRequest->requested_by_user_id, 'deleted');

        return redirect()
            ->route('trips.index')
            ->with('success', 'Trip deleted.');
    }

    public function restore(TripRequest $tripRequest, AuditLogService $auditLog): RedirectResponse
    {
        if (! $tripRequest->trashed()) {
            return redirect()
                ->route('trips.index')
                ->with('error', 'Trip is already active.');
        }

        $tripRequest->restore();
        $auditLog->log('trip_request.restored', $tripRequest, [], $tripRequest->toArray());

        return redirect()
            ->route('trips.index', ['archived' => 1])
            ->with('success', 'Trip restored.');
    }

    public function forceDelete(TripRequest $tripRequest, AuditLogService $auditLog): RedirectResponse
    {
        $previousVehicleId = $tripRequest->assigned_vehicle_id ? (int) $tripRequest->assigned_vehicle_id : null;

        $auditLog->log('trip_request.force_deleted', $tripRequest, [], $tripRequest->toArray());
        $tripRequest->forceDelete();

        if ($previousVehicleId) {
            $this->releaseVehicleIfNotNeededNow($previousVehicleId);
        }

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
        $this->authorizeTripMutation($request->user(), $tripRequest);
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

    public function archiveLogbook(Request $request, TripLog $tripLog, AuditLogService $auditLog): RedirectResponse
    {
        if (! $tripLog->tripRequest) {
            abort(404);
        }
        $this->authorizeTripMutation($request->user(), $tripLog->tripRequest);

        $this->archiveLogEntry($tripLog, $request, $auditLog);

        return redirect()
            ->route('logbooks.manage')
            ->with('success', 'Logbook archived.');
    }

    public function restoreLogbook(Request $request, TripLog $tripLog, AuditLogService $auditLog): RedirectResponse
    {
        if (! $tripLog->trashed()) {
            return redirect()
                ->route('logbooks.manage')
                ->with('error', 'Logbook is already active.');
        }

        $tripLog->loadMissing('tripRequest');
        $tripLog->restore();

        $tripRequest = $tripLog->tripRequest;
        if ($tripRequest) {
            $tripRequest->update([
                'status' => 'completed',
                'is_completed' => true,
                'logbook_entered_by' => $tripLog->entered_by_user_id ?? $request->user()->id,
                'logbook_entered_at' => $tripLog->created_at ?? now(),
                'updated_by_user_id' => $request->user()->id,
            ]);
        }

        $auditLog->log('trip_request.logbook_restored', $tripRequest, [], [
            'trip_log_id' => $tripLog->id,
        ]);
        $this->broadcastTripChange($tripRequest, 'logbook_restored');

        return redirect()
            ->route('logbooks.manage', ['archived' => 1])
            ->with('success', 'Logbook restored.');
    }

    public function forceDeleteLogbook(Request $request, TripLog $tripLog, AuditLogService $auditLog): RedirectResponse
    {
        $tripLog->loadMissing('tripRequest');
        $tripRequest = $tripLog->tripRequest;
        $logId = $tripLog->id;

        if ($tripRequest) {
            $this->resetTripAfterLogRemoval($tripRequest, $request);
        }

        $tripLog->forceDelete();

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
        $this->authorizeTripMutation($request->user(), $tripRequest);
        $tripRequest->loadMissing('log');

        if (! $tripRequest->hasStarted()) {
            $startAt = $tripRequest->tripStartAt();
            $startLabel = $startAt ? $startAt->format('M d, Y g:i A') : 'the scheduled start time';

            return redirect()
                ->route('trips.show', $tripRequest)
                ->with('error', "Logbook entry is locked until the trip starts ({$startLabel}).");
        }

        if ($tripRequest->log) {
            return redirect()
                ->route('trips.logbook.edit', $tripRequest)
                ->with('error', 'A logbook already exists for this trip.');
        }

        $data = $request->validated();
        $tripRequest->loadMissing('assignedDriver');
        if ($tripRequest->assignedDriver) {
            $data['driver_name'] = $tripRequest->assignedDriver->full_name;
            $data['driver_license_number'] = $tripRequest->assignedDriver->license_number;
        }

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
        $user = request()->user();
        $hasExistingAssignment = (bool) ($tripRequest->assigned_vehicle_id || $tripRequest->assigned_driver_id);
        $assignmentWindowStarted = $this->tripAssignmentWindowHasStarted($tripRequest);
        $assignmentBlocked = $assignmentWindowStarted
            && ! $hasExistingAssignment
            && $user
            && $user->role !== User::ROLE_SUPER_ADMIN;

        $assignmentOverrideAvailable = $assignmentWindowStarted
            && ! $hasExistingAssignment
            && $user
            && $user->role === User::ROLE_SUPER_ADMIN;

        $assignmentAlert = null;
        if ($assignmentBlocked) {
            $assignmentAlert = 'Trip date/time has passed. Please reschedule the trip before assigning a driver and vehicle.';
        } elseif ($assignmentOverrideAvailable) {
            $assignmentAlert = 'Trip date/time has passed. You can still assign this trip using an override, but a reason is required.';
        }

        [$conflictingDriverIds, $conflictingVehicleIds] = $this->tripAssignmentConflictIdsForTripWindow($tripRequest);

        $vehicles = $this->availableVehiclesForTripWindow($tripRequest)
            ->when($conflictingVehicleIds->isNotEmpty(), function ($collection) use ($conflictingVehicleIds) {
                return $collection->reject(fn ($vehicle) => $conflictingVehicleIds->contains((int) $vehicle->id));
            });

        $drivers = Driver::where('status', 'active')
            ->when($conflictingDriverIds->isNotEmpty(), function ($query) use ($conflictingDriverIds): void {
                $query->whereNotIn('id', $conflictingDriverIds->all());
            })
            ->orderBy('full_name')
            ->get();

        return view('trips.assign', [
            'tripRequest' => $tripRequest,
            'vehicles' => $vehicles,
            'drivers' => $drivers,
            'assignmentBlocked' => $assignmentBlocked,
            'assignmentOverrideAvailable' => $assignmentOverrideAvailable,
            'assignmentAlert' => $assignmentAlert,
        ]);
    }

    private function generateRequestNumber(): string
    {
        $today = now()->format('Ymd');
        $prefix = sprintf('TR-%s-', $today);

        $latest = TripRequest::withTrashed()
            ->where('request_number', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING_INDEX(request_number, "-", -1) AS UNSIGNED) DESC')
            ->value('request_number');

        $latestSequence = 0;
        if ($latest && preg_match('/^TR-\\d{8}-(\\d+)$/', $latest, $matches)) {
            $latestSequence = (int) $matches[1];
        }

        $count = $latestSequence + 1;

        return sprintf('TR-%s-%03d', $today, $count);
    }

    private function createTripRequestWithUniqueRequestNumber(array $attributes): TripRequest
    {
        $attempts = 0;
        $maxAttempts = 5;

        while ($attempts < $maxAttempts) {
            $attempts++;
            $attributes['request_number'] = $this->generateRequestNumber();

            try {
                return TripRequest::create($attributes);
            } catch (QueryException $exception) {
                if ($this->isDuplicateTripRequestNumberException($exception)) {
                    usleep(50_000);
                    continue;
                }
                throw $exception;
            }
        }

        throw new \RuntimeException('Unable to generate a unique trip request number. Please try again.');
    }

    private function isDuplicateTripRequestNumberException(QueryException $exception): bool
    {
        $errorInfo = $exception->errorInfo ?? [];
        $sqlState = $errorInfo[0] ?? null;
        $errno = $errorInfo[1] ?? null;

        return $sqlState === '23000'
            && (int) $errno === 1062
            && str_contains($exception->getMessage(), 'trip_requests_request_number_unique');
    }

    private function availableVehiclesNow()
    {
        $activeAssignedIds = $this->activeAssignedVehicleIds();
        $maintenanceBlockedIds = $this->vehiclesBlockedByScheduledMaintenanceIds();

        return Vehicle::where('status', 'available')
            ->when($activeAssignedIds->isNotEmpty(), function ($query) use ($activeAssignedIds): void {
                $query->whereNotIn('id', $activeAssignedIds);
            })
            ->when($maintenanceBlockedIds->isNotEmpty(), function ($query) use ($maintenanceBlockedIds): void {
                $query->whereNotIn('id', $maintenanceBlockedIds);
            })
            ->orderBy('registration_number')
            ->get();
    }

    private function availableVehiclesForTripWindow(TripRequest $tripRequest)
    {
        $maintenanceBlockedIds = $this->vehiclesBlockedByScheduledMaintenanceIdsForTrip($tripRequest);

        return Vehicle::query()
            ->whereNotIn('status', ['maintenance', 'offline'])
            ->when($maintenanceBlockedIds->isNotEmpty(), function ($query) use ($maintenanceBlockedIds): void {
                $query->whereNotIn('id', $maintenanceBlockedIds);
            })
            ->orderBy('registration_number')
            ->get();
    }

    private function isVehicleBlockedByScheduledMaintenanceForTrip(int $vehicleId, TripRequest $tripRequest): bool
    {
        $tripDate = $tripRequest->trip_date?->toDateString();
        if (! $tripDate) {
            $tripDate = now()->toDateString();
        }

        return VehicleMaintenance::query()
            ->where('vehicle_id', $vehicleId)
            ->where('status', VehicleMaintenance::STATUS_SCHEDULED)
            ->whereDate('scheduled_for', '<=', $tripDate)
            ->exists();
    }

    private function vehiclesBlockedByScheduledMaintenanceIdsForTrip(TripRequest $tripRequest)
    {
        $tripDate = $tripRequest->trip_date?->toDateString();
        if (! $tripDate) {
            $tripDate = now()->toDateString();
        }

        return VehicleMaintenance::query()
            ->where('status', VehicleMaintenance::STATUS_SCHEDULED)
            ->whereDate('scheduled_for', '<=', $tripDate)
            ->pluck('vehicle_id')
            ->filter()
            ->unique();
    }

    private function tripAssignmentConflictIdsForTripWindow(TripRequest $tripRequest): array
    {
        $window = $this->tripWindow($tripRequest);
        if (! $window) {
            return [collect(), collect()];
        }

        [$start, $end] = $window;
        $queryStart = $start->copy()->subDays(31)->toDateString();

        $otherTrips = TripRequest::query()
            ->where('id', '!=', $tripRequest->id)
            ->whereIn('status', ['approved', 'assigned'])
            ->where(function ($query): void {
                $query->whereNull('is_completed')->orWhere('is_completed', false);
            })
            ->whereDate('trip_date', '<=', $end->toDateString())
            ->whereDate('trip_date', '>=', $queryStart)
            ->where(function ($query): void {
                $query->whereNotNull('assigned_driver_id')
                    ->orWhereNotNull('assigned_vehicle_id');
            })
            ->get(['id', 'trip_date', 'trip_time', 'estimated_distance_km', 'assigned_driver_id', 'assigned_vehicle_id']);

        $conflictingDriverIds = collect();
        $conflictingVehicleIds = collect();

        foreach ($otherTrips as $other) {
            $otherWindow = $this->tripWindow($other);
            if (! $otherWindow) {
                continue;
            }

            [$otherStart, $otherEnd] = $otherWindow;

            $overlaps = $start->lt($otherEnd) && $otherStart->lt($end);
            if (! $overlaps) {
                continue;
            }

            if ($other->assigned_driver_id) {
                $conflictingDriverIds->push((int) $other->assigned_driver_id);
            }
            if ($other->assigned_vehicle_id) {
                $conflictingVehicleIds->push((int) $other->assigned_vehicle_id);
            }
        }

        return [
            $conflictingDriverIds->unique()->values(),
            $conflictingVehicleIds->unique()->values(),
        ];
    }

    private function isVehicleAvailableNow(int $vehicleId): bool
    {
        if ($this->vehiclesBlockedByScheduledMaintenanceIds()->contains($vehicleId)) {
            return false;
        }

        return ! $this->activeAssignedVehicleIds()->contains($vehicleId);
    }

    private function releaseVehicleIfNotNeededNow(int $vehicleId): void
    {
        $vehicle = Vehicle::find($vehicleId);
        if (! $vehicle) {
            return;
        }

        if ($vehicle->status !== 'in_use') {
            return;
        }

        if (! $this->isVehicleAvailableNow($vehicleId)) {
            return;
        }

        $vehicle->update(['status' => 'available']);
    }

    private function isVehicleAvailableForTripWindow(int $vehicleId, TripRequest $tripRequest): bool
    {
        $window = $this->tripWindow($tripRequest);
        if (! $window) {
            return true;
        }

        [$start, $end] = $window;
        $queryStart = $start->copy()->subDays(31)->toDateString();

        return ! TripRequest::query()
            ->where('id', '!=', $tripRequest->id)
            ->where('assigned_vehicle_id', $vehicleId)
            ->whereIn('status', ['approved', 'assigned'])
            ->where(function ($query): void {
                $query->whereNull('is_completed')->orWhere('is_completed', false);
            })
            ->whereDate('trip_date', '<=', $end->toDateString())
            ->whereDate('trip_date', '>=', $queryStart)
            ->get(['id', 'trip_date', 'trip_time', 'estimated_distance_km'])
            ->contains(function (TripRequest $other) use ($start, $end): bool {
                $otherWindow = $this->tripWindow($other);
                if (! $otherWindow) {
                    return false;
                }

                [$otherStart, $otherEnd] = $otherWindow;

                return $start->lt($otherEnd) && $otherStart->lt($end);
            });
    }

    private function isDriverAvailableForTripWindow(int $driverId, TripRequest $tripRequest): bool
    {
        $window = $this->tripWindow($tripRequest);
        if (! $window) {
            return true;
        }

        [$start, $end] = $window;
        $queryStart = $start->copy()->subDays(31)->toDateString();

        return ! TripRequest::query()
            ->where('id', '!=', $tripRequest->id)
            ->where('assigned_driver_id', $driverId)
            ->whereIn('status', ['approved', 'assigned'])
            ->where(function ($query): void {
                $query->whereNull('is_completed')->orWhere('is_completed', false);
            })
            ->whereDate('trip_date', '<=', $end->toDateString())
            ->whereDate('trip_date', '>=', $queryStart)
            ->get(['id', 'trip_date', 'trip_time', 'estimated_distance_km'])
            ->contains(function (TripRequest $other) use ($start, $end): bool {
                $otherWindow = $this->tripWindow($other);
                if (! $otherWindow) {
                    return false;
                }

                [$otherStart, $otherEnd] = $otherWindow;

                return $start->lt($otherEnd) && $otherStart->lt($end);
            });
    }

    private function isVehicleBlockedByScheduledMaintenance(int $vehicleId): bool
    {
        $today = now()->toDateString();

        return VehicleMaintenance::query()
            ->where('vehicle_id', $vehicleId)
            ->where('status', VehicleMaintenance::STATUS_SCHEDULED)
            ->whereDate('scheduled_for', '<=', $today)
            ->exists();
    }

    private function vehiclesBlockedByScheduledMaintenanceIds()
    {
        $today = now()->toDateString();

        return VehicleMaintenance::query()
            ->where('status', VehicleMaintenance::STATUS_SCHEDULED)
            ->whereDate('scheduled_for', '<=', $today)
            ->pluck('vehicle_id')
            ->filter()
            ->unique();
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

    private function activeAssignedDriverIds()
    {
        $now = now();
        $today = $now->toDateString();

        return TripRequest::whereNotNull('assigned_driver_id')
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
            ->pluck('assigned_driver_id')
            ->unique();
    }

    private function isDriverAvailableNow(int $driverId): bool
    {
        return ! $this->activeAssignedDriverIds()->contains($driverId);
    }

    private function tripWindow(TripRequest $tripRequest): ?array
    {
        if (! $tripRequest->trip_date) {
            return null;
        }

        $date = $tripRequest->trip_date->format('Y-m-d');
        $time = trim((string) ($tripRequest->trip_time ?? '00:00'));

        if ($time === '') {
            $time = '00:00';
        }

        if (str_contains($time, '.')) {
            $time = explode('.', $time, 2)[0];
        }

        $candidate = $date.' '.$time;
        $formats = [
            'Y-m-d H:i',
            'Y-m-d H:i:s',
            'Y-m-d g:i A',
            'Y-m-d g:iA',
        ];

        $start = null;
        foreach ($formats as $format) {
            try {
                $start = \Illuminate\Support\Carbon::createFromFormat($format, $candidate);
                break;
            } catch (\Exception $exception) {
                // try next
            }
        }

        $start = $start ?? \Illuminate\Support\Carbon::parse($candidate);

        $estimateDays = (float) ($tripRequest->estimated_distance_km ?? 0);
        $hours = $estimateDays > 0 ? (int) round($estimateDays * 24) : 24;
        if ($hours <= 0) {
            $hours = 24;
        }

        $end = $start->copy()->addHours($hours);

        return [$start, $end];
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

        $effectiveRequester = $requester ?: $tripRequest->requestedBy;

        $fleetManagers = collect();
        if (! ($effectiveRequester && $effectiveRequester->role === User::ROLE_SUPER_ADMIN)) {
            $fleetManagers = User::where('role', User::ROLE_FLEET_MANAGER)->get();
        }
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

        $effectiveRequester = $tripRequest->requestedBy;

        $fleetManagers = collect();
        if (! ($effectiveRequester && $effectiveRequester->role === User::ROLE_SUPER_ADMIN)) {
            $fleetManagers = User::where('role', User::ROLE_FLEET_MANAGER)->get();
        }
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

        $status = strtolower((string) $tripRequest->status);
        if (! in_array($status, ['pending', 'approved', 'assigned'], true)) {
            return false;
        }

        // If trip time is not specified, allow cancellation until end-of-day (instead of midnight),
        // since branch admins often create trips with date only.
        $tripMoment = $tripRequest->trip_date->copy()->endOfDay();

        if ($tripRequest->trip_time) {
            $date = $tripRequest->trip_date->format('Y-m-d');
            $time = trim((string) $tripRequest->trip_time);

            if (str_contains($time, '.')) {
                $time = explode('.', $time, 2)[0];
            }

            $candidate = $date.' '.$time;
            $formats = [
                'Y-m-d H:i',
                'Y-m-d H:i:s',
                'Y-m-d g:i A',
                'Y-m-d g:iA',
            ];

            $parsedMoment = null;
            foreach ($formats as $format) {
                try {
                    $parsedMoment = Carbon::createFromFormat($format, $candidate);
                    break;
                } catch (\Carbon\Exceptions\InvalidFormatException $exception) {
                    // Try next format.
                }
            }

            if (! $parsedMoment) {
                try {
                    $parsedMoment = Carbon::parse($candidate);
                } catch (\Throwable $exception) {
                    $parsedMoment = null;
                }
            }

            if ($parsedMoment) {
                $tripMoment = $parsedMoment;
            }
        }

        if ($status === 'pending') {
            return true;
        }

        return now()->lt($tripMoment);
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
