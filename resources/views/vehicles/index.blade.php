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
                                <td>{{ $vehicle->make }} {{ $vehicle->model }}</td>
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
                                        <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#archiveVehicleModal"
                                                data-action="{{ route('vehicles.destroy', $vehicle) }}"
                                                data-name="{{ $vehicle->registration_number }}">
                                            Delete
                                        </button>
                                    @elseif (auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                                        <a href="{{ route('vehicles.show', $vehicle->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        <form method="POST" action="{{ route('vehicles.restore', $vehicle->id) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-success" data-loading>Restore</button>
                                        </form>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#forceDeleteVehicleModal"
                                                data-action="{{ route('vehicles.force', $vehicle->id) }}"
                                                data-name="{{ $vehicle->registration_number }}">
                                            Delete Permanently
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
                                <tr>
                                    <td colspan="6" class="text-muted">No assigned trips found.</td>
                                </tr>
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

                const renderRows = (rows) => {
                    if (window.jQuery && window.jQuery.fn.dataTable && window.jQuery.fn.dataTable.isDataTable(table)) {
                        window.jQuery(table).DataTable().destroy();
                    }

                    tbody.innerHTML = rows.map((vehicle) => {
                        const maintenanceState = vehicle.maintenance_state;
                        const maintenanceBadge = (maintenanceState === 'due' || maintenanceState === 'overdue')
                            ? `<span class="badge bg-${maintenanceState === 'overdue' ? 'danger' : 'warning'} ms-1">Maintenance ${escapeHtml(maintenanceState)}</span>`
                            : '';
                        const archivedActions = `
                            <a href="${showUrlTemplate.replace('__ID__', vehicle.id)}" class="btn btn-sm btn-outline-primary">View</a>
                            <form method="POST" action="${restoreUrlTemplate.replace('__ID__', vehicle.id)}" class="d-inline">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <input type="hidden" name="_method" value="PATCH">
                                <button type="submit" class="btn btn-sm btn-outline-success" data-loading>Restore</button>
                            </form>
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#forceDeleteVehicleModal"
                                    data-action="${forceDeleteUrlTemplate.replace('__ID__', vehicle.id)}"
                                    data-name="${escapeHtml(vehicle.registration_number)}">
                                Delete Permanently
                            </button>
                        `;
                        const activeActions = `
                            <a href="${showUrlTemplate.replace('__ID__', vehicle.id)}" class="btn btn-sm btn-outline-primary">View</a>
                            <a href="${editUrlTemplate.replace('__ID__', vehicle.id)}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#archiveVehicleModal"
                                    data-action="${deleteUrlTemplate.replace('__ID__', vehicle.id)}"
                                    data-name="${escapeHtml(vehicle.registration_number)}">
                                Delete
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
            });
        </script>
    @endpush
</x-admin-layout>
