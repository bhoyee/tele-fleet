<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Drivers</h1>
            <p class="text-muted mb-0">Manage driver records and compliance.</p>
        </div>
        <div class="d-flex gap-2">
            @if (auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                @if (!($showArchived ?? false))
                    <a href="{{ route('drivers.index', ['archived' => 1]) }}" class="btn btn-outline-secondary">Show Archived</a>
                @else
                    <a href="{{ route('drivers.index') }}" class="btn btn-outline-secondary">Back to Active</a>
                @endif
            @endif
            <a href="{{ route('drivers.create') }}" class="btn btn-primary">New Driver</a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle datatable">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>License</th>
                            <th>License Expiry</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($drivers as $driver)
                            <tr>
                                <td>{{ $driver->full_name }}</td>
                                <td>{{ $driver->license_number }}</td>
                                <td>{{ $driver->license_expiry?->format('M d, Y') ?? 'N/A' }}</td>
                                <td>{{ $driver->phone }}</td>
                                <td>
                                    <span class="badge {{ $driver->status === 'active' ? 'bg-success' : ($driver->status === 'inactive' ? 'bg-secondary' : 'bg-warning') }}">
                                        {{ ucfirst($driver->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @if (!($showArchived ?? false))
                                        <a href="{{ route('drivers.show', $driver) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        <a href="{{ route('drivers.edit', $driver) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#archiveDriverModal"
                                                data-action="{{ route('drivers.destroy', $driver) }}"
                                                data-name="{{ $driver->full_name }}">
                                            Delete
                                        </button>
                                    @elseif (auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                                        <a href="{{ route('drivers.show', $driver) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        <form method="POST" action="{{ route('drivers.restore', $driver) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-success" data-loading>Restore</button>
                                        </form>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#forceDeleteDriverModal"
                                                data-action="{{ route('drivers.force', $driver) }}"
                                                data-name="{{ $driver->full_name }}">
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
                        <h5 class="fw-semibold mb-1">Driver Trip Log</h5>
                        <div class="text-muted small">Current and upcoming assigned trips (across all drivers).</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <select class="form-select form-select-sm" id="driverTripLogBranchFilter" style="min-width: 180px;">
                            <option value="">All branches</option>
                            @foreach (($driverTripLogs ?? collect())->pluck('branch.name')->filter()->unique()->sort()->values() as $branchName)
                                <option value="{{ $branchName }}">{{ $branchName }}</option>
                            @endforeach
                        </select>
                        <select class="form-select form-select-sm" id="driverTripLogStatusFilter" style="min-width: 160px;">
                            <option value="">All statuses</option>
                            <option value="Approved">Approved</option>
                            <option value="Assigned">Assigned</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle" id="driverTripLogTable">
                        <thead class="table-light">
                            <tr>
                                <th>Driver</th>
                                <th>Request #</th>
                                <th>Trip Date</th>
                                <th>Branch</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($driverTripLogs ?? collect()) as $trip)
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
                                    <td>{{ $trip->assignedDriver?->full_name ?? 'N/A' }}</td>
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
                    const table = document.getElementById('driverTripLogTable');
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

                    const statusFilter = document.getElementById('driverTripLogStatusFilter');
                    if (statusFilter) {
                        statusFilter.addEventListener('change', () => {
                            dt.column(4).search(statusFilter.value).draw();
                        });
                    }

                    const branchFilter = document.getElementById('driverTripLogBranchFilter');
                    if (branchFilter) {
                        branchFilter.addEventListener('change', () => {
                            dt.column(3).search(branchFilter.value).draw();
                        });
                    }
                });
            </script>
        @endpush
    @endif

    <div class="modal fade" id="archiveDriverModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Driver</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Delete driver <strong id="archiveDriverName"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="archiveDriverForm">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete Driver</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="forceDeleteDriverModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Driver Permanently</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Permanently delete driver <strong id="forceDeleteDriverName"></strong>? This cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="forceDeleteDriverForm">
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
            const archiveDriverModal = document.getElementById('archiveDriverModal');
            if (archiveDriverModal) {
                archiveDriverModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const action = button.getAttribute('data-action');
                    const name = button.getAttribute('data-name');
                    document.getElementById('archiveDriverForm').setAttribute('action', action);
                    document.getElementById('archiveDriverName').textContent = name;
                });
            }
        </script>
        <script>
            const forceDeleteDriverModal = document.getElementById('forceDeleteDriverModal');
            if (forceDeleteDriverModal) {
                forceDeleteDriverModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const action = button.getAttribute('data-action');
                    const name = button.getAttribute('data-name');
                    document.getElementById('forceDeleteDriverForm').setAttribute('action', action);
                    document.getElementById('forceDeleteDriverName').textContent = name;
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
                const currentUserRole = "{{ auth()->user()?->role }}";
                const dataUrl = "{{ route('drivers.data') }}" + (showArchived ? "?archived=1" : "");
                const showUrlTemplate = "{{ route('drivers.show', ['driver' => '__ID__']) }}";
                const editUrlTemplate = "{{ route('drivers.edit', ['driver' => '__ID__']) }}";
                const deleteUrlTemplate = "{{ route('drivers.destroy', ['driver' => '__ID__']) }}";
                const restoreUrlTemplate = "{{ route('drivers.restore', ['driver' => '__ID__']) }}";
                const forceDeleteUrlTemplate = "{{ route('drivers.force', ['driver' => '__ID__']) }}";

                const escapeHtml = (value) => String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');

                const statusBadge = (status) => {
                    switch ((status || '').toLowerCase()) {
                        case 'active':
                            return 'bg-success';
                        case 'inactive':
                            return 'bg-secondary';
                        default:
                            return 'bg-warning';
                    }
                };

                const renderRows = (rows) => {
                    if (window.jQuery && window.jQuery.fn.dataTable && window.jQuery.fn.dataTable.isDataTable(table)) {
                        window.jQuery(table).DataTable().destroy();
                    }

                    tbody.innerHTML = rows.map((driver) => {
                        const archivedActions = `
                            <a href="${showUrlTemplate.replace('__ID__', driver.public_id)}" class="btn btn-sm btn-outline-primary">View</a>
                            <form method="POST" action="${restoreUrlTemplate.replace('__ID__', driver.public_id)}" class="d-inline">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <input type="hidden" name="_method" value="PATCH">
                                <button type="submit" class="btn btn-sm btn-outline-success" data-loading>Restore</button>
                            </form>
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#forceDeleteDriverModal"
                                    data-action="${forceDeleteUrlTemplate.replace('__ID__', driver.public_id)}"
                                    data-name="${escapeHtml(driver.full_name)}">
                                Delete Permanently
                            </button>
                        `;
                        const activeActions = `
                            <a href="${showUrlTemplate.replace('__ID__', driver.public_id)}" class="btn btn-sm btn-outline-primary">View</a>
                            <a href="${editUrlTemplate.replace('__ID__', driver.public_id)}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#archiveDriverModal"
                                    data-action="${deleteUrlTemplate.replace('__ID__', driver.public_id)}"
                                    data-name="${escapeHtml(driver.full_name)}">
                                Delete
                            </button>
                        `;

                        return `
                            <tr>
                                <td>${escapeHtml(driver.full_name)}</td>
                                <td>${escapeHtml(driver.license_number)}</td>
                                <td>${escapeHtml(driver.license_expiry)}</td>
                                <td>${escapeHtml(driver.phone)}</td>
                                <td>
                                    <span class="badge ${statusBadge(driver.status)}">${escapeHtml(driver.status)}</span>
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
                        console.warn('Driver table refresh failed.');
                    }
                };

                let poller = null;
                const startPollingFallback = () => {
                    if (poller) {
                        return;
                    }
                    poller = setInterval(refreshTable, 30000);
                };

                const initDriversEcho = () => {
                    if (!realtimeEnabled) {
                        return null;
                    }
                    const echo = window.ChatEcho ?? window.Echo;
                    if (!echo || typeof echo.private !== 'function') {
                        return null;
                    }
                    return echo;
                };

                const subscribeDriversChannel = () => {
                    if (!realtimeEnabled) {
                        startPollingFallback();
                        return;
                    }
                    const echo = initDriversEcho();
                    if (!echo) {
                        startPollingFallback();
                        return;
                    }
                    echo.private('drivers.all')
                        .listen('.driver.changed', () => {
                            refreshTable();
                        })
                        .error(() => {
                            startPollingFallback();
                        });
                };

                subscribeDriversChannel();
                startPollingFallback();
            });
        </script>
    @endpush
</x-admin-layout>
