<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Drivers</h1>
            <p class="text-muted mb-0">Manage driver records and compliance.</p>
        </div>
        <a href="{{ route('drivers.create') }}" class="btn btn-primary">New Driver</a>
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
                                    <a href="{{ route('drivers.show', $driver) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    <a href="{{ route('drivers.edit', $driver) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#archiveDriverModal"
                                            data-action="{{ route('drivers.destroy', $driver) }}"
                                            data-name="{{ $driver->full_name }}">
                                        Archive
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="archiveDriverModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Archive Driver</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Archive driver <strong id="archiveDriverName"></strong>? You can restore later if needed.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="archiveDriverForm">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Archive Driver</button>
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
            document.addEventListener('DOMContentLoaded', () => {
                const table = document.querySelector('.datatable');
                if (!table) {
                    return;
                }
                const tbody = table.querySelector('tbody');
                if (!tbody) {
                    return;
                }

                const dataUrl = "{{ route('drivers.data') }}";
                const showUrlTemplate = "{{ route('drivers.show', ['driver' => '__ID__']) }}";
                const editUrlTemplate = "{{ route('drivers.edit', ['driver' => '__ID__']) }}";
                const deleteUrlTemplate = "{{ route('drivers.destroy', ['driver' => '__ID__']) }}";

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
                                    <a href="${showUrlTemplate.replace('__ID__', driver.id)}" class="btn btn-sm btn-outline-primary">View</a>
                                    <a href="${editUrlTemplate.replace('__ID__', driver.id)}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#archiveDriverModal"
                                            data-action="${deleteUrlTemplate.replace('__ID__', driver.id)}"
                                            data-name="${escapeHtml(driver.full_name)}">
                                        Archive
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
                    const echo = window.ChatEcho ?? window.Echo;
                    if (!echo || typeof echo.private !== 'function') {
                        return null;
                    }
                    return echo;
                };

                const subscribeDriversChannel = () => {
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
