<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Incident\StoreIncidentReportRequest;
use App\Http\Requests\Incident\UpdateIncidentReportRequest;
use App\Http\Requests\Incident\UpdateIncidentStatusRequest;
use App\Models\Branch;
use App\Models\Driver;
use App\Models\IncidentReport;
use App\Models\TripRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Events\IncidentReportChanged;
use App\Notifications\IncidentReported;
use App\Notifications\IncidentStatusUpdated;
use App\Notifications\IncidentUpdated;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Throwable;
use Illuminate\Http\JsonResponse;

class IncidentReportController extends Controller
{
    public function index(Request $request): View
    {
        $showArchived = $request->boolean('archived') && $request->user()?->role === User::ROLE_SUPER_ADMIN;
        $incidents = $this->buildIncidentQuery($request, $showArchived)->get();

        return view('incidents.index', compact('incidents', 'showArchived'));
    }

    public function indexData(Request $request): JsonResponse
    {
        $showArchived = $request->boolean('archived') && $request->user()?->role === User::ROLE_SUPER_ADMIN;
        $incidents = $this->buildIncidentQuery($request, $showArchived)->get();

        $payload = $incidents->map(function (IncidentReport $incident): array {
            return [
                'id' => $incident->id,
                'reference' => $incident->reference,
                'severity' => $incident->severity,
                'status' => $incident->status,
                'incident_date' => $incident->incident_date?->format('M d, Y') ?? '',
                'branch_id' => $incident->branch_id,
                'reported_by_user_id' => $incident->reported_by_user_id,
                'is_archived' => $incident->trashed(),
            ];
        });

        return response()->json([
            'data' => $payload,
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        $branches = Branch::orderBy('name');
        $tripsQuery = TripRequest::with(['branch', 'assignedVehicle', 'assignedDriver'])
            ->orderByDesc('created_at')
            ->take(50);
        $vehicles = Vehicle::orderBy('registration_number')->get();
        $drivers = Driver::orderBy('full_name')->get();

        if (in_array($user->role, [User::ROLE_BRANCH_ADMIN, User::ROLE_BRANCH_HEAD], true)) {
            $branches->where('id', $user->branch_id);
            $tripsQuery->where('branch_id', $user->branch_id);
        }

        $branches = $branches->get();
        $trips = $tripsQuery->get();

        return view('incidents.create', compact('branches', 'trips', 'vehicles', 'drivers'));
    }

    public function store(StoreIncidentReportRequest $request, AuditLogService $auditLog): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $data['reported_by_user_id'] = $user->id;
        if (in_array($user->role, [User::ROLE_BRANCH_ADMIN, User::ROLE_BRANCH_HEAD], true)) {
            $data['branch_id'] = $user->branch_id;
        } else {
            $data['branch_id'] = $data['branch_id'] ?? $user->branch_id;
        }

        if (! empty($data['trip_request_id'])) {
            $trip = TripRequest::find($data['trip_request_id']);
            if ($trip && $data['branch_id'] && $trip->branch_id !== $data['branch_id']) {
                return redirect()
                    ->back()
                    ->withErrors(['trip_request_id' => 'Selected trip does not belong to your branch.'])
                    ->withInput();
            }
        }
        $data['reference'] = $this->generateReference();
        $data['status'] = IncidentReport::STATUS_OPEN;
        $data['updated_by_user_id'] = $user->id;

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments', []) as $file) {
                $attachments[] = $file->store('incidents', 'local');
            }
        }
        $data['attachments'] = $attachments ?: null;

        $incident = IncidentReport::create($data);
        $auditLog->log('incident.created', $incident, [], $incident->toArray());

        $recipients = $this->buildRecipients($incident, $user);
        try {
            Notification::send($recipients, new IncidentReported($incident));
        } catch (Throwable $exception) {
            Log::warning('Incident email notification failed.', [
                'incident_id' => $incident->id,
                'error' => $exception->getMessage(),
            ]);
        }
        $this->broadcastIncidentChange($incident, 'created');

