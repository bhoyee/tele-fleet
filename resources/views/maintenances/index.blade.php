<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Maintenance</h1>
            <p class="text-muted mb-0">View service history and upcoming schedules.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('maintenances.export.csv', ['status' => $statusFilter]) }}" class="btn btn-outline-secondary" data-loading>Export CSV</a>
            <a href="{{ route('maintenances.export.pdf', ['status' => $statusFilter]) }}" class="btn btn-outline-secondary" data-loading>Export PDF</a>
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
            <a class="btn btn-sm {{ $statusFilter === 'due' ? 'btn-primary' : 'btn-outline-primary' }}"
               href="{{ route('maintenances.index', ['status' => 'due']) }}">
                Due (Mileage)
            </a>
            <a class="btn btn-sm {{ $statusFilter === 'overdue' ? 'btn-primary' : 'btn-outline-primary' }}"
               href="{{ route('maintenances.index', ['status' => 'overdue']) }}">
                Overdue (Mileage)
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle datatable">
                    <thead class="table-light">
                        <tr>
                            <th>Vehicle</th>
                            <th>Scheduled</th>
                            <th>Status</th>
                            <th>Description</th>
                            <th>Cost</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
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
                                <td>{{ $maintenance->description }}</td>
                                <td>{{ $maintenance->cost !== null ? number_format($maintenance->cost, 2) : '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('maintenances.show', $maintenance) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    <a href="{{ route('maintenances.edit', $maintenance) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteMaintenanceModal"
                                            data-action="{{ route('maintenances.destroy', $maintenance) }}"
                                            data-label="{{ $maintenance->vehicle?->registration_number ?? 'Maintenance' }}">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @endforeach
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

                const dataUrl = "{{ route('maintenances.data', ['status' => $statusFilter]) }}";
                const showUrlTemplate = "{{ route('maintenances.show', ['maintenance' => '__ID__']) }}";
                const editUrlTemplate = "{{ route('maintenances.edit', ['maintenance' => '__ID__']) }}";
                const deleteUrlTemplate = "{{ route('maintenances.destroy', ['maintenance' => '__ID__']) }}";

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

                const renderRows = (rows) => {
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
                                    <a href="${showUrlTemplate.replace('__ID__', maintenance.id)}" class="btn btn-sm btn-outline-primary">View</a>
                                    <a href="${editUrlTemplate.replace('__ID__', maintenance.id)}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteMaintenanceModal"
                                            data-action="${deleteUrlTemplate.replace('__ID__', maintenance.id)}"
                                            data-label="${escapeHtml(maintenance.vehicle_registration)}">
                                        Delete
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
                };

                const refreshTable = async () => {
                    try {
                        const response = await fetch(dataUrl, { headers: { 'Accept': 'application/json' } });
                        if (!response.ok) return;
                        const payload = await response.json();
                        renderRows(payload.data || []);
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
                    const echo = window.ChatEcho ?? window.Echo;
                    if (!echo || typeof echo.private !== 'function') {
                        return null;
                    }
                    return echo;
                };

                const subscribeMaintenancesChannel = () => {
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
