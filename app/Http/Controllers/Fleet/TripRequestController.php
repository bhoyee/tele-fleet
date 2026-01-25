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
use App\Notifications\TripRequestApproved;
use App\Notifications\TripRequestAssigned;
use App\Notifications\TripRequestCreated;
use App\Services\AuditLogService;
use App\Services\SmsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class TripRequestController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = TripRequest::with([
            'branch',
            'requestedBy',
            'approvedBy',
            'assignedVehicle',
            'assignedDriver',
        ])->orderByDesc('created_at');

        if (in_array($user->role, [User::ROLE_BRANCH_ADMIN, User::ROLE_BRANCH_HEAD], true)) {
            $query->where('branch_id', $user->branch_id);
        }

        $trips = $query->get();

        return view('trips.index', compact('trips'));
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
            'estimated_distance_km' => $data['estimated_distance_km'] ?? null,
            'number_of_passengers' => $data['number_of_passengers'] ?? 1,
            'additional_notes' => $data['additional_notes'] ?? null,
            'status' => 'pending',
        ]);

        $auditLog->log('trip_request.created', $tripRequest, [], $tripRequest->toArray());

        $recipients = $this->buildNotificationRecipients($tripRequest);
        Notification::send($recipients, new TripRequestCreated($tripRequest));

        return redirect()
            ->route('trips.show', $tripRequest)
            ->with('success', 'Trip request submitted successfully.');
    }

    public function show(TripRequest $tripRequest): View
    {
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
            $vehicles = Vehicle::where('status', 'available')
                ->orderBy('registration_number')
                ->get();
            $drivers = Driver::where('status', 'active')
                ->orderBy('full_name')
                ->get();
        }

        return view('trips.show', compact('tripRequest', 'vehicles', 'drivers'));
    }

    public function approve(TripRequest $tripRequest, AuditLogService $auditLog): RedirectResponse
    {
        $tripRequest->update([
            'status' => 'approved',
            'approved_by_user_id' => request()->user()->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $auditLog->log('trip_request.approved', $tripRequest, [], $tripRequest->toArray());

        $recipients = $this->buildNotificationRecipients($tripRequest, $tripRequest->requestedBy);
        Notification::send($recipients, new TripRequestApproved($tripRequest));

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
        ]);

        $auditLog->log('trip_request.rejected', $tripRequest, [], $tripRequest->toArray());

        return redirect()
            ->route('trips.show', $tripRequest)
            ->with('success', 'Trip request rejected.');
    }

    public function assign(AssignTripRequest $request, TripRequest $tripRequest, AuditLogService $auditLog, SmsService $sms): RedirectResponse
    {
        $vehicle = Vehicle::findOrFail($request->assigned_vehicle_id);
        $driver = Driver::findOrFail($request->assigned_driver_id);

        if ($vehicle->status !== 'available') {
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

        $tripRequest->update([
            'status' => 'assigned',
            'assigned_vehicle_id' => $request->assigned_vehicle_id,
            'assigned_driver_id' => $request->assigned_driver_id,
            'assigned_at' => now(),
        ]);

        $vehicle->update(['status' => 'in_use']);
        $driver->update(['status' => 'inactive']);

        $auditLog->log('trip_request.assigned', $tripRequest, [], $tripRequest->toArray());

        $tripRequest->load(['assignedVehicle', 'assignedDriver', 'requestedBy']);
        $recipients = $this->buildNotificationRecipients($tripRequest, $tripRequest->requestedBy);
        Notification::send($recipients, new TripRequestAssigned($tripRequest));

        if ($tripRequest->assignedDriver?->phone) {
            $sms->send($tripRequest->assignedDriver->phone, sprintf(
                'Trip %s assigned. Vehicle %s. Destination: %s. Date: %s.',
                $tripRequest->request_number,
                $tripRequest->assignedVehicle?->registration_number ?? 'N/A',
                $tripRequest->destination,
                $tripRequest->trip_date?->format('Y-m-d') ?? ''
            ));
        }

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

    public function edit(TripRequest $tripRequest): View
    {
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
        if ($tripRequest->status === 'completed') {
            return redirect()
                ->route('trips.show', $tripRequest)
                ->with('error', 'Completed trips cannot be edited.');
        }

        $data = $request->validated();

        $tripRequest->update(array_merge($data, [
            'updated_by_user_id' => $request->user()->id,
        ]));

        $auditLog->log('trip_request.updated', $tripRequest, [], $data);

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
        ]);

        $auditLog->log('trip_request.logbook_updated', $tripRequest, [], [
            'trip_log_id' => $tripRequest->log->id,
        ]);

        return redirect()
            ->route('trips.show', $tripRequest)
            ->with('success', 'Trip logbook updated.');
    }

    public function destroy(TripRequest $tripRequest, AuditLogService $auditLog): RedirectResponse
    {
        $tripRequest->load('log');

        if ($tripRequest->log) {
            $tripRequest->log->delete();
        }

        $tripRequest->delete();

        $auditLog->log('trip_request.deleted', $tripRequest, [], [
            'trip_request_id' => $tripRequest->id,
        ]);

        return redirect()
            ->route('trips.index')
            ->with('success', 'Trip deleted.');
    }

    public function destroyLogbook(TripRequest $tripRequest, AuditLogService $auditLog): RedirectResponse
    {
        $tripRequest->load('log');

        if (! $tripRequest->log) {
            return redirect()
                ->route('logbooks.index')
                ->with('error', 'No logbook found to delete.');
        }

        $logId = $tripRequest->log->id;
        $tripRequest->log->delete();

        $tripRequest->update([
            'status' => 'assigned',
            'is_completed' => false,
            'logbook_entered_by' => null,
            'logbook_entered_at' => null,
        ]);

        $auditLog->log('trip_request.logbook_deleted', $tripRequest, [], [
            'trip_log_id' => $logId,
        ]);

        return redirect()
            ->route('logbooks.index')
            ->with('success', 'Logbook deleted.');
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
        ]);

        $tripRequest->load(['assignedVehicle', 'assignedDriver']);

        if ($tripRequest->assignedVehicle) {
            $tripRequest->assignedVehicle->update(['status' => 'available']);
        }

        if ($tripRequest->assignedDriver) {
            $tripRequest->assignedDriver->update(['status' => 'active']);
        }

        $auditLog->log('trip_request.logbook_entered', $tripRequest, [], [
            'trip_log_id' => $tripLog->id,
        ]);

        return redirect()
            ->route('trips.show', $tripRequest)
            ->with('success', 'Trip logbook saved.');
    }

    public function assignmentForm(TripRequest $tripRequest): View
    {
        $vehicles = Vehicle::where('status', 'available')->orderBy('registration_number')->get();
        $drivers = Driver::where('status', 'active')->orderBy('full_name')->get();

        return view('trips.assign', compact('tripRequest', 'vehicles', 'drivers'));
    }

    private function generateRequestNumber(): string
    {
        $today = now()->format('Ymd');
        $count = TripRequest::whereDate('created_at', now()->toDateString())->count() + 1;

        return sprintf('TR-%s-%03d', $today, $count);
    }

    private function buildNotificationRecipients(TripRequest $tripRequest, ?User $requester = null)
    {
        $recipients = collect();

        $fleetManagers = User::where('role', User::ROLE_FLEET_MANAGER)->get();
        $branchHeads = User::where('role', User::ROLE_BRANCH_HEAD)
            ->where('branch_id', $tripRequest->branch_id)
            ->get();

        $recipients = $recipients->merge($fleetManagers)->merge($branchHeads);

        if ($requester) {
            $recipients->push($requester);
        }

        return $recipients->unique('id')->values();
    }
}
