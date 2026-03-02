<x-admin-layout>
    <style>
        .tele-report-filter-card {
            cursor: pointer;
        }

        .tele-report-filter-card:focus-visible {
            outline: 3px solid rgba(5, 108, 163, 0.35);
            outline-offset: 2px;
        }

        .tele-report-filter-card.tele-report-filter-active {
            box-shadow: var(--shadow-lg);
            border-color: rgba(5, 108, 163, 0.35);
        }
    </style>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h1 class="h3 mb-1">Custom Reports</h1>
            <p class="text-muted mb-0">Generate focused reports by dataset, branch, and date range.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" id="customExportCsv" href="{{ route('reports.custom.csv', request()->query()) }}" data-download>Export CSV</a>
            <a class="btn btn-outline-dark" id="customExportPdf" href="{{ route('reports.custom.pdf', request()->query()) }}" data-download>Export PDF</a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="type">Report Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="trips" @selected(request('type', $report_type) === 'trips')>Trips</option>
                        <option value="vehicles" @selected(request('type', $report_type) === 'vehicles')>Vehicles</option>
                        <option value="drivers" @selected(request('type', $report_type) === 'drivers')>Drivers</option>
                        <option value="incidents" @selected(request('type', $report_type) === 'incidents')>Incidents</option>
                        <option value="maintenance" @selected(request('type', $report_type) === 'maintenance')>Maintenance</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="range">Quick Range</label>
                    <select class="form-select" id="range" name="range">
                        <option value="">Custom</option>
                        <option value="today" @selected(request('range') === 'today')>Today</option>
                        <option value="week" @selected(request('range') === 'week')>This Week</option>
                        <option value="month" @selected(request('range') === 'month')>This Month</option>
                        <option value="year" @selected(request('range') === 'year')>This Year</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="from">From</label>
                    <input class="form-control" id="from" name="from" type="date" value="{{ request('from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="to">To</label>
                    <input class="form-control" id="to" name="to" type="date" value="{{ request('to') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="branch_id">Branch</label>
                    <select class="form-select" id="branch_id" name="branch_id">
                        <option value="">All Branches</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>
                                {{ \App\Support\TextNormalizer::titleText($branch->name) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary w-100" type="submit">Generate</button>
                    <a class="btn btn-outline-secondary w-100" href="{{ route('reports.custom') }}" data-loading>Reset</a>
                </div>
            </form>
        </div>
    </div>

    @if (! empty($summary))
        <div class="row row-cols-2 row-cols-md-5 g-3 mb-4">
            @foreach ($summary as $label => $value)
                <div class="col">
                    <div class="card stat-card h-100 tele-report-filter-card" role="button" tabindex="0" data-custom-summary-label="{{ $label }}" aria-label="Filter report: {{ $label }}">
                        <div class="card-body">
                            <div class="stat-label">{{ $label }}</div>
                            <div class="stat-value">{{ $value }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h5 mb-1">{{ $title }}</h2>
                    <div class="text-muted small">{{ $filters['branch_label'] }} · {{ $filters['range_label'] }}</div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle datatable" id="customReportTable" data-report-type="{{ $report_type }}">
                    <thead class="table-light">
                        <tr>
                            @foreach ($columns as $column)
                                <th>{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                @foreach ($row as $index => $cell)
                                    @if ($report_type === 'trips' && ($columns[$index] ?? '') === 'Status')
                                        @php
                                            $statusValue = strtolower((string) $cell);
                                            $statusClass = match ($statusValue) {
                                                'completed' => 'success',
                                                'assigned' => 'primary',
                                                'approved' => 'info',
                                                'rejected' => 'danger',
                                                'pending' => 'warning',
                                                'cancelled' => 'secondary',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <td>
                                            <span class="badge bg-{{ $statusClass }}">{{ $cell }}</span>
                                        </td>
                                    @else
                                        @php
                                            $columnName = (string) ($columns[$index] ?? '');
                                            $formattedCell = $cell;
                                            if (is_string($cell) && trim($cell) !== '') {
                                                if (in_array($columnName, ['Requester', 'Driver', 'User'], true)) {
                                                    $formattedCell = \App\Support\TextNormalizer::personName($cell);
                                                } elseif (in_array($columnName, ['Branch', 'Destination', 'Location', 'Title', 'Subject'], true)) {
                                                    $formattedCell = \App\Support\TextNormalizer::titleText($cell);
                                                } elseif ($columnName === 'Make' || $columnName === 'Model') {
                                                    $formattedCell = \App\Support\TextNormalizer::titlePreserveAcronyms($cell, 3);
                                                }
                                            }

                                            $statusToken = null;
                                            if ($report_type === 'drivers' && $columnName === 'Status' && is_string($formattedCell)) {
                                                $normalized = strtolower(trim($formattedCell));
                                                if ($normalized === 'active') {
                                                    $statusToken = 'active';
                                                } elseif ($normalized === 'assigned to officer') {
                                                    $statusToken = 'inactive';
                                                } elseif ($normalized === 'on leave') {
                                                    $statusToken = 'suspended';
                                                }
                                            }
                                        @endphp
                                        <td>
                                            @if ($statusToken)
                                                <span class="visually-hidden">{{ $statusToken }} </span>
                                            @endif
                                            {{ $formattedCell }}
                                        </td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const table = document.getElementById('customReportTable');
                if (!table) {
                    return;
                }

                const reportType = table.getAttribute('data-report-type') || 'trips';

                const statusColIndex = (() => {
                    const headers = table.querySelectorAll('thead th');
                    for (let i = 0; i < headers.length; i += 1) {
                        if ((headers[i]?.textContent || '').trim().toLowerCase() === 'status') {
                            return i;
                        }
                    }
                    return -1;
                })();

                const patternsByType = {
                    trips: {
                        'Total Trips': '',
                        'Pending': '^Pending$',
                        'Approved': '^(Approved|Assigned|Completed)$',
                        'Rejected': '^Rejected$',
                        'Completed': '^Completed$',
                    },
                    vehicles: {
                        'Total Vehicles': '',
                        'Available': '^Available$',
                        'In Use': '^In Use$',
                        'Maintenance': '^Maintenance$',
                        'Offline': '^Offline$',
                    },
                    drivers: {
                        'Total Drivers': '',
                        'Active': '\\bactive\\b',
                        'Assigned to Officer': '\\binactive\\b',
                        'On Leave': '\\bsuspended\\b',
                    },
                    incidents: {
                        'Open': '^Open$',
                        'Under Review': '^Under Review$',
                        'Resolved': '^Resolved$',
                        'Cancelled': '^Cancelled$',
                    },
                    maintenance: {
                        'Scheduled': '^Scheduled$',
                        'In Progress': '^In Progress$',
                        'Completed': '^Completed$',
                        'Cancelled': '^Cancelled$',
                    },
                };

                const clearActiveCards = () => {
                    document.querySelectorAll('[data-custom-summary-label]').forEach((card) => {
                        card.classList.remove('tele-report-filter-active');
                    });
                };

                const applyCustomFilter = (label) => {
                    if (!window.jQuery || !$.fn.dataTable) {
                        return;
                    }
                    if (statusColIndex < 0) {
                        return;
                    }

                    const pattern = patternsByType?.[reportType]?.[label];
                    if (typeof pattern === 'undefined') {
                        return;
                    }

                    const dt = $(table).DataTable();
                    const current = dt.column(statusColIndex).search();
                    const nextPattern = current === pattern ? '' : pattern;

                    const statusValueByType = {
                        trips: {
                            'Pending': 'pending',
                            'Approved': 'approved',
                            'Rejected': 'rejected',
                            'Completed': 'completed',
                        },
                        vehicles: {
                            'Available': 'available',
                            'In Use': 'in_use',
                            'Maintenance': 'maintenance',
                            'Offline': 'offline',
                        },
                        drivers: {
                            'Active': 'active',
                            'Assigned to Officer': 'inactive',
                            'On Leave': 'suspended',
                        },
                        incidents: {
                            'Open': 'open',
                            'Under Review': 'under_review',
                            'Resolved': 'resolved',
                            'Cancelled': 'cancelled',
                        },
                        maintenance: {
                            'Scheduled': 'scheduled',
                            'In Progress': 'in_progress',
                            'Completed': 'completed',
                            'Cancelled': 'cancelled',
                        },
                    };

                    const params = new URLSearchParams(window.location.search);
                    const statusValue = statusValueByType?.[reportType]?.[label] ?? null;
                    if (nextPattern && statusValue) {
                        params.set('status', statusValue);
                    } else {
                        params.delete('status');
                    }

                    const nextUrl = window.location.pathname + (params.toString() ? `?${params.toString()}` : '');
                    window.history.replaceState({}, '', nextUrl);

                    const exportCsv = document.getElementById('customExportCsv');
                    const exportPdf = document.getElementById('customExportPdf');
                    [exportCsv, exportPdf].forEach((link) => {
                        if (!link) return;
                        link.dataset.baseHref ??= link.getAttribute('href')?.split('?')[0] ?? '';
                        if (!link.dataset.baseHref) return;
                        link.setAttribute('href', link.dataset.baseHref + (params.toString() ? `?${params.toString()}` : ''));
                    });

                    dt.search('');
                    dt.columns().search('');
                    if (nextPattern) {
                        dt.column(statusColIndex).search(nextPattern, true, false);
                    }
                    dt.draw();

                    clearActiveCards();
                    if (nextPattern) {
                        document.querySelectorAll('[data-custom-summary-label]').forEach((card) => {
                            if ((card.getAttribute('data-custom-summary-label') || '') === label) {
                                card.classList.add('tele-report-filter-active');
                            }
                        });
                    }

                    table.scrollIntoView({ behavior: 'smooth', block: 'start' });
                };

                document.querySelectorAll('[data-custom-summary-label]').forEach((card) => {
                    const label = card.getAttribute('data-custom-summary-label') || '';
                    const trigger = () => applyCustomFilter(label);

                    card.addEventListener('click', (event) => {
                        event.preventDefault();
                        trigger();
                    });

                    card.addEventListener('keydown', (event) => {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            trigger();
                        }
                    });
                });
            });
        </script>
    @endpush
</x-admin-layout>