        return redirect()
            ->route('incidents.show', $incident)
            ->with('success', 'Incident report submitted.');
    }

    public function show(IncidentReport $incident): View
    {
        $this->authorizeIncidentView($incident, request()->user());
        $incident->load(['tripRequest', 'branch', 'vehicle', 'driver', 'reportedBy', 'closedBy', 'updatedBy']);

        return view('incidents.show', compact('incident'));
    }

    public function edit(IncidentReport $incident, Request $request): View
    {
        $this->authorizeIncidentMutation($incident, $request->user());

        if ($incident->status !== IncidentReport::STATUS_OPEN) {
            return redirect()
                ->route('incidents.show', $incident)
                ->with('error', 'Only open incidents can be edited.');
        }

        $user = $request->user();

        $branches = Branch::orderBy('name');
        $tripsQuery = TripRequest::with(['branch', 'assignedVehicle', 'assignedDriver'])
            ->orderByDesc('created_at')
            ->take(50);
        $vehicles = Vehicle::orderBy('registration_number')->get();
        $drivers = Driver::orderBy('full_name')->get();

        if (in_array($user->role, [User::ROLE_BRANCH_ADMIN, User::ROLE_BRANCH_HEAD], true)) {
            $branches->where('id', $user->branch_id);
            $tripsQuery->where('branch_id', $user->branch_id);
        }

        $branches = $branches->get();
        $trips = $tripsQuery->get();

        return view('incidents.edit', compact('incident', 'branches', 'trips', 'vehicles', 'drivers'));
    }

    public function update(UpdateIncidentReportRequest $request, IncidentReport $incident, AuditLogService $auditLog): RedirectResponse
    {
        $this->authorizeIncidentMutation($incident, $request->user());

        if ($incident->status !== IncidentReport::STATUS_OPEN) {
            return redirect()
                ->route('incidents.show', $incident)
                ->with('error', 'Only open incidents can be updated.');
        }

        $data = $request->validated();
        if (in_array($request->user()?->role, [User::ROLE_BRANCH_ADMIN, User::ROLE_BRANCH_HEAD], true)) {
            $data['branch_id'] = $incident->branch_id;
        } else {
            $data['branch_id'] = $data['branch_id'] ?? $incident->branch_id;
        }

        if (! empty($data['trip_request_id'])) {
            $trip = TripRequest::find($data['trip_request_id']);
            if ($trip && $data['branch_id'] && $trip->branch_id !== $data['branch_id']) {
                return redirect()
                    ->back()
                    ->withErrors(['trip_request_id' => 'Selected trip does not belong to this branch.'])
                    ->withInput();
            }
        }

        $attachments = $incident->attachments ?? [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments', []) as $file) {
                $attachments[] = $file->store('incidents', 'local');
            }
        }
        $data['attachments'] = $attachments ?: null;

        $oldValues = $incident->getOriginal();
        $data['updated_by_user_id'] = $request->user()->id;
        $incident->update($data);
        $auditLog->log('incident.updated', $incident, $oldValues, $incident->getChanges());

        $recipients = $this->buildRecipients($incident, $incident->reportedBy ?? $request->user());
        try {
            Notification::send($recipients, new IncidentUpdated($incident, $request->user()));
        } catch (Throwable $exception) {
            Log::warning('Incident update notification failed.', [
                'incident_id' => $incident->id,
                'error' => $exception->getMessage(),
            ]);
        }
        $this->broadcastIncidentChange($incident, 'updated');

        return redirect()
            ->route('incidents.show', $incident)
            ->with('success', 'Incident updated successfully.');
    }

    public function cancel(IncidentReport $incident, Request $request, AuditLogService $auditLog): RedirectResponse
    {
        $this->authorizeIncidentMutation($incident, $request->user());

        if ($incident->status !== IncidentReport::STATUS_OPEN) {
            return redirect()
                ->route('incidents.show', $incident)
                ->with('error', 'Only open incidents can be cancelled.');
        }

        $oldValues = $incident->getOriginal();
        $incident->update([
            'status' => IncidentReport::STATUS_CANCELLED,
            'closed_by_user_id' => $request->user()->id,
            'updated_by_user_id' => $request->user()->id,
            'closed_at' => now(),
        ]);
        $auditLog->log('incident.cancelled', $incident, $oldValues, $incident->getChanges());

        $recipients = $this->buildRecipients($incident, $incident->reportedBy ?? $request->user());
        try {
            Notification::send($recipients, new IncidentStatusUpdated($incident, $request->user()));
        } catch (Throwable $exception) {
            Log::warning('Incident cancel notification failed.', [
                'incident_id' => $incident->id,
                'error' => $exception->getMessage(),
            ]);
        }
        $this->broadcastIncidentChange($incident, 'cancelled');

        return redirect()
            ->route('incidents.index')
            ->with('success', 'Incident cancelled.');
    }

    public function updateStatus(UpdateIncidentStatusRequest $request, IncidentReport $incident, AuditLogService $auditLog): RedirectResponse
    {
        $data = $request->validated();

        $updates = [
            'status' => $data['status'],
            'resolution_notes' => $data['resolution_notes'] ?? null,
        ];

        if ($data['status'] === IncidentReport::STATUS_RESOLVED) {
            $updates['closed_by_user_id'] = $request->user()->id;
            $updates['closed_at'] = now();
        } else {
            $updates['closed_by_user_id'] = null;
            $updates['closed_at'] = null;
        }

        $oldValues = $incident->getOriginal();
        $updates['updated_by_user_id'] = $request->user()->id;
        $incident->update($updates);
        $auditLog->log('incident.status_updated', $incident, $oldValues, $incident->getChanges());

        $recipients = $this->buildRecipients($incident, $incident->reportedBy ?? $request->user());
        try {
            Notification::send($recipients, new IncidentStatusUpdated($incident, $request->user()));
        } catch (Throwable $exception) {
            Log::warning('Incident status update notification failed.', [
                'incident_id' => $incident->id,
                'error' => $exception->getMessage(),
            ]);
        }
        $this->broadcastIncidentChange($incident, 'status_updated');

        return redirect()
            ->route('incidents.show', $incident)
            ->with('success', 'Incident status updated.');
    }

    public function destroy(IncidentReport $incident, AuditLogService $auditLog): RedirectResponse
    {
        $this->authorizeIncidentMutation($incident, request()->user());

        $incident->delete();
        $auditLog->log('incident.deleted', $incident);
        $this->broadcastIncidentChangeData($incident->id, $incident->branch_id, $incident->reported_by_user_id, 'deleted');

        return redirect()
            ->route('incidents.index')
            ->with('success', 'Incident report deleted.');
    }

    public function restore(int $incident, AuditLogService $auditLog): RedirectResponse
    {
        $incidentModel = IncidentReport::onlyTrashed()->findOrFail($incident);
        $incidentModel->restore();
        $auditLog->log('incident.restored', $incidentModel);
        $this->broadcastIncidentChangeData($incidentModel->id, $incidentModel->branch_id, $incidentModel->reported_by_user_id, 'restored');

        return redirect()
            ->route('incidents.index', ['archived' => 1])
            ->with('success', 'Incident report restored.');
    }

    public function forceDelete(int $incident, AuditLogService $auditLog): RedirectResponse
    {
        $incidentModel = IncidentReport::onlyTrashed()->findOrFail($incident);
        if (! empty($incidentModel->attachments)) {
            foreach ($incidentModel->attachments as $attachment) {
                Storage::disk('local')->delete($attachment);
                Storage::disk('public')->delete($attachment);
            }
        }
        $incidentModel->forceDelete();
        $auditLog->log('incident.force_deleted', $incidentModel);
        $this->broadcastIncidentChangeData($incidentModel->id, $incidentModel->branch_id, $incidentModel->reported_by_user_id, 'force_deleted');

        return redirect()
            ->route('incidents.index', ['archived' => 1])
            ->with('success', 'Incident report permanently deleted.');
    }

    public function exportCsv(Request $request, AuditLogService $auditLog)
    {
        $showArchived = $request->boolean('archived') && $request->user()?->role === User::ROLE_SUPER_ADMIN;
        $incidents = $this->buildIncidentQuery($request, $showArchived)->get();
        $filename = 'incident-reports-' . now()->format('Ymd-His') . '.csv';
        $auditLog->log('incident.export_csv', null, [], [
            'archived' => $showArchived,
            'count' => $incidents->count(),
        ]);

        return response()->streamDownload(function () use ($incidents): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Reference', 'Branch', 'Severity', 'Status', 'Incident Date', 'Location', 'Reported By']);
            foreach ($incidents as $incident) {
                fputcsv($handle, [
                    $incident->reference,
                    $incident->branch?->name ?? 'N/A',
                    ucfirst($incident->severity),
                    str_replace('_', ' ', ucfirst($incident->status)),
                    $incident->incident_date?->format('Y-m-d'),
                    $incident->location ?? 'N/A',
                    $incident->reportedBy?->name ?? 'N/A',
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request, AuditLogService $auditLog)
    {
        $showArchived = $request->boolean('archived') && $request->user()?->role === User::ROLE_SUPER_ADMIN;
        $incidents = $this->buildIncidentQuery($request, $showArchived)->get();
        $auditLog->log('incident.export_pdf', null, [], [
            'archived' => $showArchived,
            'count' => $incidents->count(),
        ]);

        $pdf = Pdf::loadView('incidents/report-pdf', [
            'incidents' => $incidents,
            'generatedAt' => now(),
        ]);

        return $pdf->download('incident-reports-' . now()->format('Ymd-His') . '.pdf');
    }

    public function downloadAttachment(IncidentReport $incident, string $filename)
    {
        $this->authorizeIncidentView($incident, request()->user());
        $path = collect($incident->attachments ?? [])
            ->first(fn ($item) => basename($item) === $filename);

        if (! $path) {
            abort(404);
        }

        $disk = null;
        if (Storage::disk('local')->exists($path)) {
            $disk = 'local';
        } elseif (Storage::disk('public')->exists($path)) {
            $disk = 'public';
        }
        if (! $disk) {
            abort(404);
        }

        return Storage::disk($disk)->download($path);
    }

    public function previewAttachment(IncidentReport $incident, string $filename)
    {
        $this->authorizeIncidentView($incident, request()->user());
        $path = collect($incident->attachments ?? [])
            ->first(fn ($item) => basename($item) === $filename);

        if (! $path) {
            abort(404);
        }

        $disk = null;
        if (Storage::disk('local')->exists($path)) {
            $disk = 'local';
        } elseif (Storage::disk('public')->exists($path)) {
            $disk = 'public';
        }
        if (! $disk) {
            abort(404);
        }

        return Storage::disk($disk)->response($path, $filename, [
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    private function generateReference(): string
    {
        $today = now()->format('Ymd');
        $count = IncidentReport::whereDate('created_at', now()->toDateString())->count() + 1;

        return sprintf('IN-%s-%03d', $today, $count);
    }

    private function buildRecipients(IncidentReport $incident, User $reporter)
    {
        $recipients = collect();

        $recipients = $recipients->merge(
            User::where('role', User::ROLE_FLEET_MANAGER)->get()
        );

        $recipients = $recipients->merge(
            User::where('role', User::ROLE_SUPER_ADMIN)->get()
        );

        if ($incident->branch_id) {
            $recipients = $recipients->merge(
                User::where('role', User::ROLE_BRANCH_HEAD)
                    ->where('branch_id', $incident->branch_id)
                    ->get()
            );
        }

        $recipients->push($reporter);

        return $recipients->unique('id')->values();
    }

    private function buildIncidentQuery(Request $request, bool $showArchived = false)
    {
        $user = $request->user();

        $query = IncidentReport::with(['branch', 'vehicle', 'driver', 'reportedBy'])
            ->orderByDesc('incident_date');

        if ($showArchived) {
            $query->onlyTrashed();
        } elseif ($user->role === User::ROLE_BRANCH_ADMIN) {
            $query->where('reported_by_user_id', $user->id);
        }

        if ($user->role === User::ROLE_BRANCH_HEAD) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }

    private function authorizeIncidentMutation(IncidentReport $incident, ?User $user): void
    {
        if (! $user) {
            abort(403);
        }

        if (in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER], true)) {
            return;
        }

        if ($user->role === User::ROLE_BRANCH_HEAD && $user->branch_id && $incident->branch_id === $user->branch_id) {
            return;
        }

        if ($incident->reported_by_user_id === $user->id) {
            return;
        }

        abort(403);
    }

    private function authorizeIncidentView(IncidentReport $incident, ?User $user): void
    {
        if (! $user) {
            abort(403);
        }

        if (in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_FLEET_MANAGER], true)) {
            return;
        }

        if ($user->role === User::ROLE_BRANCH_HEAD && $user->branch_id && $incident->branch_id === $user->branch_id) {
            return;
        }

        if ($incident->reported_by_user_id === $user->id) {
            return;
        }

        abort(403);
    }

    private function broadcastIncidentChange(IncidentReport $incident, string $action): void
    {
        event(new IncidentReportChanged($incident->id, $incident->branch_id, $incident->reported_by_user_id, $action));
    }

    private function broadcastIncidentChangeData(int $incidentId, ?int $branchId, ?int $reporterId, string $action): void
    {
        event(new IncidentReportChanged($incidentId, $branchId, $reporterId, $action));
    }
}
