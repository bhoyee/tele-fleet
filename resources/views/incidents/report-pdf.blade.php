<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Incident Reports</title>
        <style>
            :root {
                --primary: #056ca3;
                --dark: #1f2937;
                --muted: #6b7280;
                --border: #e5e7eb;
            }

            body {
                font-family: DejaVu Sans, Arial, sans-serif;
                color: var(--dark);
                font-size: 12px;
                margin: 0;
                padding: 24px;
                background: #ffffff;
            }

            .header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }

            .title {
                font-size: 20px;
                font-weight: 700;
                margin-bottom: 4px;
            }

            .subtitle {
                color: var(--muted);
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th, td {
                padding: 8px 10px;
                border-bottom: 1px solid var(--border);
                text-align: left;
            }

            th {
                background: #f9fafb;
                font-weight: 600;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <div>
                <div class="title">Incident Reports</div>
                <div class="subtitle">Generated {{ $generatedAt->format('M d, Y H:i') }}</div>
            </div>
            <div class="subtitle">Tele-Fleet</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Branch</th>
                    <th>Severity</th>
                    <th>Status</th>
                    <th>Incident Date</th>
                    <th>Location</th>
                    <th>Reported By</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($incidents as $incident)
                    <tr>
                        <td>{{ $incident->reference }}</td>
                        <td>{{ $incident->branch?->name ?? 'N/A' }}</td>
                        <td>{{ ucfirst($incident->severity) }}</td>
                        <td>{{ str_replace('_', ' ', ucfirst($incident->status)) }}</td>
                        <td>{{ $incident->incident_date?->format('M d, Y') }}</td>
                        <td>{{ $incident->location ?? 'N/A' }}</td>
                        <td>{{ $incident->reportedBy?->name ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>
