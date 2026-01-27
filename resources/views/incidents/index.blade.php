<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Incident Reports</h1>
            <p class="text-muted mb-0">Track safety events and follow ups.</p>
        </div>
        <div class="d-flex gap-2">
            @if (auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                <a class="btn btn-outline-primary" href="{{ route('incidents.export.csv') }}" data-download>Export CSV</a>
                <a class="btn btn-outline-dark" href="{{ route('incidents.export.pdf') }}" data-download>Export PDF</a>
            @endif
            @if (auth()->check())
                <a href="{{ route('incidents.create') }}" class="btn btn-primary" data-loading>New Incident</a>
            @endif
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle datatable" id="incidentReportsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Reference</th>
                            <th>Severity</th>
                            <th>Status</th>
                            <th>Incident Date</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($incidents as $incident)
                            <tr>
                                <td>{{ $incident->reference }}</td>
                                <td class="text-capitalize">{{ $incident->severity }}</td>
                                <td>
                                    <span class="badge bg-{{ $incident->status === 'resolved' ? 'success' : ($incident->status === 'under_review' ? 'warning text-dark' : ($incident->status === 'cancelled' ? 'secondary' : 'info')) }}">
                                        {{ str_replace('_', ' ', ucfirst($incident->status)) }}
                                    </span>
                                </td>
                                <td>{{ $incident->incident_date?->format('M d, Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('incidents.show', $incident) }}" class="btn btn-sm btn-outline-primary" data-loading>View</a>
                                    @if ($incident->status === \App\Models\IncidentReport::STATUS_OPEN)
                                        <a href="{{ route('incidents.edit', $incident) }}" class="btn btn-sm btn-outline-secondary" data-loading>Edit</a>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-warning"
                                                data-bs-toggle="modal"
                                                data-bs-target="#cancelIncidentModal"
                                                data-cancel-action="{{ route('incidents.cancel', $incident) }}"
                                                data-cancel-label="{{ $incident->reference }}">
                                            Cancel
                                        </button>
                                    @endif
                                    @if (auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN && $incident->status === \App\Models\IncidentReport::STATUS_OPEN)
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteIncidentModal"
                                                data-delete-action="{{ route('incidents.destroy', $incident) }}"
                                                data-delete-label="{{ $incident->reference }}">
                                            Delete
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

    @if (auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
        <div class="modal fade" id="deleteIncidentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Incident</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">Delete incident <strong id="deleteIncidentLabel"></strong>? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST" id="deleteIncidentForm">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete Incident</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                document.querySelectorAll('[data-delete-action]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const action = button.getAttribute('data-delete-action');
                        const label = button.getAttribute('data-delete-label');
                        const form = document.getElementById('deleteIncidentForm');
                        if (form) {
                            form.setAttribute('action', action);
                        }
                        const labelEl = document.getElementById('deleteIncidentLabel');
                        if (labelEl) {
                            labelEl.textContent = label;
                        }
                    });
                });
            </script>
        @endpush
    @endif

    <div class="modal fade" id="cancelIncidentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Incident</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Cancel incident <strong id="cancelIncidentLabel"></strong>? This cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Back</button>
                    <form method="POST" id="cancelIncidentForm">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-warning">Cancel Incident</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @php
        $currentUserData = [
            'id' => auth()->id(),
            'role' => auth()->user()?->role,
            'branch_id' => auth()->user()?->branch_id,
        ];
    @endphp

    @push('scripts')
        <script>
            document.addEventListener('click', (event) => {
                const button = event.target.closest('[data-cancel-action]');
                if (!button) {
                    return;
                }
                const action = button.getAttribute('data-cancel-action');
                const label = button.getAttribute('data-cancel-label');
                const form = document.getElementById('cancelIncidentForm');
                if (form) {
                    form.setAttribute('action', action);
                }
                const labelEl = document.getElementById('cancelIncidentLabel');
                if (labelEl) {
                    labelEl.textContent = label;
                }
            });

            document.addEventListener('click', (event) => {
                const button = event.target.closest('[data-delete-action]');
                if (!button) {
                    return;
                }
                const action = button.getAttribute('data-delete-action');
                const label = button.getAttribute('data-delete-label');
                const form = document.getElementById('deleteIncidentForm');
                if (form) {
                    form.setAttribute('action', action);
                }
                const labelEl = document.getElementById('deleteIncidentLabel');
                if (labelEl) {
                    labelEl.textContent = label;
                }
            });
        </script>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const table = document.getElementById('incidentReportsTable');
                if (!table) {
                    return;
                }
                const tbody = table.querySelector('tbody');
                if (!tbody) {
                    return;
                }

                const currentUser = @json($currentUserData);
                const dataUrl = "{{ route('incidents.data') }}";
                const showUrlTemplate = "{{ route('incidents.show', ['incident' => '__ID__']) }}";
                const editUrlTemplate = "{{ route('incidents.edit', ['incident' => '__ID__']) }}";
                const cancelUrlTemplate = "{{ route('incidents.cancel', ['incident' => '__ID__']) }}";
                const deleteUrlTemplate = "{{ route('incidents.destroy', ['incident' => '__ID__']) }}";

                let poller = null;
                const startPollingFallback = () => {
                    if (poller) {
                        return;
                    }
                    poller = setInterval(refreshTable, 30000);
                };

                const escapeHtml = (value) => String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');

                const statusBadge = (status) => {
                    switch ((status || '').toLowerCase()) {
                        case 'resolved':
                            return 'success';
                        case 'under_review':
                            return 'warning text-dark';
                        case 'cancelled':
                            return 'secondary';
                        default:
                            return 'info';
                    }
                };

                const canEdit = (incident) => {
                    return incident.status === 'open';
                };

                const canDelete = (incident) => {
                    return currentUser.role === 'super_admin' && incident.status === 'open';
                };

                const renderRows = (rows) => {
                    if (window.jQuery && window.jQuery.fn.dataTable && window.jQuery.fn.dataTable.isDataTable(table)) {
                        window.jQuery(table).DataTable().destroy();
                    }

                    tbody.innerHTML = rows.map((incident) => {
                        const viewHtml = `<a href="${showUrlTemplate.replace('__ID__', incident.id)}" class="btn btn-sm btn-outline-primary" data-loading>View</a>`;
                        const editHtml = canEdit(incident)
                            ? `<a href="${editUrlTemplate.replace('__ID__', incident.id)}" class="btn btn-sm btn-outline-secondary" data-loading>Edit</a>`
                            : '';
                        const cancelHtml = canEdit(incident)
                            ? `<button type="button"
                                    class="btn btn-sm btn-outline-warning"
                                    data-bs-toggle="modal"
                                    data-bs-target="#cancelIncidentModal"
                                    data-cancel-action="${cancelUrlTemplate.replace('__ID__', incident.id)}"
                                    data-cancel-label="${escapeHtml(incident.reference)}">
                                Cancel
                               </button>`
                            : '';
                        const deleteHtml = canDelete(incident)
                            ? `<button type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteIncidentModal"
                                    data-delete-action="${deleteUrlTemplate.replace('__ID__', incident.id)}"
                                    data-delete-label="${escapeHtml(incident.reference)}">
                                Delete
                               </button>`
                            : '';

                        return `
                            <tr>
                                <td>${escapeHtml(incident.reference)}</td>
                                <td class="text-capitalize">${escapeHtml(incident.severity)}</td>
                                <td>
                                    <span class="badge bg-${statusBadge(incident.status)}">
                                        ${escapeHtml(String(incident.status || '').replace('_', ' '))}
                                    </span>
                                </td>
                                <td>${escapeHtml(incident.incident_date)}</td>
                                <td class="text-end">${viewHtml} ${editHtml} ${cancelHtml} ${deleteHtml}</td>
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
                        console.warn('Incident table refresh failed.');
                    }
                };

                const initIncidentsEcho = () => {
                    if (window.IncidentEcho && typeof window.IncidentEcho.private === 'function') {
                        return window.IncidentEcho;
                    }
                    if (window.ChatEcho && typeof window.ChatEcho.private === 'function') {
                        window.IncidentEcho = window.ChatEcho;
                        return window.IncidentEcho;
                    }
                    if (typeof window.Echo !== 'function') {
                        return null;
                    }
                    window.Pusher = window.Pusher ?? Pusher;
                    window.IncidentEcho = new window.Echo({
                        broadcaster: 'pusher',
                        cluster: 'mt1',
                        key: "{{ config('broadcasting.connections.reverb.key') }}",
                        wsHost: "{{ config('broadcasting.connections.reverb.options.host') }}",
                        wsPort: {{ config('broadcasting.connections.reverb.options.port') }},
                        wssPort: {{ config('broadcasting.connections.reverb.options.port') }},
                        forceTLS: "{{ config('broadcasting.connections.reverb.options.scheme') }}" === 'https',
                        enabledTransports: ['ws', 'wss'],
                        disableStats: true,
                        authEndpoint: '/broadcasting/auth',
                        auth: {
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                            }
                        },
                    });
                    return window.IncidentEcho;
                };

                const subscribeIncidentChannels = () => {
                    const echo = initIncidentsEcho();
                    if (!echo || typeof echo.private !== 'function') {
                        startPollingFallback();
                        return;
                    }

                    if (['super_admin', 'fleet_manager'].includes(currentUser.role)) {
                        echo.private('incidents.all')
                            .listen('.incident.changed', refreshTable);
                    }

                    if (currentUser.branch_id) {
                        echo.private(`incidents.branch.${currentUser.branch_id}`)
                            .listen('.incident.changed', refreshTable);
                    }

                    if (currentUser.id) {
                        echo.private(`incidents.user.${currentUser.id}`)
                            .listen('.incident.changed', refreshTable);
                    }

                    const connection = echo.connector?.pusher?.connection;
                    if (connection && typeof connection.bind === 'function') {
                        connection.bind('error', startPollingFallback);
                        connection.bind('disconnected', startPollingFallback);
                    }

                    setTimeout(() => {
                        const state = echo.connector?.pusher?.connection?.state;
                        if (state !== 'connected') {
                            startPollingFallback();
                        }
                    }, 3000);
                };

                refreshTable();
                subscribeIncidentChannels();
            });
        </script>
    @endpush
</x-admin-layout>
