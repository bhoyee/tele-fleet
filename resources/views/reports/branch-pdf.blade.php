<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>{{ $reportTitle }}</title>
        <style>
            :root {
                --primary: #056ca3;
                --dark: #1f2937;
                --muted: #6b7280;
                --border: #e5e7eb;
            }

            @page {
                margin: 26px 24px 48px 24px;
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
                margin-bottom: 24px;
            }

            .title {
                font-size: 20px;
                font-weight: 700;
                margin-bottom: 4px;
            }

            .subtitle {
                color: var(--muted);
            }

            .summary-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 10px;
                margin-bottom: 20px;
            }

            .summary-cell {
                border: 1px solid var(--border);
                border-radius: 10px;
                padding: 10px;
                width: 25%;
            }

            .summary-label {
                color: var(--muted);
                font-size: 11px;
                margin-bottom: 4px;
            }

            .summary-value {
                font-size: 16px;
                font-weight: 700;
                color: var(--primary);
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

            .status {
                font-weight: 600;
                text-transform: capitalize;
            }

        </style>
    </head>
    <body>
        @include('reports._pdf_header')

        <div class="title">{{ $reportTitle }}</div>
        <div class="subtitle" style="margin-bottom: 20px;">Generated {{ $generatedAt->format('M d, Y H:i') }}</div>

        <table class="summary-table">
            <tr>
                <td class="summary-cell">
                    <div class="summary-label">Total Requests</div>
                    <div class="summary-value">{{ $stats['total'] }}</div>
                </td>
                <td class="summary-cell">
                    <div class="summary-label">Approved</div>
                    <div class="summary-value">{{ $stats['approved'] }}</div>
                </td>
                <td class="summary-cell">
                    <div class="summary-label">Pending</div>
                    <div class="summary-value">{{ $stats['pending'] }}</div>
                </td>
                <td class="summary-cell">
                    <div class="summary-label">Rejected</div>
                    <div class="summary-value">{{ $stats['rejected'] }}</div>
                </td>
            </tr>
        </table>

        <table>
            <thead>
                <tr>
                    <th>Request #</th>
                    <th>Branch</th>
                    <th>Requester</th>
                    <th>Destination</th>
                    <th>Trip Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($trips as $trip)
                    @php
                        $displayStatus = $trip->status;
                        if (in_array($trip->status, ['approved', 'assigned', 'completed'], true)) {
                            $displayStatus = 'approved';
                        } elseif ($trip->status === 'rejected') {
                            $displayStatus = 'rejected';
                        } else {
                            $displayStatus = 'pending';
                        }
                    @endphp
                    <tr>
                        <td>{{ $trip->request_number }}</td>
                        <td>{{ $trip->branch?->name ?? 'N/A' }}</td>
                        <td>{{ $trip->requestedBy?->name ?? 'N/A' }}</td>
                        <td>{{ $trip->destination }}</td>
                        <td>{{ $trip->trip_date?->format('M d, Y') }}</td>
                        <td class="status">{{ $displayStatus }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @include('reports._pdf_pagination')
    </body>
</html>
