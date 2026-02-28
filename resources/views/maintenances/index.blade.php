<x-admin-layout>
    @php
        $vehicleMode = $vehicleMode ?? false;
        $vehicles = $vehicles ?? collect();
    @endphp
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Maintenance</h1>
            <p class="text-muted mb-0">View service history and upcoming schedules.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('maintenances.export.csv', ['status' => $statusFilter]) }}" class="btn btn-outline-secondary" data-download>Export CSV</a>
            <a href="{{ route('maintenances.export.pdf', ['status' => $statusFilter]) }}" class="btn btn-outline-secondary" data-download>Export PDF</a>
            <a href="{{ route('maintenances.create') }}" class="btn btn-primary">Schedule Maintenance</a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body d-flex flex-wrap gap-2 align-items-center">
            <span class="text-muted small">Filter:</span>
            <a class="btn btn-sm {{ empty($statusFilter) ? 'btn-primary' : 'btn-outline-primary' }}"
               href="{{ route('maintenances.index') }}">
                All
            </a>
            <a class="btn btn-sm {{ $statusFilter === 'maintenance' ? 'btn-primary' : 'btn-outline-primary' }}"
               href="{{ route('maintenances.index', ['status' => 'maintenance']) }}">
                In Maintenance (Vehicles)
            </a>
            <a class="btn btn-sm {{ $statusFilter === 'due' ? 'btn-primary' : 'btn-outline-primary' }}"
               href="{{ route('maintenances.index', ['status' => 'due']) }}">
                Due (Mileage)
            </a>
            <a class="btn btn-sm {{ $statusFilter === 'overdue' ? 'btn-primary' : 'btn-outline-primary' }}"
               href="{{ route('maintenances.index', ['status' => 'overdue']) }}">
                Overdue (Mileage)
            </a>
            <div class="w-100 text-muted small mt-2">
                Due = at 98% of the mileage target since last maintenance. Overdue = target reached/exceeded. Configure the target in Maintenance Settings.
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>Maintenance Analytics (<span data-maintenance-month>{{ $maintenanceAnalytics['month_label'] ?? now()->format('F Y') }}</span>)</span>
        </div>
        <div class="card-body">
            <div class="row g-3" id="maintenanceStatsCards">
                <div class="col-6 col-lg-2">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-label">Scheduled</div>
                            <div class="stat-value" data-maintenance-stat="scheduled">{{ $maintenanceAnalytics['scheduled'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-label">In Progress</div>
                            <div class="stat-value" data-maintenance-stat="in_progress">{{ $maintenanceAnalytics['in_progress'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-label">Completed</div>
                            <div class="stat-value" data-maintenance-stat="completed">{{ $maintenanceAnalytics['completed'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-label">Cancelled</div>
                            <div class="stat-value" data-maintenance-stat="cancelled">{{ $maintenanceAnalytics['cancelled'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-label">Due (Mileage)</div>
                            <div class="stat-value" data-maintenance-stat="due">{{ $maintenanceAnalytics['due'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-label">Overdue (Mileage)</div>
                            <div class="stat-value" data-maintenance-stat="overdue">{{ $maintenanceAnalytics['overdue'] ?? 0 }}</div>
                        </div>
                    </div>
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
                            @if (!empty($vehicleMode))
                                <th>Vehicle</th>
                                <th>Branch</th>
                                <th>Status</th>
                                <th>Mileage</th>
                                <th>Maintenance</th>
                                <th class="text-end">Action</th>
                            @else
                                <th>Vehicle</th>
                                <th>Scheduled</th>
                                <th>Status</th>
                                <th>Description</th>
                                <th>Cost</th>
                                <th class="text-end">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @if (!empty($vehicleMode))
                            @foreach ($vehicles as $vehicle)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $vehicle->registration_number ?? 'N/A' }}</div>
                                        <small class="text-muted">{{ $vehicle->make }} {{ $vehicle->model }}</small>
                                    </td>
                                    <td>{{ $vehicle->branch?->name ?? 'N/A' }}</td>
                                    <td>
                                        @php
                                            $vehicleStatus = strtolower((string) ($vehicle->status ?? ''));
                                            $vehicleStatusClass = match ($vehicleStatus) {
                                                'available' => 'success',
                                                'maintenance' => 'warning text-dark',
                                                'in_use' => 'primary',
                                                'offline' => 'secondary',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $vehicleStatusClass }}">
                                            {{ ucfirst(str_replace('_', ' ', $vehicleStatus ?: 'unknown')) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small text-muted">Current: {{ number_format((int) ($vehicle->current_mileage ?? 0)) }} km</div>
                                        <div class="small text-muted">Last: {{ number_format((int) ($vehicle->last_maintenance_mileage ?? 0)) }} km</div>
                                    </td>
                                    <td>
                                        @php
                                            $state = strtolower((string) ($vehicle->maintenance_state ?? 'ok'));
                                            $stateClass = match ($state) {
                                                'overdue' => 'danger',
                                                'due' => 'warning text-dark',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $stateClass }}">{{ ucfirst($state) }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('maintenances.create', ['vehicle_id' => $vehicle->id]) }}"
                                           class="btn btn-sm btn-outline-primary"
                                           data-tele-tooltip
                                           title="Schedule maintenance">
                                            <i class="bi bi-calendar-plus"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            @foreach ($maintenances as $maintenance)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $maintenance->vehicle?->registration_number ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $maintenance->vehicle?->make }} {{ $maintenance->vehicle?->model }}</small>
                                </td>
                                <td>{{ $maintenance->scheduled_for?->format('M d, Y') }}</td>
                                <td>
                                    @php
                                        $status = $maintenance->status;
                                        $statusClass = $status === 'completed'
                                            ? 'success'
                                            : ($status === 'in_progress'
                                                ? 'primary'
                                                : ($status === 'cancelled'
                                                    ? 'secondary'
                                                    : 'warning'));
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                                    </span>
                                </td>
                                <td>{{ \App\Support\TextNormalizer::titleText($maintenance->description) }}</td>
                                <td>{{ $maintenance->cost !== null ? number_format($maintenance->cost, 2) : '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('maintenances.show', $maintenance) }}" class="btn btn-sm btn-outline-primary" data-tele-tooltip title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if ($maintenance->status !== \App\Models\VehicleMaintenance::STATUS_COMPLETED)
                                        <a href="{{ route('maintenances.edit', $maintenance) }}" class="btn btn-sm btn-outline-secondary" data-tele-tooltip title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endif
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteMaintenanceModal"
                                            data-action="{{ route('maintenances.destroy', $maintenance) }}"
                                            data-label="{{ $maintenance->vehicle?->registration_number ?? 'Maintenance' }}"
                                            data-tele-tooltip
                                            title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteMaintenanceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Maintenance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Delete maintenance record for <strong id="deleteMaintenanceLabel"></strong>? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="deleteMaintenanceForm">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete Maintenance</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const deleteMaintenanceModal = document.getElementById('deleteMaintenanceModal');
            if (deleteMaintenanceModal) {
                deleteMaintenanceModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const action = button.getAttribute('data-action');
                    const label = button.getAttribute('data-label');
                    document.getElementById('deleteMaintenanceForm').setAttribute('action', action);
                    document.getElementById('deleteMaintenanceLabel').textContent = label;
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

                const realtimeEnabled = {{ config('app.realtime_enabled') ? 'true' : 'false' }};
                const dataUrl = "{{ route('maintenances.data', ['status' => $statusFilter]) }}";
                const showUrlTemplate = "{{ route('maintenances.show', ['maintenance' => '__ID__']) }}";
                const editUrlTemplate = "{{ route('maintenances.edit', ['maintenance' => '__ID__']) }}";
                const deleteUrlTemplate = "{{ route('maintenances.destroy', ['maintenance' => '__ID__']) }}";
                const scheduleUrlTemplate = "{{ route('maintenances.create', ['vehicle_id' => '__ID__']) }}";

                const escapeHtml = (value) => String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');

                const statusBadge = (status) => {
                    switch ((status || '').toLowerCase()) {
                        case 'completed':
                            return 'success';
                        case 'in_progress':
                            return 'primary';
                        case 'cancelled':
                            return 'secondary';
                        default:
                            return 'warning';
                    }
                };

                const renderRecordRows = (rows) => {
                    if (window.jQuery && window.jQuery.fn.dataTable && window.jQuery.fn.dataTable.isDataTable(table)) {
                        window.jQuery(table).DataTable().destroy();
                    }

                    tbody.innerHTML = rows.map((maintenance) => {
                        const vehicleLine = `
                            <div class="fw-semibold">${escapeHtml(maintenance.vehicle_registration)}</div>
                            <small class="text-muted">${escapeHtml(maintenance.vehicle_make)} ${escapeHtml(maintenance.vehicle_model)}</small>
                        `;

                        return `
                            <tr>
                                <td>${vehicleLine}</td>
                                <td>${escapeHtml(maintenance.scheduled_for)}</td>
                                <td>
                                    <span class="badge bg-${statusBadge(maintenance.status)}">${escapeHtml(maintenance.status_label)}</span>
                                </td>
                                <td>${escapeHtml(maintenance.description)}</td>
                                <td>${escapeHtml(maintenance.cost)}</td>
                                <td class="text-end">
                                    <a href="${showUrlTemplate.replace('__ID__', maintenance.public_id)}" class="btn btn-sm btn-outline-primary" data-tele-tooltip title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    ${maintenance.status !== 'completed'
                                        ? `<a href="${editUrlTemplate.replace('__ID__', maintenance.public_id)}" class="btn btn-sm btn-outline-secondary" data-tele-tooltip title="Edit"><i class="bi bi-pencil"></i></a>`
                                        : ''}
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteMaintenanceModal"
                                            data-action="${deleteUrlTemplate.replace('__ID__', maintenance.public_id)}"
                                            data-label="${escapeHtml(maintenance.vehicle_registration)}"
                                            data-tele-tooltip
                                            title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
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
                };

                const vehicleStatusBadge = (status) => {
                    switch ((status || '').toLowerCase()) {
                        case 'available':
                            return 'success';
                        case 'in_use':
                            return 'primary';
                        case 'maintenance':
                            return 'warning text-dark';
                        case 'offline':
                            return 'secondary';
                        default:
                            return 'secondary';
                    }
                };

                const maintenanceStateBadge = (state) => {
                    switch ((state || '').toLowerCase()) {
                        case 'overdue':
                            return 'danger';
                        case 'due':
                            return 'warning text-dark';
                        default:
                            return 'secondary';
                    }
                };

                const renderVehicleRows = (rows) => {
                    if (window.jQuery && window.jQuery.fn.dataTable && window.jQuery.fn.dataTable.isDataTable(table)) {
                        window.jQuery(table).DataTable().destroy();
                    }

                    tbody.innerHTML = rows.map((vehicle) => {
                        const vehicleLine = `
                            <div class="fw-semibold">${escapeHtml(vehicle.registration_number)}</div>
                            <small class="text-muted">${escapeHtml(vehicle.make)} ${escapeHtml(vehicle.model)}</small>
                        `;

                        const mileageLine = `
                            <div class="small text-muted">Current: ${escapeHtml(vehicle.current_mileage ?? 0)} km</div>
                            <div class="small text-muted">Last: ${escapeHtml(vehicle.last_maintenance_mileage ?? 0)} km</div>
                        `;

                        const statusLabel = String(vehicle.status ?? '').replaceAll('_', ' ') || 'unknown';
                        const stateLabel = String(vehicle.maintenance_state ?? 'ok').replaceAll('_', ' ') || 'ok';

                        return `
                            <tr>
                                <td>${vehicleLine}</td>
                                <td>${escapeHtml(vehicle.branch)}</td>
                                <td><span class="badge bg-${vehicleStatusBadge(vehicle.status)}">${escapeHtml(statusLabel)}</span></td>
                                <td>${mileageLine}</td>
                                <td><span class="badge bg-${maintenanceStateBadge(vehicle.maintenance_state)}">${escapeHtml(stateLabel)}</span></td>
                                <td class="text-end">
                                    <a href="${scheduleUrlTemplate.replace('__ID__', vehicle.id)}" class="btn btn-sm btn-outline-primary" data-tele-tooltip title="Schedule maintenance">
                                        <i class="bi bi-calendar-plus"></i>
                                    </a>
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
                };

                const updateMaintenanceStats = (stats) => {
                    if (!stats) {
                        return;
                    }
                    const monthEl = document.querySelector('[data-maintenance-month]');
                    if (monthEl && stats.month_label) {
                        monthEl.textContent = String(stats.month_label);
                    }
                    ['scheduled', 'in_progress', 'completed', 'cancelled', 'due', 'overdue'].forEach((key) => {
                        const el = document.querySelector(`[data-maintenance-stat="${key}"]`);
                        if (el) {
                            el.textContent = String(stats[key] ?? 0);
                        }
                    });
                };

                const refreshTable = async () => {
                    try {
                        const response = await fetch(dataUrl, { headers: { 'Accept': 'application/json' } });
                        if (!response.ok) return;
                        const payload = await response.json();
                        if ((payload.mode || 'records') === 'vehicles') {
                            renderVehicleRows(payload.data || []);
                        } else {
                            renderRecordRows(payload.data || []);
                        }
                        updateMaintenanceStats(payload.stats);
                    } catch (error) {
                        console.warn('Maintenance table refresh failed.');
                    }
                };

                let poller = null;
                const startPollingFallback = () => {
                    if (poller) {
                        return;
                    }
                    poller = setInterval(refreshTable, 30000);
                };

                const initMaintenancesEcho = () => {
                    if (!realtimeEnabled) {
                        return null;
                    }
                    const echo = window.ChatEcho ?? window.Echo;
                    if (!echo || typeof echo.private !== 'function') {
                        return null;
                    }
                    return echo;
                };

                const subscribeMaintenancesChannel = () => {
                    if (!realtimeEnabled) {
                        startPollingFallback();
                        return;
                    }
                    const echo = initMaintenancesEcho();
                    if (!echo) {
                        startPollingFallback();
                        return;
                    }
                    echo.private('maintenances.all')
                        .listen('.maintenance.changed', () => {
                            refreshTable();
                        })
                        .error(() => {
                            startPollingFallback();
                        });
                };

                subscribeMaintenancesChannel();
                startPollingFallback();
            });
        </script>
    @endpush
</x-admin-layout>
