<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>{{ $report['filters']['branch_label'] }} Fleet Report</title>
        <style>
            body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2937; font-size: 12px; }
            h1 { font-size: 20px; margin-bottom: 4px; }
            h2 { font-size: 14px; margin: 20px 0 6px; }
            .meta { font-size: 11px; color: #6b7280; margin-bottom: 12px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
            th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
            th { background: #f3f4f6; font-weight: 600; }
            .summary-grid { width: 100%; margin-bottom: 12px; }
            .summary-grid td { border: none; padding: 4px 8px; }
        </style>
    </head>
    <body>
        <h1>Fleet Report</h1>
        <div class="meta">
            Branch: {{ $report['filters']['branch_label'] }} |
            Range: {{ $report['filters']['range_label'] }} |
            Generated: {{ $generatedAt->format('M d, Y H:i') }}
        </div>

        <table class="summary-grid">
            <tr>
                <td><strong>Total Trips:</strong> {{ $report['stats']['total_trips'] }}</td>
                <td><strong>Completed:</strong> {{ $report['stats']['completed_trips'] }}</td>
                <td><strong>Rejected:</strong> {{ $report['stats']['rejected_trips'] }}</td>
                <td><strong>Pending:</strong> {{ $report['stats']['pending_trips'] }}</td>
            </tr>
            <tr>
                <td><strong>Approval Rate:</strong> {{ $report['stats']['approval_rate'] }}%</td>
                <td><strong>Completion Rate:</strong> {{ $report['stats']['completion_rate'] }}%</td>
                <td><strong>Avg Approval:</strong> {{ $report['stats']['avg_approval_hours'] ?? 'N/A' }} hrs</td>
                <td><strong>Avg Assignment:</strong> {{ $report['stats']['avg_assignment_hours'] ?? 'N/A' }} hrs</td>
            </tr>
            <tr>
                <td><strong>Vehicles:</strong> {{ $report['stats']['vehicles_available'] }}/{{ $report['stats']['total_vehicles'] }} available</td>
                <td><strong>In Use:</strong> {{ $report['stats']['vehicles_in_use'] }}</td>
                <td><strong>Maintenance:</strong> {{ $report['stats']['vehicles_maintenance'] }}</td>
                <td><strong>Offline:</strong> {{ $report['stats']['vehicles_offline'] }}</td>
            </tr>
            <tr>
                <td><strong>Drivers Active:</strong> {{ $report['stats']['drivers_active'] }}</td>
                <td><strong>Drivers Inactive:</strong> {{ $report['stats']['drivers_inactive'] }}</td>
                <td><strong>Drivers Suspended:</strong> {{ $report['stats']['drivers_suspended'] }}</td>
                <td><strong>Incidents Open:</strong> {{ $report['stats']['incidents_open'] }}</td>
            </tr>
        </table>

        <h2>Trips</h2>
        <table>
            <thead>
                <tr>
                    <th>Request #</th>
                    <th>Branch</th>
                    <th>Requester</th>
                    <th>Trip Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['tables']['trips'] as $trip)
                    <tr>
                        <td>{{ $trip->request_number }}</td>
                        <td>{{ $trip->branch?->name ?? 'N/A' }}</td>
                        <td>{{ $trip->requestedBy?->name ?? 'N/A' }}</td>
                        <td>{{ $trip->trip_date?->format('M d, Y') }}</td>
                        <td>{{ ucfirst($trip->status) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h2>Vehicles</h2>
        <table>
            <thead>
                <tr>
                    <th>Registration</th>
                    <th>Make</th>
                    <th>Model</th>
                    <th>Status</th>
                    <th>Maintenance</th>
                    <th>Mileage</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['tables']['vehicles'] as $vehicle)
                    <tr>
                        <td>{{ $vehicle->registration_number }}</td>
                        <td>{{ $vehicle->make }}</td>
                        <td>{{ $vehicle->model }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $vehicle->report_status)) }}</td>
                        <td>{{ ucfirst($vehicle->maintenance_state ?? 'ok') }}</td>
                        <td>{{ number_format($vehicle->current_mileage ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h2>Drivers</h2>
        <table>
            <thead>
                <tr>
                    <th>Driver</th>
                    <th>Status</th>
                    <th>License Expiry</th>
                    <th>Trips</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['tables']['drivers'] as $driverRow)
                    <tr>
                        <td>{{ $driverRow['driver']?->full_name ?? 'N/A' }}</td>
                        <td>{{ ucfirst($driverRow['driver']?->status ?? 'N/A') }}</td>
                        <td>{{ $driverRow['driver']?->license_expiry?->format('M d, Y') ?? 'N/A' }}</td>
                        <td>{{ $driverRow['trips_count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h2>Incidents</h2>
        <table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Branch</th>
                    <th>Severity</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['tables']['incidents'] as $incident)
                    <tr>
                        <td>{{ $incident->reference }}</td>
                        <td>{{ $incident->branch?->name ?? 'N/A' }}</td>
                        <td>{{ ucfirst($incident->severity) }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $incident->status)) }}</td>
                        <td>{{ $incident->incident_date?->format('M d, Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h2>Maintenance</h2>
        <table>
            <thead>
                <tr>
                    <th>Vehicle</th>
                    <th>Status</th>
                    <th>Scheduled For</th>
                    <th>Started At</th>
                    <th>Completed At</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['tables']['maintenances'] as $maintenance)
                    <tr>
                        <td>{{ $maintenance->vehicle?->registration_number ?? 'N/A' }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $maintenance->status)) }}</td>
                        <td>{{ $maintenance->scheduled_for?->format('M d, Y') }}</td>
                        <td>{{ $maintenance->started_at?->format('M d, Y H:i') ?? 'N/A' }}</td>
                        <td>{{ $maintenance->completed_at?->format('M d, Y H:i') ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h2>Branch Comparison</h2>
        <table>
            <thead>
                <tr>
                    <th>Branch</th>
                    <th>Trip Requests</th>
                    <th>Driver Usage</th>
                    <th>Incident Reports</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['rankings']['branch_table'] as $row)
                    <tr>
                        <td>{{ $row['branch'] }}</td>
                        <td>{{ $row['trips'] }}</td>
                        <td>{{ $row['driver_usage'] }}</td>
                        <td>{{ $row['incidents'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>
