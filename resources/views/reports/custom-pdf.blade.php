<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>{{ $report['title'] }}</title>
        <style>
            body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2937; font-size: 12px; }
            h1 { font-size: 20px; margin-bottom: 4px; }
            h2 { font-size: 14px; margin: 20px 0 6px; }
            .meta { font-size: 11px; color: #6b7280; margin-bottom: 12px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
            th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
            th { background: #f3f4f6; font-weight: 600; }
            .summary-grid td { border: none; padding: 4px 8px; }
        </style>
    </head>
    <body>
        <h1>{{ $report['title'] }}</h1>
        <div class="meta">
            Branch: {{ $report['filters']['branch_label'] }} |
            Range: {{ $report['filters']['range_label'] }} |
            Generated: {{ $generatedAt->format('M d, Y H:i') }}
        </div>

        @if (! empty($report['summary']))
            <table class="summary-grid">
                @foreach ($report['summary'] as $label => $value)
                    <tr>
                        <td><strong>{{ $label }}:</strong> {{ $value }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        <h2>Details</h2>
        <table>
            <thead>
                <tr>
                    @foreach ($report['columns'] as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($report['rows'] as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>
