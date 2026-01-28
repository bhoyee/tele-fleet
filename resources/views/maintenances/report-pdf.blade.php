<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maintenance Records</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111827; }
        .header { margin-bottom: 24px; }
        .title { font-size: 20px; font-weight: 700; margin-bottom: 6px; }
        .meta { font-size: 12px; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background: #f3f4f6; font-weight: 600; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Maintenance Records</div>
        <div class="meta">
            Generated {{ $generatedAt?->format('M d, Y g:i A') }}
            @if ($statusFilter)
                · Filter: {{ ucfirst($statusFilter) }} mileage
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Vehicle</th>
                <th>Status</th>
                <th>Scheduled</th>
                <th>Description</th>
                <th>Cost</th>
                <th>Odometer</th>
                <th>Mileage State</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($maintenances as $maintenance)
                <tr>
                    <td>{{ $maintenance->vehicle?->registration_number ?? 'N/A' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $maintenance->status)) }}</td>
                    <td>{{ $maintenance->scheduled_for?->format('Y-m-d') }}</td>
                    <td>{{ $maintenance->description }}</td>
                    <td>{{ $maintenance->cost !== null ? number_format($maintenance->cost, 2) : '—' }}</td>
                    <td>{{ $maintenance->odometer !== null ? number_format($maintenance->odometer) : '—' }}</td>
                    <td>{{ ucfirst($maintenance->vehicle?->maintenance_state ?? 'ok') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
