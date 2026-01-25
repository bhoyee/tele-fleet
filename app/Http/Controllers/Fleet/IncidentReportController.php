<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Incident\StoreIncidentReportRequest;
use App\Http\Requests\Incident\UpdateIncidentStatusRequest;
use App\Models\Branch;
use App\Models\Driver;
use App\Models\IncidentReport;
use App\Models\TripRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\IncidentReported;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class IncidentReportController extends Controller
{
    public function index(Request $request): View
    {
        $incidents = $this->buildIncidentQuery($request)->get();

        return view('incidents.index', compact('incidents'));
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
        Notification::send($recipients, new IncidentReported($incident));

        return redirect()
            ->route('incidents.show', $incident)
            ->with('success', 'Incident report submitted.');
    }

    public function show(IncidentReport $incident): View
    {
        $incident->load(['tripRequest', 'branch', 'vehicle', 'driver', 'reportedBy', 'closedBy']);

        return view('incidents.show', compact('incident'));
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

        return redirect()
            ->route('incidents.show', $incident)
            ->with('success', 'Incident status updated.');
    }

    public function destroy(IncidentReport $incident): RedirectResponse
    {
        if (! empty($incident->attachments)) {
            foreach ($incident->attachments as $attachment) {
                Storage::disk('public')->delete($attachment);
            }
        }

        $incident->delete();

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

        if (in_array($user->role, [User::ROLE_BRANCH_ADMIN, User::ROLE_BRANCH_HEAD], true)) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }
}
