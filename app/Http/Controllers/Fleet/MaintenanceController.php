<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\StoreMaintenanceRequest;
use App\Http\Requests\Maintenance\UpdateMaintenanceRequest;
use App\Models\Vehicle;
use App\Models\User;
use App\Events\TripRequestChanged;
use App\Models\VehicleMaintenance;
use App\Models\TripRequest;
use App\Notifications\TripAssignmentConflict;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class MaintenanceController extends Controller
{
    public function index(Request $request): View
    {
        $statusFilter = $request->query('status');

        $query = VehicleMaintenance::with(['vehicle', 'branch', 'createdBy'])
            ->orderByDesc('scheduled_for')
            ->orderByDesc('created_at');

        if (in_array($statusFilter, ['due', 'overdue'], true)) {
            $query->whereHas('vehicle', function ($vehicleQuery) use ($statusFilter): void {
                $vehicleQuery->where('maintenance_state', $statusFilter);
            });
        }

        $maintenances = $query->get();

        return view('maintenances.index', compact('maintenances', 'statusFilter'));
    }

    public function create(): View
    {
        $vehicles = Vehicle::orderBy('registration_number')->get();
        $statuses = $this->statusOptions();

        return view('maintenances.create', compact('vehicles', 'statuses'));
    }

    public function store(StoreMaintenanceRequest $request, AuditLogService $auditLog): RedirectResponse
    {
        $data = $request->validated();
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);

        $data['branch_id'] = $vehicle->branch_id;
        $data['created_by_user_id'] = $request->user()?->id;

        $data = $this->normalizeStatusDates($data);

        $maintenance = VehicleMaintenance::create($data);
        $this->syncVehicleStatus($vehicle, $maintenance->status, $request->user());
        $auditLog->log('maintenance.created', $maintenance, [], $maintenance->toArray());

        return redirect()
            ->route('maintenances.show', $maintenance)
            ->with('success', 'Maintenance scheduled successfully.');
    }

    public function show(VehicleMaintenance $maintenance): View
    {
        $maintenance->load(['vehicle', 'branch', 'createdBy']);

        return view('maintenances.show', compact('maintenance'));
    }

    public function edit(VehicleMaintenance $maintenance): View
    {
        if ($maintenance->status === VehicleMaintenance::STATUS_COMPLETED) {
            abort(403, 'Completed maintenance records cannot be edited.');
        }
        $vehicles = Vehicle::orderBy('registration_number')->get();
        $statuses = $this->statusOptions();

        return view('maintenances.edit', compact('maintenance', 'vehicles', 'statuses'));
    }

    public function update(UpdateMaintenanceRequest $request, VehicleMaintenance $maintenance, AuditLogService $auditLog): RedirectResponse
    {
        if ($maintenance->status === VehicleMaintenance::STATUS_COMPLETED) {
            return redirect()
                ->route('maintenances.show', $maintenance)
                ->with('error', 'Completed maintenance records cannot be edited.');
        }
        $data = $request->validated();
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);

        $data['branch_id'] = $vehicle->branch_id;
        $data = $this->normalizeStatusDates($data, $maintenance);

        $oldValues = $maintenance->getOriginal();
        $maintenance->update($data);
        $this->syncVehicleStatus($vehicle, $maintenance->status, $request->user());
        $auditLog->log('maintenance.updated', $maintenance, $oldValues, $maintenance->getChanges());

        return redirect()
            ->route('maintenances.show', $maintenance)
            ->with('success', 'Maintenance updated successfully.');
    }

    public function destroy(VehicleMaintenance $maintenance, AuditLogService $auditLog): RedirectResponse
    {
        $maintenance->delete();
        $auditLog->log('maintenance.deleted', $maintenance);

        return redirect()
            ->route('maintenances.index')
            ->with('success', 'Maintenance record deleted.');
    }

    public function exportCsv(Request $request, AuditLogService $auditLog)
    {
        $statusFilter = $request->query('status');

        $query = VehicleMaintenance::with(['vehicle', 'branch', 'createdBy'])
            ->orderByDesc('scheduled_for')
            ->orderByDesc('created_at');

        if (in_array($statusFilter, ['due', 'overdue'], true)) {
            $query->whereHas('vehicle', function ($vehicleQuery) use ($statusFilter): void {
                $vehicleQuery->where('maintenance_state', $statusFilter);
            });
        }

        $maintenances = $query->get();
        $filename = 'maintenance-records-' . now()->format('Ymd-His') . '.csv';
        $auditLog->log('maintenance.export_csv', null, [], [
            'status' => $statusFilter,
            'count' => $maintenances->count(),
        ]);

        return response()->streamDownload(function () use ($maintenances): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Vehicle', 'Status', 'Scheduled For', 'Description', 'Cost', 'Odometer', 'Maintenance State']);
            foreach ($maintenances as $maintenance) {
                fputcsv($handle, [
                    $maintenance->vehicle?->registration_number ?? 'N/A',
                    ucfirst(str_replace('_', ' ', $maintenance->status)),
                    $maintenance->scheduled_for?->format('Y-m-d'),
                    $maintenance->description,
                    $maintenance->cost ?? '—',
                    $maintenance->odometer ?? '—',
                    ucfirst($maintenance->vehicle?->maintenance_state ?? 'ok'),
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request, AuditLogService $auditLog)
    {
        $statusFilter = $request->query('status');

        $query = VehicleMaintenance::with(['vehicle', 'branch', 'createdBy'])
            ->orderByDesc('scheduled_for')
            ->orderByDesc('created_at');

        if (in_array($statusFilter, ['due', 'overdue'], true)) {
            $query->whereHas('vehicle', function ($vehicleQuery) use ($statusFilter): void {
                $vehicleQuery->where('maintenance_state', $statusFilter);
            });
        }

        $maintenances = $query->get();
        $auditLog->log('maintenance.export_pdf', null, [], [
            'status' => $statusFilter,
            'count' => $maintenances->count(),
        ]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('maintenances/report-pdf', [
            'maintenances' => $maintenances,
            'generatedAt' => now(),
            'statusFilter' => $statusFilter,
        ]);

        return $pdf->download('maintenance-records-' . now()->format('Ymd-His') . '.pdf');
    }

    public function indexData(Request $request): JsonResponse
    {
        $statusFilter = $request->query('status');

        $query = VehicleMaintenance::with(['vehicle', 'branch', 'createdBy'])
            ->orderByDesc('scheduled_for')
            ->orderByDesc('created_at');

        if (in_array($statusFilter, ['due', 'overdue'], true)) {
            $query->whereHas('vehicle', function ($vehicleQuery) use ($statusFilter): void {
                $vehicleQuery->where('maintenance_state', $statusFilter);
            });
        }

        $maintenances = $query->get();

        return response()->json([
            'data' => $maintenances->map(function (VehicleMaintenance $maintenance): array {
                $status = $maintenance->status;
                return [
                    'id' => $maintenance->id,
                    'vehicle_registration' => $maintenance->vehicle?->registration_number ?? 'N/A',
                    'vehicle_make' => $maintenance->vehicle?->make ?? '',
                    'vehicle_model' => $maintenance->vehicle?->model ?? '',
                    'scheduled_for' => $maintenance->scheduled_for?->format('M d, Y') ?? 'N/A',
                    'status' => $status,
                    'status_label' => ucfirst(str_replace('_', ' ', $status)),
                    'description' => $maintenance->description,
                    'cost' => $maintenance->cost !== null ? number_format($maintenance->cost, 2) : 'N/A',
                ];
            }),
        ]);
    }

    private function statusOptions(): array
    {
        return [
            VehicleMaintenance::STATUS_SCHEDULED,
            VehicleMaintenance::STATUS_IN_PROGRESS,
            VehicleMaintenance::STATUS_COMPLETED,
            VehicleMaintenance::STATUS_CANCELLED,
        ];
    }

    private function normalizeStatusDates(array $data, ?VehicleMaintenance $maintenance = null): array
    {
        $status = $data['status'] ?? null;
        $now = Carbon::now();

        if ($status === VehicleMaintenance::STATUS_IN_PROGRESS && empty($data['started_at'])) {
            $data['started_at'] = $maintenance?->started_at ?? $now;
        }

        if ($status === VehicleMaintenance::STATUS_COMPLETED && empty($data['completed_at'])) {
            $data['completed_at'] = $maintenance?->completed_at ?? $now;
        }

        if ($status === VehicleMaintenance::STATUS_SCHEDULED) {
            $data['started_at'] = $data['started_at'] ?? null;
            $data['completed_at'] = $data['completed_at'] ?? null;
        }

        return $data;
    }

    private function syncVehicleStatus(Vehicle $vehicle, string $maintenanceStatus, ?User $actor = null): void
    {
        $wasMaintenance = $vehicle->status === 'maintenance';
        if ($maintenanceStatus === VehicleMaintenance::STATUS_IN_PROGRESS) {
            if ($vehicle->status !== 'maintenance') {
                $vehicle->status = 'maintenance';
            }
            $vehicle->maintenance_state = 'ok';
            $vehicle->maintenance_due_notified_at = null;
            $vehicle->maintenance_overdue_notified_at = null;
            $vehicle->save();
            if (! $wasMaintenance) {
                $this->handleAssignmentConflicts($vehicle, $actor);
            }
            return;
        }

        if (in_array($maintenanceStatus, [VehicleMaintenance::STATUS_COMPLETED, VehicleMaintenance::STATUS_CANCELLED], true)) {
            if ($maintenanceStatus === VehicleMaintenance::STATUS_COMPLETED) {
                $vehicle->last_maintenance_mileage = $vehicle->current_mileage ?? $vehicle->last_maintenance_mileage;
                $vehicle->maintenance_state = 'ok';
                $vehicle->maintenance_due_notified_at = null;
                $vehicle->maintenance_overdue_notified_at = null;
            }

            if ($vehicle->status === 'maintenance') {
                $vehicle->status = 'available';
            }

            $vehicle->save();

            if ($maintenanceStatus === VehicleMaintenance::STATUS_COMPLETED) {
                $this->refreshMaintenanceMileageState($vehicle);
            }
        }
    }

    private function refreshMaintenanceMileageState(Vehicle $vehicle): void
    {
        $target = (int) \App\Models\AppSetting::getValue('maintenance_mileage_target', '5000');
        $dueThreshold = (int) ceil($vehicle->last_maintenance_mileage + ($target * 0.98));
        $overdueThreshold = (int) ($vehicle->last_maintenance_mileage + $target);

        $state = 'ok';
        if ($vehicle->current_mileage >= $overdueThreshold) {
            $state = 'overdue';
        } elseif ($vehicle->current_mileage >= $dueThreshold) {
            $state = 'due';
        }

        $vehicle->maintenance_state = $state;
        if ($state === 'ok') {
            $vehicle->maintenance_due_notified_at = null;
            $vehicle->maintenance_overdue_notified_at = null;
        }
        $vehicle->save();
    }

    private function handleAssignmentConflicts(Vehicle $vehicle, ?User $actor = null): void
    {
        $now = Carbon::now();

        $trips = TripRequest::where('assigned_vehicle_id', $vehicle->id)
            ->whereIn('status', ['approved', 'assigned'])
            ->where(function ($query): void {
                $query->whereNull('is_completed')->orWhere('is_completed', false);
            })
            ->get();

        foreach ($trips as $trip) {
            if (! $trip->trip_date) {
                continue;
            }
            $time = $trip->trip_time ?? '00:00';
            try {
                $tripAt = Carbon::createFromFormat('Y-m-d H:i', $trip->trip_date->format('Y-m-d').' '.$time);
            } catch (\Exception $exception) {
                $tripAt = Carbon::parse($trip->trip_date->format('Y-m-d').' '.$time);
            }

            if ($tripAt->lt($now)) {
                continue;
            }

            $trip->requires_reassignment = true;
            $trip->assignment_conflict_reason = 'Vehicle moved to maintenance.';
            $trip->assignment_conflict_at = now();

            $autoUnassigned = false;
            if ($tripAt->diffInMinutes($now) <= 30) {
                $trip->assigned_vehicle_id = null;
                $trip->assigned_driver_id = null;
                $trip->status = 'approved';
                $autoUnassigned = true;
            }

            if ($actor?->id) {
                $trip->updated_by_user_id = $actor->id;
            }

            $trip->save();
            event(new TripRequestChanged($trip->id, $trip->branch_id, $trip->requested_by_user_id, 'maintenance_conflict'));

            $recipients = $this->buildConflictRecipients($trip);
            $message = $autoUnassigned
                ? 'Vehicle moved to maintenance. Assignment auto-unassigned because the trip is within 30 minutes.'
                : 'Vehicle moved to maintenance. Trip needs reassignment.';

            try {
                Notification::send($recipients, new TripAssignmentConflict($trip, $message, $autoUnassigned));
            } catch (Throwable $exception) {
                Log::warning('Trip assignment conflict notification failed.', [
                    'trip_id' => $trip->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    private function buildConflictRecipients(TripRequest $tripRequest)
    {
        $recipients = collect();

        $recipients = $recipients->merge(
            User::where('role', User::ROLE_FLEET_MANAGER)->get()
        );

        $recipients = $recipients->merge(
            User::where('role', User::ROLE_SUPER_ADMIN)->get()
        );

        if ($tripRequest->requestedBy) {
            $recipients->push($tripRequest->requestedBy);
        }

        return $recipients->unique('id')->values();
    }
}
