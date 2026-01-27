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
        $incidents = $this->buildIncidentQuery($request)->get();

        return view('incidents.index', compact('incidents'));
    }

    public function indexData(Request $request): JsonResponse
    {
        $incidents = $this->buildIncidentQuery($request)->get();

        $payload = $incidents->map(function (IncidentReport $incident): array {
            return [
                'id' => $incident->id,
                'reference' => $incident->reference,
                'severity' => $incident->severity,
                'status' => $incident->status,
                'incident_date' => $incident->incident_date?->format('M d, Y') ?? '',
                'branch_id' => $incident->branch_id,
                'reported_by_user_id' => $incident->reported_by_user_id,
            ];
        });

        return response()->json([
            'data' => $payload,
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        $branches = Branch::orderBy('name')->get();
        $trips = TripRequest::with(['branch', 'assignedVehicle', 'assignedDriver'])
            ->orderByDesc('created_at')
            ->take(50)
            ->get();
        $vehicles = Vehicle::orderBy('registration_number')->get();
        $drivers = Driver::orderBy('full_name')->get();

        if (in_array($user->role, [User::ROLE_BRANCH_ADMIN, User::ROLE_BRANCH_HEAD], true)) {
            $branches = $branches->where('id', $user->branch_id);
            $trips = $trips->where('branch_id', $user->branch_id);
            $vehicles = $vehicles->where('branch_id', $user->branch_id);
            $drivers = $drivers->where('branch_id', $user->branch_id);
        }

        return view('incidents.create', compact('branches', 'trips', 'vehicles', 'drivers'));
    }

    public function store(StoreIncidentReportRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $data['reported_by_user_id'] = $user->id;
        $data['branch_id'] = $data['branch_id'] ?? $user->branch_id;
        $data['reference'] = $this->generateReference();
        $data['status'] = IncidentReport::STATUS_OPEN;

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments', []) as $file) {
                $attachments[] = $file->store('incidents', 'public');
            }
        }
        $data['attachments'] = $attachments ?: null;

        $incident = IncidentReport::create($data);

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
        $incident->load(['tripRequest', 'branch', 'vehicle', 'driver', 'reportedBy', 'closedBy']);

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

        $branches = Branch::orderBy('name')->get();
        $trips = TripRequest::with(['branch', 'assignedVehicle', 'assignedDriver'])
            ->orderByDesc('created_at')
            ->take(50)
            ->get();
        $vehicles = Vehicle::orderBy('registration_number')->get();
        $drivers = Driver::orderBy('full_name')->get();

        if (in_array($user->role, [User::ROLE_BRANCH_ADMIN, User::ROLE_BRANCH_HEAD], true)) {
            $branches = $branches->where('id', $user->branch_id);
            $trips = $trips->where('branch_id', $user->branch_id);
            $vehicles = $vehicles->where('branch_id', $user->branch_id);
            $drivers = $drivers->where('branch_id', $user->branch_id);
        }

        return view('incidents.edit', compact('incident', 'branches', 'trips', 'vehicles', 'drivers'));
    }

    public function update(UpdateIncidentReportRequest $request, IncidentReport $incident): RedirectResponse
    {
        $this->authorizeIncidentMutation($incident, $request->user());

        if ($incident->status !== IncidentReport::STATUS_OPEN) {
            return redirect()
                ->route('incidents.show', $incident)
                ->with('error', 'Only open incidents can be updated.');
        }

        $data = $request->validated();
        $data['branch_id'] = $data['branch_id'] ?? $incident->branch_id;

        $attachments = $incident->attachments ?? [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments', []) as $file) {
                $attachments[] = $file->store('incidents', 'public');
            }
        }
        $data['attachments'] = $attachments ?: null;

        $incident->update($data);

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

    public function cancel(IncidentReport $incident, Request $request): RedirectResponse
    {
        $this->authorizeIncidentMutation($incident, $request->user());

        if ($incident->status !== IncidentReport::STATUS_OPEN) {
            return redirect()
                ->route('incidents.show', $incident)
                ->with('error', 'Only open incidents can be cancelled.');
        }

        $incident->update([
            'status' => IncidentReport::STATUS_CANCELLED,
            'closed_by_user_id' => $request->user()->id,
            'closed_at' => now(),
        ]);

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

    public function updateStatus(UpdateIncidentStatusRequest $request, IncidentReport $incident): RedirectResponse
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

        $incident->update($updates);

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

    public function destroy(IncidentReport $incident): RedirectResponse
    {
        $this->authorizeIncidentMutation($incident, request()->user());

        if ($incident->status !== IncidentReport::STATUS_OPEN) {
            return redirect()
                ->route('incidents.index')
                ->with('error', 'Only open incidents can be deleted.');
        }

        if (! empty($incident->attachments)) {
            foreach ($incident->attachments as $attachment) {
                Storage::disk('public')->delete($attachment);
            }
        }

        $incident->delete();
        $this->broadcastIncidentChangeData($incident->id, $incident->branch_id, $incident->reported_by_user_id, 'deleted');

        return redirect()
            ->route('incidents.index')
            ->with('success', 'Incident report deleted.');
    }

    public function exportCsv(Request $request)
    {
        $incidents = $this->buildIncidentQuery($request)->get();
        $filename = 'incident-reports-' . now()->format('Ymd-His') . '.csv';

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

    public function exportPdf(Request $request)
    {
        $incidents = $this->buildIncidentQuery($request)->get();

        $pdf = Pdf::loadView('incidents/report-pdf', [
            'incidents' => $incidents,
            'generatedAt' => now(),
        ]);

        return $pdf->download('incident-reports-' . now()->format('Ymd-His') . '.pdf');
    }

    public function downloadAttachment(IncidentReport $incident, string $filename)
    {
        $path = collect($incident->attachments ?? [])
            ->first(fn ($item) => basename($item) === $filename);

        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->download($path);
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

    private function buildIncidentQuery(Request $request)
    {
        $user = $request->user();

        $query = IncidentReport::with(['branch', 'vehicle', 'driver', 'reportedBy'])
            ->orderByDesc('incident_date');

        if ($user->role === User::ROLE_BRANCH_ADMIN) {
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
