<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Vehicles</h1>
            <p class="text-muted mb-0">Track fleet assets and current status.</p>
        </div>
        <div class="d-flex gap-2">
            @if (auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                @if (!($showArchived ?? false))
                    <a href="{{ route('vehicles.index', ['archived' => 1]) }}" class="btn btn-outline-secondary">Show Archived</a>
                @else
                    <a href="{{ route('vehicles.index') }}" class="btn btn-outline-secondary">Back to Active</a>
                @endif
            @endif
            <a href="{{ route('vehicles.create') }}" class="btn btn-primary">New Vehicle</a>
        </div>
    </div>

    <div class="row g-3 mb-4" id="vehicleStatsCards">
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100 tele-vehicle-filter" role="button" tabindex="0" data-vehicle-filter="all" data-tele-tooltip title="Show all vehicles">
                <div class="card-body">
                    <div class="stat-label">Total Vehicles</div>
                    <div class="stat-value" data-vehicle-stat="total">{{ $vehicleStats['total'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100 tele-vehicle-filter" role="button" tabindex="0" data-vehicle-filter="status" data-vehicle-status="available" data-tele-tooltip title="Filter available vehicles">
                <div class="card-body">
                    <div class="stat-label">Available</div>
                    <div class="stat-value" data-vehicle-stat="available">{{ $vehicleStats['available'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card stat-card h-100 tele-vehicle-filter" role="button" tabindex="0" data-vehicle-filter="status" data-vehicle-status="in_use" data-tele-tooltip title="Filter vehicles in use">
                <div class="card-body">
                    <div class="stat-label">In Use</div>
                    <div class="stat-value" data-vehicle-stat="in_use">{{ $vehicleStats['in_use'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card stat-card h-100 tele-vehicle-filter" role="button" tabindex="0" data-vehicle-filter="status" data-vehicle-status="offline" data-tele-tooltip title="Filter offline vehicles">
                <div class="card-body">
                    <div class="stat-label">Offline</div>
                    <div class="stat-value" data-vehicle-stat="offline">{{ $vehicleStats['offline'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card stat-card h-100 tele-vehicle-filter" role="button" tabindex="0" data-vehicle-filter="status" data-vehicle-status="maintenance" data-tele-tooltip title="Filter vehicles on maintenance">
                <div class="card-body">
                    <div class="stat-label">Maintenance</div>
                    <div class="stat-value" data-vehicle-stat="maintenance">{{ $vehicleStats['maintenance'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle datatable">
                    <thead class="table-light">
                        <tr>
                            <th>Registration</th>
                            <th>Make/Model</th>
                            <th>Mileage</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vehicles as $vehicle)
                            @php
                                $displayStatus = $vehicle->status;
                                if ($vehicle->status !== 'maintenance' && $vehicle->status !== 'offline') {
                                    $displayStatus = ($activeAssignedIds ?? collect())->contains($vehicle->id)
                                        ? 'in_use'
                                        : 'available';
                                }
                            @endphp
                            <tr>
                                <td>{{ $vehicle->registration_number }}</td>
                                <td>
                                    {{ \App\Support\TextNormalizer::titlePreserveAcronyms($vehicle->make, 3) }}
                                    {{ \App\Support\TextNormalizer::titlePreserveAcronyms($vehicle->model, 3) }}
                                </td>
                                <td>{{ number_format($vehicle->current_mileage) }} km</td>
                                <td>
                                    <span class="badge bg-{{ $displayStatus === 'available' ? 'success' : ($displayStatus === 'in_use' ? 'primary' : ($displayStatus === 'maintenance' ? 'warning' : 'secondary')) }}">
                                        {{ ucfirst(str_replace('_', ' ', $displayStatus)) }}
                                    </span>
                                    @if (in_array($vehicle->maintenance_state, ['due', 'overdue'], true))
                                        <span class="badge bg-{{ $vehicle->maintenance_state === 'overdue' ? 'danger' : 'warning' }} ms-1">
                                            Maintenance {{ ucfirst($vehicle->maintenance_state) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if (!($showArchived ?? false))
                                        <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-sm btn-outline-primary" data-tele-tooltip title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-sm btn-outline-secondary" data-tele-tooltip title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#archiveVehicleModal"
                                                data-action="{{ route('vehicles.destroy', $vehicle) }}"
                                                data-name="{{ $vehicle->registration_number }}"
                                                data-tele-tooltip
                                                title="Archive">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @elseif (auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                                        <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-sm btn-outline-primary" data-tele-tooltip title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <form method="POST" action="{{ route('vehicles.restore', $vehicle) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-success" data-loading data-tele-tooltip title="Restore">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        </form>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#forceDeleteVehicleModal"
                                                data-action="{{ route('vehicles.force', $vehicle) }}"
                                                data-name="{{ $vehicle->registration_number }}"
                                                data-tele-tooltip
                                                title="Delete permanently">
                                            <i class="bi bi-x-octagon"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if (in_array(auth()->user()?->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true))
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h5 class="fw-semibold mb-1">Vehicle Trip Log</h5>
                        <div class="text-muted small">Current and upcoming assigned trips (across all vehicles).</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <select class="form-select form-select-sm" id="vehicleTripLogBranchFilter" style="min-width: 180px;">
                            <option value="">All branches</option>
                            @foreach (($vehicleTripLogs ?? collect())->pluck('branch.name')->filter()->unique()->sort()->values() as $branchName)
                                <option value="{{ $branchName }}">{{ $branchName }}</option>
                            @endforeach
                        </select>
                        <select class="form-select form-select-sm" id="vehicleTripLogStatusFilter" style="min-width: 160px;">
                            <option value="">All statuses</option>
                            <option value="Approved">Approved</option>
                            <option value="Assigned">Assigned</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle" id="vehicleTripLogTable">
                        <thead class="table-light">
                            <tr>
                                <th>Vehicle</th>
                                <th>Request #</th>
                                <th>Trip Date</th>
                                <th>Branch</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($vehicleTripLogs ?? collect()) as $trip)
                                @php
                                    $tripTime = $trip->trip_time;
                                    if ($tripTime) {
                                        try {
                                            $tripTime = \Illuminate\Support\Carbon::parse($tripTime)->format('g:i A');
                                        } catch (\Exception $e) {
                                            $tripTime = \Illuminate\Support\Carbon::parse($trip->trip_time)->format('g:i A');
                                        }
                                    }
                                    $status = strtolower((string) $trip->status);
                                    $statusClass = match ($status) {
                                        'assigned' => 'primary',
                                        'approved' => 'info',
                                        default => 'secondary',
                                    };

                                    $tripStart = $trip->trip_date?->copy()?->startOfDay();
                                    if ($trip->trip_date && $trip->trip_time) {
                                        $dateString = $trip->trip_date->format('Y-m-d');
                                        $timeString = trim((string) $trip->trip_time);
                                        if (str_contains($timeString, '.')) {
                                            $timeString = explode('.', $timeString, 2)[0];
                                        }
                                        try {
                                            $tripStart = \Illuminate\Support\Carbon::createFromFormat('Y-m-d H:i', $dateString.' '.$timeString);
                                        } catch (\Exception $e) {
                                            $tripStart = \Illuminate\Support\Carbon::parse($dateString.' '.$timeString);
                                        }
                                    }

                                    $windowBadge = 'Current';
                                    $windowBadgeClass = 'primary';
                                    if ($tripStart && now()->lt($tripStart)) {
                                        $windowBadge = 'Future';
                                        $windowBadgeClass = 'info text-dark';
                                    }
                                    if (method_exists($trip, 'dueStatus') && $trip->dueStatus() === 'overdue') {
                                        $windowBadge = 'Overdue';
                                        $windowBadgeClass = 'danger';
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $trip->assignedVehicle?->registration_number ?? 'N/A' }}</div>
                                        <small class="text-muted">{{ trim(($trip->assignedVehicle?->make ?? '').' '.($trip->assignedVehicle?->model ?? '')) ?: '—' }}</small>
                                    </td>
                                    <td class="fw-semibold">{{ $trip->request_number }}</td>
                                    <td>
                                        <div>{{ $trip->trip_date?->format('M d, Y') ?? 'N/A' }}</div>
                                        <small class="text-muted">{{ $tripTime ?: 'N/A' }}</small>
                                    </td>
                                    <td>{{ $trip->branch?->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $statusClass }} {{ $status === 'approved' ? 'text-dark' : '' }}">
                                            {{ ucfirst($status) }}
                                        </span>
                                        <span class="badge bg-{{ $windowBadgeClass }} ms-1">{{ $windowBadge }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('trips.show', $trip) }}" data-loading>View</a>
                                    </td>
                                </tr>
                            @empty
                                {{-- Let DataTables render its built-in empty state (prevents colspan column-count warnings). --}}
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.dataTable) {
                        return;
                    }
                    const table = document.getElementById('vehicleTripLogTable');
                    if (!table) {
                        return;
                    }

                    const dt = window.jQuery(table).DataTable({
                        pageLength: 10,
                        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
                        order: [[2, 'asc']],
                        searching: true,
                        paging: true,
                        info: true,
                        responsive: true,
                        language: {
                            emptyTable: 'No assigned trips found.',
                        },
                    });

                    const statusFilter = document.getElementById('vehicleTripLogStatusFilter');
                    if (statusFilter) {
                        statusFilter.addEventListener('change', () => {
                            dt.column(4).search(statusFilter.value).draw();
                        });
                    }

                    const branchFilter = document.getElementById('vehicleTripLogBranchFilter');
                    if (branchFilter) {
                        branchFilter.addEventListener('change', () => {
                            dt.column(3).search(branchFilter.value).draw();
                        });
                    }
                });
            </script>
        @endpush
    @endif

    <div class="modal fade" id="archiveVehicleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Vehicle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Delete vehicle <strong id="archiveVehicleName"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="archiveVehicleForm">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete Vehicle</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="forceDeleteVehicleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Vehicle Permanently</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Permanently delete vehicle <strong id="forceDeleteVehicleName"></strong>? This cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="forceDeleteVehicleForm">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete Permanently</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const archiveVehicleModal = document.getElementById('archiveVehicleModal');
            if (archiveVehicleModal) {
                archiveVehicleModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const action = button.getAttribute('data-action');
                    const name = button.getAttribute('data-name');
                    document.getElementById('archiveVehicleForm').setAttribute('action', action);
                    document.getElementById('archiveVehicleName').textContent = name;
                });
            }
        </script>
        <script>
            const forceDeleteVehicleModal = document.getElementById('forceDeleteVehicleModal');
            if (forceDeleteVehicleModal) {
                forceDeleteVehicleModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const action = button.getAttribute('data-action');
                    const name = button.getAttribute('data-name');
                    document.getElementById('forceDeleteVehicleForm').setAttribute('action', action);
                    document.getElementById('forceDeleteVehicleName').textContent = name;
                });
            }
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const table = document.querySelector('.datatable');
                if (!table) {
                    return;
                }
                const tbody = table.querySelector('tbody');
                if (!tbody) {
                    return;
                }

                const showArchived = @json($showArchived ?? false);
                const realtimeEnabled = {{ config('app.realtime_enabled') ? 'true' : 'false' }};
                const dataUrl = "{{ route('vehicles.data') }}" + (showArchived ? "?archived=1" : "");
                const showUrlTemplate = "{{ route('vehicles.show', ['vehicle' => '__ID__']) }}";
                const editUrlTemplate = "{{ route('vehicles.edit', ['vehicle' => '__ID__']) }}";
                const deleteUrlTemplate = "{{ route('vehicles.destroy', ['vehicle' => '__ID__']) }}";
                const restoreUrlTemplate = "{{ route('vehicles.restore', ['vehicle' => '__ID__']) }}";
                const forceDeleteUrlTemplate = "{{ route('vehicles.force', ['vehicle' => '__ID__']) }}";

                const escapeHtml = (value) => String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');

                const statusBadge = (status) => {
                    switch ((status || '').toLowerCase()) {
                        case 'available':
                            return 'success';
                        case 'in_use':
                            return 'primary';
                        case 'maintenance':
                            return 'warning';
                        default:
                            return 'secondary';
                    }
                };

                let activeVehicleFilter = { type: 'all', status: null };

                const applyVehicleFilter = () => {
                    if (!window.jQuery?.fn?.dataTable) {
                        return;
                    }
                    if (!window.jQuery.fn.dataTable.isDataTable(table)) {
                        return;
                    }

                    const dt = window.jQuery(table).DataTable();
                    dt.search('');
                    dt.columns().search('');

                    if (activeVehicleFilter.type === 'status' && activeVehicleFilter.status) {
                        const statusToken = String(activeVehicleFilter.status || '').replaceAll('_', ' ').trim();
                        if (statusToken) {
                            // Status cells can also include the "Due/Overdue" badges; match the leading status.
                            dt.column(3).search('^\\s*' + statusToken + '\\b', true, false, true);
                        }
                    }

                    dt.draw();
                };

                const updateVehicleStats = (rows) => {
                    const stats = {
                        total: 0,
                        available: 0,
                        in_use: 0,
                        offline: 0,
                        maintenance: 0,
                    };

                    (rows || []).forEach((vehicle) => {
                        stats.total += 1;
                        const status = String(vehicle?.status ?? '').toLowerCase();
                        if (status in stats) {
                            stats[status] += 1;
                        }
                    });

                    Object.entries(stats).forEach(([key, value]) => {
                        const el = document.querySelector(`[data-vehicle-stat="${key}"]`);
                        if (el) {
                            el.textContent = String(value);
                        }
                    });
                };

                const renderRows = (rows) => {
                    if (window.jQuery && window.jQuery.fn.dataTable && window.jQuery.fn.dataTable.isDataTable(table)) {
                        window.jQuery(table).DataTable().destroy();
                    }

                    updateVehicleStats(rows);

                    tbody.innerHTML = rows.map((vehicle) => {
                        const maintenanceState = vehicle.maintenance_state;
                        const maintenanceBadge = (maintenanceState === 'due' || maintenanceState === 'overdue')
                            ? `<span class="badge bg-${maintenanceState === 'overdue' ? 'danger' : 'warning'} ms-1">Maintenance ${escapeHtml(maintenanceState)}</span>`
                            : '';
                        const archivedActions = `
                            <a href="${showUrlTemplate.replace('__ID__', vehicle.public_id)}" class="btn btn-sm btn-outline-primary" data-tele-tooltip title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <form method="POST" action="${restoreUrlTemplate.replace('__ID__', vehicle.public_id)}" class="d-inline">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <input type="hidden" name="_method" value="PATCH">
                                <button type="submit" class="btn btn-sm btn-outline-success" data-loading data-tele-tooltip title="Restore">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </form>
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#forceDeleteVehicleModal"
                                    data-action="${forceDeleteUrlTemplate.replace('__ID__', vehicle.public_id)}"
                                    data-name="${escapeHtml(vehicle.registration_number)}"
                                    data-tele-tooltip
                                    title="Delete permanently">
                                <i class="bi bi-x-octagon"></i>
                            </button>
                        `;
                        const activeActions = `
                            <a href="${showUrlTemplate.replace('__ID__', vehicle.public_id)}" class="btn btn-sm btn-outline-primary" data-tele-tooltip title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="${editUrlTemplate.replace('__ID__', vehicle.public_id)}" class="btn btn-sm btn-outline-secondary" data-tele-tooltip title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#archiveVehicleModal"
                                    data-action="${deleteUrlTemplate.replace('__ID__', vehicle.public_id)}"
                                    data-name="${escapeHtml(vehicle.registration_number)}"
                                    data-tele-tooltip
                                    title="Archive">
                                <i class="bi bi-trash"></i>
                            </button>
                        `;

                        return `
                            <tr>
                                <td>${escapeHtml(vehicle.registration_number)}</td>
                                <td>${escapeHtml(vehicle.make)} ${escapeHtml(vehicle.model)}</td>
                                <td>${escapeHtml(vehicle.current_mileage)} km</td>
                                <td>
                                    <span class="badge bg-${statusBadge(vehicle.status)}">${escapeHtml(vehicle.status.replace('_', ' '))}</span>
                                    ${maintenanceBadge}
                                </td>
                                <td class="text-end">
                                    ${showArchived ? archivedActions : activeActions}
                                </td>
                            </tr>
                        `;
                    }).join('');

                    if (window.jQuery && window.jQuery.fn.dataTable) {
                        window.jQuery(table).DataTable({
                            pageLength: 10,
                            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
                            order: [],
                            searching: true,
                            paging: true,
                            info: true,
                        });
                    }

                    if (window.bootstrap?.Tooltip) {
                        table.querySelectorAll('[data-tele-tooltip]').forEach((el) => {
                            bootstrap.Tooltip.getOrCreateInstance(el);
                        });
                    }

                    applyVehicleFilter();
                };

                const refreshTable = async () => {
                    try {
                        const response = await fetch(dataUrl, { headers: { 'Accept': 'application/json' } });
                        if (!response.ok) return;
                        const payload = await response.json();
                        renderRows(payload.data || []);
                    } catch (error) {
                        console.warn('Vehicle table refresh failed.');
                    }
                };

                let poller = null;
                const startPollingFallback = () => {
                    if (poller) {
                        return;
                    }
                    poller = setInterval(refreshTable, 30000);
                };

                const initVehiclesEcho = () => {
                    if (!realtimeEnabled) {
                        return null;
                    }
                    const echo = window.ChatEcho ?? window.Echo;
                    if (!echo || typeof echo.private !== 'function') {
                        return null;
                    }
                    return echo;
                };

                const subscribeVehiclesChannel = () => {
                    if (!realtimeEnabled) {
                        startPollingFallback();
                        return;
                    }
                    const echo = initVehiclesEcho();
                    if (!echo) {
                        startPollingFallback();
                        return;
                    }
                    echo.private('vehicles.all')
                        .listen('.vehicle.changed', () => {
                            refreshTable();
                        })
                        .error(() => {
                            startPollingFallback();
                        });
                };

                subscribeVehiclesChannel();
                startPollingFallback();

                const scrollToTable = () => {
                    table.scrollIntoView({ behavior: 'smooth', block: 'start' });
                };

                const handleFilterClick = (node) => {
                    const type = node.getAttribute('data-vehicle-filter');
                    if (!type) {
                        return;
                    }

                    if (type === 'all') {
                        activeVehicleFilter = { type: 'all', status: null };
                    } else if (type === 'status') {
                        activeVehicleFilter = { type: 'status', status: node.getAttribute('data-vehicle-status') };
                    } else {
                        return;
                    }

                    // Apply to the current table state (initial server-rendered DataTable or after refresh).
                    setTimeout(() => {
                        applyVehicleFilter();
                        scrollToTable();
                    }, 0);
                };

                document.addEventListener('click', (event) => {
                    const target = event.target.closest('[data-vehicle-filter]');
                    if (!target) {
                        return;
                    }
                    handleFilterClick(target);
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter' && event.key !== ' ') {
                        return;
                    }
                    const target = event.target.closest('[data-vehicle-filter]');
                    if (!target) {
                        return;
                    }
                    event.preventDefault();
                    handleFilterClick(target);
                });
            });
        </script>
    @endpush
</x-admin-layout>
