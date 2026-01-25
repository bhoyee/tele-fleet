<?php

namespace App\Http\Controllers;

use App\Models\TripRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function myRequests(Request $request): View
    {
        [$trips, $stats] = $this->buildMyRequestsReport($request);

        return view('reports.my-requests', [
            'trips' => $trips,
            'stats' => $stats,
        ]);
    }

    public function exportMyRequestsExcel(Request $request)
    {
        [$trips] = $this->buildMyRequestsReport($request);

        $slug = str_replace(' ', '-', strtolower($request->user()->name));
        $filename = $slug . '-report-' . now()->format('Ymd-His') . '.csv';
        $title = $request->user()->name . ' Report';
        $generatedAt = now()->format('M d, Y H:i');

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

    public function exportMyRequestsPdf(Request $request)
    {
        [$trips, $stats] = $this->buildMyRequestsReport($request);
        $reportTitle = $request->user()->name . ' Report';

        $pdf = Pdf::loadView('reports.my-requests-pdf', [
            'trips' => $trips,
            'stats' => $stats,
            'generatedAt' => now(),
            'reportTitle' => $reportTitle,
        ]);

        $slug = str_replace(' ', '-', strtolower($request->user()->name));
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
}
