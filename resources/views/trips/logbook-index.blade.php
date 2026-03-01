<x-admin-layout>
    <style>
        .logbook-action-icons {
            display: inline-flex;
            gap: 0.5rem;
            flex-wrap: nowrap;
            white-space: nowrap;
            justify-content: flex-end;
            align-items: center;
        }

        .logbook-action-icons .btn {
            padding: 0.35rem 0.5rem;
        }

        .tele-logbook-filter {
            cursor: pointer;
        }
    </style>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h1 class="h3 mb-1">Logbooks</h1>
            <p class="text-muted mb-0">Track trips pending logbook entry and completed logs.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('logbooks.manage') }}">Manage Logbooks</a>
    </div>

    @php
        $isSuperAdmin = auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN;
        $canEditLogbook = in_array(auth()->user()?->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true);
    @endphp

    @if (! empty($stats))
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>Logbook Summary ({{ $stats['range_label'] ?? now()->format('M Y') }})</span>
                <span class="text-muted small">Click a card to filter the table.</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="card stat-card h-100 tele-logbook-filter" role="button" tabindex="0" data-logbook-filter="all" data-tele-tooltip title="Show trips from this month">
                            <div class="card-body">
                                <div class="stat-label">Trips This Month</div>
                                <div class="stat-value">{{ $stats['total'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card stat-card h-100 tele-logbook-filter" role="button" tabindex="0" data-logbook-filter="logbook" data-logbook-state="completed" data-tele-tooltip title="Filter completed logbooks (this month)">
                            <div class="card-body">
                                <div class="stat-label">Logbooks Completed</div>
                                <div class="stat-value">{{ $stats['completed'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card stat-card h-100 tele-logbook-filter" role="button" tabindex="0" data-logbook-filter="logbook" data-logbook-state="pending" data-tele-tooltip title="Filter trips awaiting logbook (this month)">
                            <div class="card-body">
                                <div class="stat-label">Awaiting Logbook</div>
                                <div class="stat-value">{{ $stats['pending'] ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle datatable">
                    <thead class="table-light">
                        <tr>
                            <th>Request #</th>
                            <th>Branch</th>
                            <th>Trip Date</th>
                            <th>Vehicle</th>
                            <th>Driver</th>
                            <th>Status</th>
                            <th>Due Status</th>
                            <th>Logbook</th>
                            @if ($isSuperAdmin)
                                <th>Entered By</th>
                                <th>Last Edited By</th>
                            @endif
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trips as $trip)
                            <tr>
                                <td>{{ $trip->request_number }}</td>
                                <td>{{ $trip->branch?->name ? \App\Support\TextNormalizer::titleText($trip->branch->name) : 'N/A' }}</td>
                                <td>
                                    <span class="visually-hidden">{{ $trip->trip_date?->format('Y-m-d') }}</span>
                                    {{ $trip->trip_date?->format('M d, Y') }}
                                </td>
                                <td>{{ $trip->assignedVehicle?->registration_number ?? 'N/A' }}</td>
                                <td>{{ $trip->assignedDriver?->full_name ? \App\Support\TextNormalizer::personName($trip->assignedDriver->full_name) : 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-{{ $trip->status === 'completed' ? 'dark' : 'primary' }}">
                                        {{ ucfirst($trip->status) }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $dueStatus = $trip->dueStatus();
                                        $dueLabel = $dueStatus ? ucfirst($dueStatus) : 'On Schedule';
                                        $dueClass = $dueStatus === 'overdue'
                                            ? 'danger'
                                            : ($dueStatus === 'due'
                                                ? 'warning text-dark'
                                                : 'secondary');
                                    @endphp
                                    <span class="badge bg-{{ $dueClass }}">{{ $dueLabel }}</span>
                                </td>
                                <td>
                                    @if ($trip->log)
                                        <span class="visually-hidden">logbook_completed</span>
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="visually-hidden">logbook_pending</span>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                                @if ($isSuperAdmin)
                                    <td>{{ $trip->log?->enteredBy?->name ? \App\Support\TextNormalizer::personName($trip->log->enteredBy->name) : 'N/A' }}</td>
                                    <td>{{ $trip->log?->editedBy?->name ? \App\Support\TextNormalizer::personName($trip->log->editedBy->name) : 'N/A' }}</td>
                                @endif
                                <td class="text-end">
                                    <div class="logbook-action-icons">
                                        @if ($trip->log)
                                            <a href="{{ route('logbooks.show', $trip->log) }}" class="btn btn-sm btn-outline-primary" data-loading data-tele-tooltip title="View logbook">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @if ($canEditLogbook)
                                                <a href="{{ route('trips.logbook.edit', $trip) }}" class="btn btn-sm btn-outline-secondary" data-loading data-tele-tooltip title="Edit logbook">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            @endif
                                        @else
                                            <a href="{{ route('trips.logbook', $trip) }}" class="btn btn-sm btn-dark" data-loading data-tele-tooltip title="Enter logbook">
                                                <i class="bi bi-journal-plus"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
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
                const table = document.querySelector('table.datatable');
                if (!table || !window.jQuery?.fn?.dataTable || !window.jQuery.fn.dataTable.isDataTable(table)) {
                    return;
                }

                const currentMonth = @json(now()->format('Y-m'));
                const tripDateCol = 2;
                const logbookCol = 7;

                let activeFilter = { type: 'none', month: null, logbookState: null };

                const applyFilters = () => {
                    const dt = window.jQuery(table).DataTable();
                    dt.search('');
                    dt.columns().search('');

                    if (activeFilter.month) {
                        dt.column(tripDateCol).search(currentMonth + '-\\d{2}', true, false, true);
                    }

                    if (activeFilter.type === 'logbook' && activeFilter.logbookState) {
                        const token = activeFilter.logbookState === 'completed' ? 'logbook_completed' : 'logbook_pending';
                        dt.column(logbookCol).search('\\b' + token + '\\b', true, false, true);
                    }

                    dt.draw();
                };

                const scrollToTable = () => {
                    table.scrollIntoView({ behavior: 'smooth', block: 'start' });
                };

                const handleClick = (node) => {
                    const type = node.getAttribute('data-logbook-filter');
                    if (!type) {
                        return;
                    }

                    if (type === 'all') {
                        activeFilter = { type: 'all', month: currentMonth, logbookState: null };
                        applyFilters();
                        scrollToTable();
                        return;
                    }

                    if (type === 'logbook') {
                        const state = node.getAttribute('data-logbook-state');
                        activeFilter = { type: 'logbook', month: currentMonth, logbookState: state };
                        applyFilters();
                        scrollToTable();
                    }
                };

                document.addEventListener('click', (event) => {
                    const target = event.target.closest('[data-logbook-filter]');
                    if (!target) {
                        return;
                    }
                    handleClick(target);
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter' && event.key !== ' ') {
                        return;
                    }
                    const target = event.target.closest('[data-logbook-filter]');
                    if (!target) {
                        return;
                    }
                    event.preventDefault();
                    handleClick(target);
                });
            });
        </script>
    @endpush
</x-admin-layout>
