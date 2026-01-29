<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Driver;
use App\Models\IncidentReport;
use App\Models\TripRequest;
use App\Models\Vehicle;
use App\Models\VehicleMaintenance;
use App\Services\AuditLogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function fleetReport(Request $request, AuditLogService $auditLog): View
    {
        $report = $this->buildFleetReportData($request);
        $auditLog->log('report.view', null, [], [
            'report' => 'fleet',
            'filters' => $report['filters'],
        ]);

        return view('reports.fleet', $report);
    }

    public function exportFleetReportCsv(Request $request, AuditLogService $auditLog)
    {
        $report = $this->buildFleetReportData($request);
        $branchLabel = $report['filters']['branch_label'];
        $filename = 'fleet-report-' . now()->format('Ymd-His') . '.csv';
        $auditLog->log('report.export_csv', null, [], [
            'report' => 'fleet',
            'filters' => $report['filters'],
            'rows' => [
                'trips' => $report['tables']['trips']->count(),
                'vehicles' => $report['tables']['vehicles']->count(),
                'drivers' => count($report['tables']['drivers']),
                'incidents' => $report['tables']['incidents']->count(),
                'maintenances' => $report['tables']['maintenances']->count(),
            ],
        ]);

        return response()->streamDownload(function () use ($report, $branchLabel): void {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, ['Fleet Report']);
            fputcsv($handle, ['Branch', $branchLabel]);
            fputcsv($handle, ['Range', $report['filters']['range_label']]);
            fputcsv($handle, ['Generated', now()->format('M d, Y H:i')]);
            fputcsv($handle, ['Tele-Fleet']);
            fputcsv($handle, []);

            fputcsv($handle, ['Trip Summary']);
            fputcsv($handle, ['Total Trips', $report['stats']['total_trips']]);
            fputcsv($handle, ['Completed Trips', $report['stats']['completed_trips']]);
            fputcsv($handle, ['Rejected Trips', $report['stats']['rejected_trips']]);
            fputcsv($handle, ['Pending Trips', $report['stats']['pending_trips']]);
            fputcsv($handle, ['Approval Rate (%)', $report['stats']['approval_rate']]);
            fputcsv($handle, ['Completion Rate (%)', $report['stats']['completion_rate']]);
            fputcsv($handle, ['Avg Approval (hrs)', $report['stats']['avg_approval_hours'] ?? 'N/A']);
            fputcsv($handle, ['Avg Assignment (hrs)', $report['stats']['avg_assignment_hours'] ?? 'N/A']);
            fputcsv($handle, []);

            fputcsv($handle, ['Vehicle Summary']);
            fputcsv($handle, ['Total Vehicles', $report['stats']['total_vehicles']]);
            fputcsv($handle, ['Available', $report['stats']['vehicles_available']]);
            fputcsv($handle, ['In Use', $report['stats']['vehicles_in_use']]);
            fputcsv($handle, ['Maintenance', $report['stats']['vehicles_maintenance']]);
            fputcsv($handle, ['Offline', $report['stats']['vehicles_offline']]);
            fputcsv($handle, ['Maintenance Due', $report['stats']['maintenance_due']]);
            fputcsv($handle, ['Maintenance Overdue', $report['stats']['maintenance_overdue']]);
            fputcsv($handle, []);

            fputcsv($handle, ['Driver Summary']);
            fputcsv($handle, ['Total Drivers', $report['stats']['total_drivers']]);
            fputcsv($handle, ['Active', $report['stats']['drivers_active']]);
            fputcsv($handle, ['Inactive', $report['stats']['drivers_inactive']]);
            fputcsv($handle, ['Suspended', $report['stats']['drivers_suspended']]);
            fputcsv($handle, []);

            fputcsv($handle, ['Incident Summary']);
            fputcsv($handle, ['Open', $report['stats']['incidents_open']]);
            fputcsv($handle, ['Under Review', $report['stats']['incidents_review']]);
            fputcsv($handle, ['Resolved', $report['stats']['incidents_resolved']]);
            fputcsv($handle, ['Cancelled', $report['stats']['incidents_cancelled']]);
            fputcsv($handle, []);

            fputcsv($handle, ['Trips']);
            fputcsv($handle, ['Request #', 'Branch', 'Requester', 'Trip Date', 'Status']);
            foreach ($report['tables']['trips'] as $trip) {
                fputcsv($handle, [
                    $trip->request_number,
                    $trip->branch?->name ?? 'N/A',
                    $trip->requestedBy?->name ?? 'N/A',
                    optional($trip->trip_date)->format('Y-m-d'),
                    $trip->status,
                ]);
            }
            fputcsv($handle, []);

            fputcsv($handle, ['Vehicles']);
            fputcsv($handle, ['Registration', 'Make', 'Model', 'Status', 'Maintenance State', 'Mileage']);
            foreach ($report['tables']['vehicles'] as $vehicle) {
                fputcsv($handle, [
                    $vehicle->registration_number,
                    $vehicle->make,
                    $vehicle->model,
                    $vehicle->report_status,
                    $vehicle->maintenance_state ?? 'ok',
                    $vehicle->current_mileage ?? 0,
                ]);
            }
            fputcsv($handle, []);

            fputcsv($handle, ['Drivers']);
            fputcsv($handle, ['Driver', 'Status', 'License Expiry', 'Trips In Range']);
            foreach ($report['tables']['drivers'] as $driverRow) {
                $driver = $driverRow['driver'];
                fputcsv($handle, [
                    $driver?->full_name ?? 'N/A',
                    $driver?->status ?? 'N/A',
                    $driver?->license_expiry?->format('Y-m-d') ?? 'N/A',
                    $driverRow['trips_count'] ?? 0,
                ]);
            }
            fputcsv($handle, []);

            fputcsv($handle, ['Incidents']);
            fputcsv($handle, ['Reference', 'Branch', 'Severity', 'Status', 'Incident Date']);
            foreach ($report['tables']['incidents'] as $incident) {
                fputcsv($handle, [
                    $incident->reference,
                    $incident->branch?->name ?? 'N/A',
                    $incident->severity,
                    $incident->status,
                    $incident->incident_date?->format('Y-m-d'),
                ]);
            }
            fputcsv($handle, []);

            fputcsv($handle, ['Maintenance']);
            fputcsv($handle, ['Vehicle', 'Status', 'Scheduled For', 'Started At', 'Completed At', 'Cost']);
            foreach ($report['tables']['maintenances'] as $maintenance) {
                fputcsv($handle, [
                    $maintenance->vehicle?->registration_number ?? 'N/A',
                    $maintenance->status,
                    $maintenance->scheduled_for?->format('Y-m-d'),
                    $maintenance->started_at?->format('Y-m-d H:i'),
                    $maintenance->completed_at?->format('Y-m-d H:i'),
                    $maintenance->cost ?? 'N/A',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportFleetReportPdf(Request $request, AuditLogService $auditLog)
    {
        $report = $this->buildFleetReportData($request);
        $auditLog->log('report.export_pdf', null, [], [
            'report' => 'fleet',
            'filters' => $report['filters'],
        ]);

        $pdf = Pdf::loadView('reports.fleet-pdf', [
            'report' => $report,
            'generatedAt' => now(),
        ]);

        return $pdf->download('fleet-report-' . now()->format('Ymd-His') . '.pdf');
    }

    public function customReport(Request $request, AuditLogService $auditLog): View
    {
        $report = $this->buildCustomReportData($request);
        $auditLog->log('report.view', null, [], [
            'report' => 'custom',
            'type' => $report['report_type'],
            'filters' => $report['filters'],
        ]);

        return view('reports.custom', $report);
    }

    public function exportCustomReportCsv(Request $request, AuditLogService $auditLog)
    {
        $report = $this->buildCustomReportData($request);
        $filename = $report['report_type'] . '-report-' . now()->format('Ymd-His') . '.csv';
        $auditLog->log('report.export_csv', null, [], [
            'report' => 'custom',
            'type' => $report['report_type'],
            'filters' => $report['filters'],
            'rows' => count($report['rows']),
        ]);

        return response()->streamDownload(function () use ($report): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, [$report['title']]);
            fputcsv($handle, ['Branch', $report['filters']['branch_label']]);
            fputcsv($handle, ['Range', $report['filters']['range_label']]);
            fputcsv($handle, ['Generated', now()->format('M d, Y H:i')]);
            fputcsv($handle, ['Tele-Fleet']);
            fputcsv($handle, []);

            if (! empty($report['summary'])) {
                fputcsv($handle, ['Summary']);
                foreach ($report['summary'] as $label => $value) {
                    fputcsv($handle, [$label, $value]);
                }
                fputcsv($handle, []);
            }

            fputcsv($handle, $report['columns']);
            foreach ($report['rows'] as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportCustomReportPdf(Request $request, AuditLogService $auditLog)
    {
        $report = $this->buildCustomReportData($request);
        $auditLog->log('report.export_pdf', null, [], [
            'report' => 'custom',
            'type' => $report['report_type'],
            'filters' => $report['filters'],
        ]);

        $pdf = Pdf::loadView('reports.custom-pdf', [
            'report' => $report,
            'generatedAt' => now(),
        ]);

        return $pdf->download($report['report_type'] . '-report-' . now()->format('Ymd-His') . '.pdf');
    }

    public function myRequests(Request $request, AuditLogService $auditLog): View
    {
        [$trips, $stats] = $this->buildMyRequestsReport($request);
        $auditLog->log('report.view', null, [], [
            'report' => 'my_requests',
            'filters' => $request->only(['status', 'from', 'to', 'range']),
            'count' => $trips->count(),
        ]);

        return view('reports.my-requests', [
            'trips' => $trips,
            'stats' => $stats,
        ]);
    }

    public function exportMyRequestsExcel(Request $request, AuditLogService $auditLog)
    {
        [$trips] = $this->buildMyRequestsReport($request);

        $slug = str_replace(' ', '-', strtolower($request->user()->name));
        $filename = $slug . '-report-' . now()->format('Ymd-His') . '.csv';
        $title = $request->user()->name . ' Report';
        $generatedAt = now()->format('M d, Y H:i');
        $auditLog->log('report.export_csv', null, [], [
            'report' => 'my_requests',
            'filters' => $request->only(['status', 'from', 'to', 'range']),
            'count' => $trips->count(),
        ]);

        return response()->streamDownload(function () use ($trips, $title, $generatedAt): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, [$title]);
            fputcsv($handle, ['Generated ' . $generatedAt]);
            fputcsv($handle, ['Tele-Fleet']);
            fputcsv($handle, []);
            fputcsv($handle, ['Request Number', 'Branch', 'Destination', 'Trip Date', 'Status', 'Created At']);
            foreach ($trips as $trip) {
                fputcsv($handle, [
                    $trip->request_number,
                    $trip->branch?->name ?? 'N/A',
                    $trip->destination,
                    optional($trip->trip_date)->format('Y-m-d'),
                    $this->formatStatus($trip->status),
                    $trip->created_at?->format('Y-m-d H:i'),
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportMyRequestsPdf(Request $request, AuditLogService $auditLog)
    {
        [$trips, $stats] = $this->buildMyRequestsReport($request);
        $reportTitle = $request->user()->name . ' Report';
        $auditLog->log('report.export_pdf', null, [], [
            'report' => 'my_requests',
            'filters' => $request->only(['status', 'from', 'to', 'range']),
            'count' => $trips->count(),
        ]);

        $pdf = Pdf::loadView('reports.my-requests-pdf', [
            'trips' => $trips,
            'stats' => $stats,
            'generatedAt' => now(),
            'reportTitle' => $reportTitle,
        ]);

        $slug = str_replace(' ', '-', strtolower($request->user()->name));
        return $pdf->download($slug . '-report-' . now()->format('Ymd-His') . '.pdf');
    }

    public function branchReport(Request $request, AuditLogService $auditLog): View
    {
        [$trips, $stats, $branchName] = $this->buildBranchReport($request);
        $auditLog->log('report.view', null, [], [
            'report' => 'branch',
            'filters' => $request->only(['status', 'from', 'to', 'range']),
            'branch' => $branchName,
            'count' => $trips->count(),
        ]);

        return view('reports.branch', [
            'trips' => $trips,
            'stats' => $stats,
            'branchName' => $branchName,
        ]);
    }

    public function exportBranchExcel(Request $request, AuditLogService $auditLog)
    {
        [$trips, $stats, $branchName] = $this->buildBranchReport($request);

        $slug = str_replace(' ', '-', strtolower($branchName));
        $filename = $slug . '-report-' . now()->format('Ymd-His') . '.csv';
        $title = $branchName . ' Report';
        $generatedAt = now()->format('M d, Y H:i');
        $auditLog->log('report.export_csv', null, [], [
            'report' => 'branch',
            'filters' => $request->only(['status', 'from', 'to', 'range']),
            'branch' => $branchName,
            'count' => $trips->count(),
        ]);

        return response()->streamDownload(function () use ($trips, $title, $generatedAt): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, [$title]);
            fputcsv($handle, ['Generated ' . $generatedAt]);
            fputcsv($handle, ['Tele-Fleet']);
            fputcsv($handle, []);
            fputcsv($handle, ['Request Number', 'Branch', 'Destination', 'Trip Date', 'Status', 'Created At']);
            foreach ($trips as $trip) {
                fputcsv($handle, [
                    $trip->request_number,
                    $trip->branch?->name ?? 'N/A',
                    $trip->destination,
                    optional($trip->trip_date)->format('Y-m-d'),
                    $this->formatStatus($trip->status),
                    $trip->created_at?->format('Y-m-d H:i'),
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportBranchPdf(Request $request, AuditLogService $auditLog)
    {
        [$trips, $stats, $branchName] = $this->buildBranchReport($request);
        $reportTitle = $branchName . ' Report';
        $auditLog->log('report.export_pdf', null, [], [
            'report' => 'branch',
            'filters' => $request->only(['status', 'from', 'to', 'range']),
            'branch' => $branchName,
            'count' => $trips->count(),
        ]);

        $pdf = Pdf::loadView('reports.branch-pdf', [
            'trips' => $trips,
            'stats' => $stats,
            'generatedAt' => now(),
            'reportTitle' => $reportTitle,
        ]);

        $slug = str_replace(' ', '-', strtolower($branchName));
        return $pdf->download($slug . '-report-' . now()->format('Ymd-His') . '.pdf');
    }

    private function buildMyRequestsReport(Request $request): array
    {
        $query = TripRequest::with(['branch'])
            ->where('requested_by_user_id', $request->user()->id);

        if ($request->filled('status')) {
            if ($request->status === 'approved') {
                $query->whereIn('status', ['approved', 'assigned', 'completed']);
            } elseif (in_array($request->status, ['pending', 'rejected'], true)) {
                $query->where('status', $request->status);
            }
        }

        [$from, $to] = $this->resolveDateRange($request);

        if ($from) {
            $query->whereDate('trip_date', '>=', $from);
        }

        if ($to) {
            $query->whereDate('trip_date', '<=', $to);
        }

        $trips = $query->orderByDesc('trip_date')->get();

        $stats = [
            'total' => $trips->count(),
            'pending' => $trips->where('status', 'pending')->count(),
            'rejected' => $trips->where('status', 'rejected')->count(),
            'approved' => $trips->whereIn('status', ['approved', 'assigned', 'completed'])->count(),
        ];

        return [$trips, $stats];
    }

    private function formatStatus(string $status): string
    {
        if (in_array($status, ['approved', 'assigned', 'completed'], true)) {
            return 'Approved';
        }
        if ($status === 'rejected') {
            return 'Rejected';
        }
        return 'Pending';
    }

    private function resolveDateRange(Request $request): array
    {
        if ($request->filled('from') || $request->filled('to')) {
            return [
                $request->filled('from') ? $request->from : null,
                $request->filled('to') ? $request->to : null,
            ];
        }

        $preset = $request->input('range');
        $now = now();

        if ($preset === 'today') {
            return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
        }

        if ($preset === 'week') {
            return [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()];
        }

        if ($preset === 'month') {
            return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
        }

        if ($preset === 'year') {
            return [$now->copy()->startOfYear(), $now->copy()->endOfYear()];
        }

        return [null, null];
    }

    private function buildFleetReportData(Request $request): array
    {
        $branchId = $request->input('branch_id');
        $branch = $branchId ? Branch::find($branchId) : null;
        if ($branchId && ! $branch) {
            $branchId = null;
        }
        [$from, $to] = $this->resolveDateRange($request);
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate = $to ? Carbon::parse($to)->endOfDay() : null;
        $rangeLabel = $this->buildRangeLabel($fromDate, $toDate, $request->input('range'));

        $tripQuery = TripRequest::with(['branch', 'requestedBy'])
            ->when($branchId, function ($query) use ($branchId): void {
                $query->where('branch_id', $branchId);
            })
            ->when($fromDate, function ($query) use ($fromDate): void {
                $query->whereDate('trip_date', '>=', $fromDate);
            })
            ->when($toDate, function ($query) use ($toDate): void {
                $query->whereDate('trip_date', '<=', $toDate);
            });

        $totalTrips = (clone $tripQuery)->count();
        $completedTrips = (clone $tripQuery)->where('status', 'completed')->count();
        $rejectedTrips = (clone $tripQuery)->where('status', 'rejected')->count();
        $pendingTrips = (clone $tripQuery)->where('status', 'pending')->count();
        $assignedTrips = (clone $tripQuery)->where('status', 'assigned')->count();
        $approvedTrips = (clone $tripQuery)->whereIn('status', ['approved', 'assigned', 'completed'])->count();
        $cancelledTrips = (clone $tripQuery)->where('status', 'cancelled')->count();

        $avgApprovalHours = (clone $tripQuery)
            ->whereNotNull('approved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, approved_at)) as avg_hours')
            ->value('avg_hours');

        $avgAssignmentHours = (clone $tripQuery)
            ->whereNotNull('assigned_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, approved_at, assigned_at)) as avg_hours')
            ->value('avg_hours');

        $tripRows = (clone $tripQuery)
            ->orderByDesc('trip_date')
            ->limit(50)
            ->get();

        $now = now();
        $today = $now->toDateString();
        $activeAssignedVehicleIds = TripRequest::whereNotNull('assigned_vehicle_id')
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

        $vehicleQuery = Vehicle::query()
            ->when($branchId, function ($query) use ($branchId): void {
                $query->where('branch_id', $branchId);
            })
            ->orderBy('registration_number');
        $vehicles = $vehicleQuery->get();
        $vehicleStatusCounts = [
            'available' => 0,
            'in_use' => 0,
            'maintenance' => 0,
            'offline' => 0,
        ];
        foreach ($vehicles as $vehicle) {
            $displayStatus = $vehicle->status;
            if (! in_array($vehicle->status, ['maintenance', 'offline'], true)) {
                $displayStatus = $activeAssignedVehicleIds->contains($vehicle->id) ? 'in_use' : 'available';
            }
            $vehicleStatusCounts[$displayStatus] = ($vehicleStatusCounts[$displayStatus] ?? 0) + 1;
            $vehicle->report_status = $displayStatus;
        }

        $vehiclesTable = $vehicles->take(20);
        $totalVehicles = $vehicles->count();
        $maintenanceDue = Vehicle::when($branchId, function ($query) use ($branchId): void {
                $query->where('branch_id', $branchId);
            })
            ->whereIn('maintenance_state', ['due', 'overdue'])
            ->count();
        $maintenanceOverdue = Vehicle::when($branchId, function ($query) use ($branchId): void {
                $query->where('branch_id', $branchId);
            })
            ->where('maintenance_state', 'overdue')
            ->count();

        $driversQuery = Driver::query()
            ->when($branchId, function ($query) use ($branchId): void {
                $query->where('branch_id', $branchId);
            });
        $drivers = $driversQuery->get();
        $totalDrivers = $drivers->where('status', '!=', 'suspended')->count();
        $driversActive = $drivers->where('status', 'active')->count();
        $driversInactive = $drivers->where('status', 'inactive')->count();
        $driversSuspended = $drivers->where('status', 'suspended')->count();

        $driverTripRows = (clone $tripQuery)
            ->whereNotNull('assigned_driver_id')
            ->select('assigned_driver_id', DB::raw('COUNT(*) as trips_count'))
            ->groupBy('assigned_driver_id')
            ->orderByDesc('trips_count')
            ->limit(10)
            ->get();
        $driverIds = $driverTripRows->pluck('assigned_driver_id')->all();
        $driverLookup = Driver::whereIn('id', $driverIds)
            ->when($branchId, function ($query) use ($branchId): void {
                $query->where('branch_id', $branchId);
            })
            ->get()
            ->keyBy('id');
        $driverPerformance = $driverTripRows->map(function ($row) use ($driverLookup): array {
            return [
                'driver' => $driverLookup->get($row->assigned_driver_id),
                'trips_count' => (int) $row->trips_count,
            ];
        });

        $incidentQuery = IncidentReport::with(['branch'])
            ->when($branchId, function ($query) use ($branchId): void {
                $query->where('branch_id', $branchId);
            })
            ->when($fromDate, function ($query) use ($fromDate): void {
                $query->whereDate('incident_date', '>=', $fromDate);
            })
            ->when($toDate, function ($query) use ($toDate): void {
                $query->whereDate('incident_date', '<=', $toDate);
            });

        $incidentsOpen = (clone $incidentQuery)->where('status', IncidentReport::STATUS_OPEN)->count();
        $incidentsReview = (clone $incidentQuery)->where('status', IncidentReport::STATUS_REVIEW)->count();
        $incidentsResolved = (clone $incidentQuery)->where('status', IncidentReport::STATUS_RESOLVED)->count();
        $incidentsCancelled = (clone $incidentQuery)->where('status', IncidentReport::STATUS_CANCELLED)->count();
        $incidentRows = (clone $incidentQuery)->orderByDesc('incident_date')->limit(20)->get();

        $branchTripCounts = (clone $tripQuery)
            ->select('branch_id', DB::raw('COUNT(*) as total'))
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');
        $branchDriverUsageCounts = (clone $tripQuery)
            ->whereNotNull('assigned_driver_id')
            ->select('branch_id', DB::raw('COUNT(*) as total'))
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');
        $branchIncidentCounts = (clone $incidentQuery)
            ->select('branch_id', DB::raw('COUNT(*) as total'))
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');

        $branchMetrics = Branch::orderBy('name')->get()->map(function (Branch $branch) use (
            $branchTripCounts,
            $branchDriverUsageCounts,
            $branchIncidentCounts
        ): array {
            return [
                'branch' => $branch->name,
                'branch_id' => $branch->id,
                'trips' => (int) ($branchTripCounts[$branch->id]->total ?? 0),
                'driver_usage' => (int) ($branchDriverUsageCounts[$branch->id]->total ?? 0),
                'incidents' => (int) ($branchIncidentCounts[$branch->id]->total ?? 0),
            ];
        });

        $topTripsBranches = $branchMetrics->sortByDesc('trips')->values()->take(3);
        $topDriverUsageBranches = $branchMetrics->sortByDesc('driver_usage')->values()->take(3);
        $topIncidentBranches = $branchMetrics->sortByDesc('incidents')->values()->take(3);

        if ($branchId) {
            $branchMetrics = $branchMetrics->where('branch_id', (int) $branchId)->values();
            $topTripsBranches = $branchMetrics->take(1);
            $topDriverUsageBranches = $branchMetrics->take(1);
            $topIncidentBranches = $branchMetrics->take(1);
        }

        $maintenanceQuery = VehicleMaintenance::with(['vehicle', 'branch'])
            ->when($branchId, function ($query) use ($branchId): void {
                $query->where('branch_id', $branchId);
            })
            ->when($fromDate, function ($query) use ($fromDate): void {
                $query->whereDate('scheduled_for', '>=', $fromDate);
            })
            ->when($toDate, function ($query) use ($toDate): void {
                $query->whereDate('scheduled_for', '<=', $toDate);
            });

        $maintenancesScheduled = (clone $maintenanceQuery)->where('status', VehicleMaintenance::STATUS_SCHEDULED)->count();
        $maintenancesInProgress = (clone $maintenanceQuery)->where('status', VehicleMaintenance::STATUS_IN_PROGRESS)->count();
        $maintenancesCompleted = (clone $maintenanceQuery)->where('status', VehicleMaintenance::STATUS_COMPLETED)->count();
        $maintenanceRows = (clone $maintenanceQuery)->orderByDesc('scheduled_for')->limit(20)->get();

        $approvalRate = $totalTrips ? round(($approvedTrips / $totalTrips) * 100, 1) : 0;
        $completionRate = $totalTrips ? round(($completedTrips / $totalTrips) * 100, 1) : 0;

        return [
            'branches' => Branch::orderBy('name')->get(),
            'filters' => [
                'range' => $request->input('range'),
                'from' => $fromDate?->toDateString(),
                'to' => $toDate?->toDateString(),
                'branch_id' => $branchId,
                'branch_label' => $branch?->name ?? 'All Branches',
                'range_label' => $rangeLabel,
            ],
            'stats' => [
                'total_trips' => $totalTrips,
                'completed_trips' => $completedTrips,
                'rejected_trips' => $rejectedTrips,
                'pending_trips' => $pendingTrips,
                'assigned_trips' => $assignedTrips,
                'approved_trips' => $approvedTrips,
                'cancelled_trips' => $cancelledTrips,
                'approval_rate' => $approvalRate,
                'completion_rate' => $completionRate,
                'avg_approval_hours' => $avgApprovalHours ? round($avgApprovalHours, 1) : null,
                'avg_assignment_hours' => $avgAssignmentHours ? round($avgAssignmentHours, 1) : null,
                'total_vehicles' => $totalVehicles,
                'vehicles_available' => $vehicleStatusCounts['available'],
                'vehicles_in_use' => $vehicleStatusCounts['in_use'],
                'vehicles_maintenance' => $vehicleStatusCounts['maintenance'],
                'vehicles_offline' => $vehicleStatusCounts['offline'],
                'maintenance_due' => $maintenanceDue,
                'maintenance_overdue' => $maintenanceOverdue,
                'total_drivers' => $totalDrivers,
                'drivers_active' => $driversActive,
                'drivers_inactive' => $driversInactive,
                'drivers_suspended' => $driversSuspended,
                'incidents_open' => $incidentsOpen,
                'incidents_review' => $incidentsReview,
                'incidents_resolved' => $incidentsResolved,
                'incidents_cancelled' => $incidentsCancelled,
                'maintenances_scheduled' => $maintenancesScheduled,
                'maintenances_in_progress' => $maintenancesInProgress,
                'maintenances_completed' => $maintenancesCompleted,
            ],
            'charts' => [
                'trip_status' => [
                    'labels' => ['Pending', 'Approved', 'Assigned', 'Completed', 'Rejected', 'Cancelled'],
                    'values' => [$pendingTrips, $approvedTrips, $assignedTrips, $completedTrips, $rejectedTrips, $cancelledTrips],
                ],
                'vehicle_status' => [
                    'labels' => ['Available', 'In Use', 'Maintenance', 'Offline'],
                    'values' => [
                        $vehicleStatusCounts['available'],
                        $vehicleStatusCounts['in_use'],
                        $vehicleStatusCounts['maintenance'],
                        $vehicleStatusCounts['offline'],
                    ],
                ],
                'driver_status' => [
                    'labels' => ['Active', 'Inactive', 'Suspended'],
                    'values' => [$driversActive, $driversInactive, $driversSuspended],
                ],
                'incident_status' => [
                    'labels' => ['Open', 'Under Review', 'Resolved', 'Cancelled'],
                    'values' => [$incidentsOpen, $incidentsReview, $incidentsResolved, $incidentsCancelled],
                ],
                'branch_trips' => [
                    'labels' => $branchMetrics->pluck('branch')->all(),
                    'values' => $branchMetrics->pluck('trips')->all(),
                ],
                'branch_driver_usage' => [
                    'labels' => $branchMetrics->pluck('branch')->all(),
                    'values' => $branchMetrics->pluck('driver_usage')->all(),
                ],
                'branch_incidents' => [
                    'labels' => $branchMetrics->pluck('branch')->all(),
                    'values' => $branchMetrics->pluck('incidents')->all(),
                ],
            ],
            'rankings' => [
                'top_trips' => $topTripsBranches,
                'top_driver_usage' => $topDriverUsageBranches,
                'top_incidents' => $topIncidentBranches,
                'branch_table' => $branchMetrics->values(),
            ],
            'tables' => [
                'trips' => $tripRows,
                'vehicles' => $vehiclesTable,
                'drivers' => $driverPerformance,
                'incidents' => $incidentRows,
                'maintenances' => $maintenanceRows,
            ],
        ];
    }

    private function buildCustomReportData(Request $request): array
    {
        $type = $request->input('type', 'trips');
        $allowedTypes = ['trips', 'vehicles', 'drivers', 'incidents', 'maintenance'];
        if (! in_array($type, $allowedTypes, true)) {
            $type = 'trips';
        }

        $branchId = $request->input('branch_id');
        $branch = $branchId ? Branch::find($branchId) : null;
        if ($branchId && ! $branch) {
            $branchId = null;
        }

        [$from, $to] = $this->resolveDateRange($request);
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate = $to ? Carbon::parse($to)->endOfDay() : null;
        $rangeLabel = $this->buildRangeLabel($fromDate, $toDate, $request->input('range'));

        $summary = [];
        $columns = [];
        $rows = [];

        if ($type === 'trips') {
            $tripQuery = TripRequest::with(['branch', 'requestedBy'])
                ->when($branchId, function ($query) use ($branchId): void {
                    $query->where('branch_id', $branchId);
                })
                ->when($fromDate, function ($query) use ($fromDate): void {
                    $query->whereDate('trip_date', '>=', $fromDate);
                })
                ->when($toDate, function ($query) use ($toDate): void {
                    $query->whereDate('trip_date', '<=', $toDate);
                })
                ->orderByDesc('trip_date');

            $trips = $tripQuery->get();
            $summary = [
                'Total Trips' => $trips->count(),
                'Pending' => $trips->where('status', 'pending')->count(),
                'Approved' => $trips->whereIn('status', ['approved', 'assigned', 'completed'])->count(),
                'Rejected' => $trips->where('status', 'rejected')->count(),
                'Completed' => $trips->where('status', 'completed')->count(),
            ];
            $columns = ['Request #', 'Branch', 'Requester', 'Trip Date', 'Status'];
            $rows = $trips->map(function (TripRequest $trip): array {
                return [
                    $trip->request_number,
                    $trip->branch?->name ?? 'N/A',
                    $trip->requestedBy?->name ?? 'N/A',
                    $trip->trip_date?->format('Y-m-d'),
                    ucfirst($trip->status),
                ];
            })->all();
        } elseif ($type === 'vehicles') {
            $now = now();
            $today = $now->toDateString();
            $activeAssignedVehicleIds = TripRequest::whereNotNull('assigned_vehicle_id')
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

            $vehicleQuery = Vehicle::when($branchId, function ($query) use ($branchId): void {
                $query->where('branch_id', $branchId);
            });
            if ($fromDate) {
                $vehicleQuery->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $vehicleQuery->whereDate('created_at', '<=', $toDate);
            }
            $vehicles = $vehicleQuery->orderBy('registration_number')->get();

            $statusCounts = [
                'available' => 0,
                'in_use' => 0,
                'maintenance' => 0,
                'offline' => 0,
            ];

            $columns = ['Registration', 'Make', 'Model', 'Status', 'Maintenance', 'Mileage'];
            $rows = $vehicles->map(function (Vehicle $vehicle) use ($activeAssignedVehicleIds, &$statusCounts): array {
                $displayStatus = $vehicle->status;
                if (! in_array($vehicle->status, ['maintenance', 'offline'], true)) {
                    $displayStatus = $activeAssignedVehicleIds->contains($vehicle->id) ? 'in_use' : 'available';
                }
                $statusCounts[$displayStatus] = ($statusCounts[$displayStatus] ?? 0) + 1;
                return [
                    $vehicle->registration_number,
                    $vehicle->make,
                    $vehicle->model,
                    ucfirst(str_replace('_', ' ', $displayStatus)),
                    ucfirst($vehicle->maintenance_state ?? 'ok'),
                    $vehicle->current_mileage ?? 0,
                ];
            })->all();

            $summary = [
                'Total Vehicles' => $vehicles->count(),
                'Available' => $statusCounts['available'],
                'In Use' => $statusCounts['in_use'],
                'Maintenance' => $statusCounts['maintenance'],
                'Offline' => $statusCounts['offline'],
            ];
        } elseif ($type === 'drivers') {
            $driverQuery = Driver::when($branchId, function ($query) use ($branchId): void {
                $query->where('branch_id', $branchId);
            });
            if ($fromDate) {
                $driverQuery->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $driverQuery->whereDate('created_at', '<=', $toDate);
            }
            $drivers = $driverQuery->orderBy('full_name')->get();

            $summary = [
                'Total Drivers' => $drivers->count(),
                'Active' => $drivers->where('status', 'active')->count(),
                'Inactive' => $drivers->where('status', 'inactive')->count(),
                'Suspended' => $drivers->where('status', 'suspended')->count(),
            ];

            $tripCountQuery = TripRequest::query()
                ->whereNotNull('assigned_driver_id')
                ->when($branchId, function ($query) use ($branchId): void {
                    $query->where('branch_id', $branchId);
                })
                ->when($fromDate, function ($query) use ($fromDate): void {
                    $query->whereDate('trip_date', '>=', $fromDate);
                })
                ->when($toDate, function ($query) use ($toDate): void {
                    $query->whereDate('trip_date', '<=', $toDate);
                })
                ->select('assigned_driver_id', DB::raw('COUNT(*) as trips_count'))
                ->groupBy('assigned_driver_id')
                ->get();

            $tripCounts = $tripCountQuery->pluck('trips_count', 'assigned_driver_id');

            $columns = ['Driver', 'Status', 'License Expiry', 'Phone', 'Trips in Range'];
            $rows = $drivers->map(function (Driver $driver) use ($tripCounts): array {
                return [
                    $driver->full_name,
                    ucfirst($driver->status),
                    $driver->license_expiry?->format('Y-m-d') ?? 'N/A',
                    $driver->phone ?? 'N/A',
                    (int) ($tripCounts[$driver->id] ?? 0),
                ];
            })->all();
        } elseif ($type === 'incidents') {
            $incidentQuery = IncidentReport::with(['branch'])
                ->when($branchId, function ($query) use ($branchId): void {
                    $query->where('branch_id', $branchId);
                })
                ->when($fromDate, function ($query) use ($fromDate): void {
                    $query->whereDate('incident_date', '>=', $fromDate);
                })
                ->when($toDate, function ($query) use ($toDate): void {
                    $query->whereDate('incident_date', '<=', $toDate);
                })
                ->orderByDesc('incident_date');

            $incidents = $incidentQuery->get();

            $summary = [
                'Open' => $incidents->where('status', IncidentReport::STATUS_OPEN)->count(),
                'Under Review' => $incidents->where('status', IncidentReport::STATUS_REVIEW)->count(),
                'Resolved' => $incidents->where('status', IncidentReport::STATUS_RESOLVED)->count(),
                'Cancelled' => $incidents->where('status', IncidentReport::STATUS_CANCELLED)->count(),
            ];

            $columns = ['Reference', 'Branch', 'Severity', 'Status', 'Incident Date'];
            $rows = $incidents->map(function (IncidentReport $incident): array {
                return [
                    $incident->reference,
                    $incident->branch?->name ?? 'N/A',
                    ucfirst($incident->severity),
                    ucfirst(str_replace('_', ' ', $incident->status)),
                    $incident->incident_date?->format('Y-m-d'),
                ];
            })->all();
        } else {
            $maintenanceQuery = VehicleMaintenance::with(['vehicle', 'branch'])
                ->when($branchId, function ($query) use ($branchId): void {
                    $query->where('branch_id', $branchId);
                })
                ->when($fromDate, function ($query) use ($fromDate): void {
                    $query->whereDate('scheduled_for', '>=', $fromDate);
                })
                ->when($toDate, function ($query) use ($toDate): void {
                    $query->whereDate('scheduled_for', '<=', $toDate);
                })
                ->orderByDesc('scheduled_for');

            $maintenances = $maintenanceQuery->get();

            $summary = [
                'Scheduled' => $maintenances->where('status', VehicleMaintenance::STATUS_SCHEDULED)->count(),
                'In Progress' => $maintenances->where('status', VehicleMaintenance::STATUS_IN_PROGRESS)->count(),
                'Completed' => $maintenances->where('status', VehicleMaintenance::STATUS_COMPLETED)->count(),
                'Cancelled' => $maintenances->where('status', VehicleMaintenance::STATUS_CANCELLED)->count(),
            ];

            $columns = ['Vehicle', 'Status', 'Scheduled For', 'Started At', 'Completed At', 'Cost'];
            $rows = $maintenances->map(function (VehicleMaintenance $maintenance): array {
                return [
                    $maintenance->vehicle?->registration_number ?? 'N/A',
                    ucfirst(str_replace('_', ' ', $maintenance->status)),
                    $maintenance->scheduled_for?->format('Y-m-d'),
                    $maintenance->started_at?->format('Y-m-d H:i') ?? 'N/A',
                    $maintenance->completed_at?->format('Y-m-d H:i') ?? 'N/A',
                    $maintenance->cost !== null ? number_format($maintenance->cost, 2) : 'N/A',
                ];
            })->all();
        }

        return [
            'branches' => Branch::orderBy('name')->get(),
            'report_type' => $type,
            'title' => ucfirst($type) . ' Report',
            'filters' => [
                'range' => $request->input('range'),
                'from' => $fromDate?->toDateString(),
                'to' => $toDate?->toDateString(),
                'branch_id' => $branchId,
                'branch_label' => $branch?->name ?? 'All Branches',
                'range_label' => $rangeLabel,
            ],
            'summary' => $summary,
            'columns' => $columns,
            'rows' => $rows,
        ];
    }

    private function buildRangeLabel(?Carbon $fromDate, ?Carbon $toDate, ?string $preset): string
    {
        if ($preset === 'today') {
            return 'Today';
        }
        if ($preset === 'week') {
            return 'This Week';
        }
        if ($preset === 'month') {
            return 'This Month';
        }
        if ($preset === 'year') {
            return 'This Year';
        }
        if ($fromDate && $toDate) {
            return $fromDate->format('M d, Y') . ' - ' . $toDate->format('M d, Y');
        }
        if ($fromDate) {
            return 'From ' . $fromDate->format('M d, Y');
        }
        if ($toDate) {
            return 'Up to ' . $toDate->format('M d, Y');
        }
        return 'All Time';
    }

    private function buildBranchReport(Request $request): array
    {
        $user = $request->user();
        $branchName = $user->branch?->name ?? 'Branch Report';

        $query = TripRequest::with(['branch', 'requestedBy'])
            ->where('branch_id', $user->branch_id)
            ->whereIn('requested_by_user_id', function ($sub) use ($user): void {
                $sub->select('id')
                    ->from('users')
                    ->where('branch_id', $user->branch_id)
                    ->whereIn('role', ['branch_admin', 'branch_head']);
            });

        if ($request->filled('status')) {
            if ($request->status === 'approved') {
                $query->whereIn('status', ['approved', 'assigned', 'completed']);
            } elseif (in_array($request->status, ['pending', 'rejected'], true)) {
                $query->where('status', $request->status);
            }
        }

        [$from, $to] = $this->resolveDateRange($request);

        if ($from) {
            $query->whereDate('trip_date', '>=', $from);
        }

        if ($to) {
            $query->whereDate('trip_date', '<=', $to);
        }

        $trips = $query->orderByDesc('trip_date')->get();

        $stats = [
            'total' => $trips->count(),
            'pending' => $trips->where('status', 'pending')->count(),
            'rejected' => $trips->where('status', 'rejected')->count(),
            'approved' => $trips->whereIn('status', ['approved', 'assigned', 'completed'])->count(),
        ];

        return [$trips, $stats, $branchName];
    }
}
