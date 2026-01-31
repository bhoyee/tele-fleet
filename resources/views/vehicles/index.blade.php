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
