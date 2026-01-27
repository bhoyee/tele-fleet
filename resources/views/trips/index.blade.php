<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Trip Requests</h1>
            <p class="text-muted mb-0">Track requests, approvals, and assignments.</p>
        </div>
        @if (in_array(auth()->user()->role, [\App\Models\User::ROLE_BRANCH_ADMIN, \App\Models\User::ROLE_BRANCH_HEAD, \App\Models\User::ROLE_SUPER_ADMIN], true))
            <a href="{{ route('trips.create') }}" class="btn btn-primary">New Trip</a>
        @endif
    </div>

    @php
        $user = auth()->user();
        $hasDeleteAccess = false;
    @endphp

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle datatable" id="tripRequestsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Request #</th>
                            <th>Purpose</th>
                            <th>Trip Date</th>
                            <th>Status</th>
                            <th>Assignment</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trips as $trip)
                            @php
                                $canManage = $user && (
                                    in_array($user->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true)
                                    || ($user->role === \App\Models\User::ROLE_BRANCH_ADMIN && $trip->requested_by_user_id === $user->id)
                                    || ($user->role === \App\Models\User::ROLE_BRANCH_HEAD && $user->branch_id && $trip->branch_id === $user->branch_id)
                                );
                                $canEdit = $canManage && (
                                    in_array($user->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true)
                                    || $trip->status === 'pending'
                                );
                                $hasDeleteAccess = $hasDeleteAccess || $canManage;
                                if ($trip->trip_time) {
                                    try {
                                        $tripMoment = \Illuminate\Support\Carbon::createFromFormat('Y-m-d H:i', $trip->trip_date->format('Y-m-d') . ' ' . $trip->trip_time);
                                    } catch (\Exception $e) {
                                        $tripMoment = \Illuminate\Support\Carbon::parse($trip->trip_date->format('Y-m-d') . ' ' . $trip->trip_time);
                                    }
                                } else {
                                    $tripMoment = $trip->trip_date->copy()->startOfDay();
                                }
                                $tripStatus = strtolower((string) $trip->status);
                                $canCancel = $tripStatus === 'pending' || ($tripStatus !== 'completed' && now()->lt($tripMoment));
                            @endphp
                            <tr>
                                <td>{{ $trip->request_number }}</td>
                                @php
                                    $shouldBlurPurpose = $user
                                        && $user->role === \App\Models\User::ROLE_BRANCH_ADMIN
                                        && $trip->requested_by_user_id !== $user->id;
                                @endphp
                                <td>
                                    @if ($shouldBlurPurpose)
                                        <span class="text-muted">Restricted</span>
                                    @else
                                        {{ $trip->purpose }}
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $trip->trip_date?->format('M d, Y') }}</div>
                                    @php
                                        $tripTime = $trip->trip_time;
                                        if ($tripTime) {
                                            try {
                                                $tripTime = \Illuminate\Support\Carbon::parse($tripTime)->format('g:i A');
                                            } catch (\Exception $e) {
                                                $tripTime = \Illuminate\Support\Carbon::parse($trip->trip_time)->format('g:i A');
                                            }
                                        }
                                    @endphp
                                    <small class="text-muted">{{ $tripTime ?: 'N/A' }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $trip->status === 'approved' ? 'success' : ($trip->status === 'rejected' ? 'danger' : ($trip->status === 'assigned' ? 'primary' : ($trip->status === 'completed' ? 'dark' : 'secondary'))) }}">
                                        {{ ucfirst($trip->status) }}
                                    </span>
                                    @php
                                        $dueStatus = null;
                                        if (in_array($user?->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true)) {
                                            $dueStatus = $trip->dueStatus();
                                        }
                                    @endphp
                                    @if ($dueStatus)
                                        <span class="badge bg-{{ $dueStatus === 'overdue' ? 'danger' : 'warning' }} ms-1">
                                            {{ ucfirst($dueStatus) }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($trip->assigned_vehicle_id && $trip->assigned_driver_id)
                                        <span class="badge bg-primary">Assigned</span>
                                    @else
                                        <span class="badge bg-secondary">Unassigned</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($canEdit)
                                        <a href="{{ route('trips.edit', $trip) }}" class="btn btn-sm btn-outline-secondary" data-loading>Edit</a>
                                    @endif
                                    @if (! ($user?->role === \App\Models\User::ROLE_BRANCH_ADMIN && $trip->requested_by_user_id !== $user->id))
                                        <a href="{{ route('trips.show', $trip) }}" class="btn btn-sm btn-outline-primary" data-loading>View</a>
                                    @endif
                                    @if ($canManage && $trip->status === 'pending')
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteTripModal"
                                                data-delete-action="{{ route('trips.destroy', $trip) }}"
                                                data-delete-label="{{ $trip->request_number }}">
                                            Delete
                                        </button>
                                    @endif
                                    @if ($canManage && $canCancel)
                                        <button type="button"
                                                class="btn btn-sm btn-outline-warning"
                                                data-bs-toggle="modal"
                                                data-bs-target="#cancelTripModal"
                                                data-cancel-action="{{ route('trips.cancel', $trip) }}"
                                                data-cancel-label="{{ $trip->request_number }}">
                                            Cancel
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

    @if ($hasDeleteAccess)
        <div class="modal fade" id="deleteTripModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Trip</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">Delete trip <strong id="deleteTripLabel"></strong>? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST" id="deleteTripForm">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete Trip</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                document.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-delete-action]');
                    if (!button) {
                        return;
                    }
                    const action = button.getAttribute('data-delete-action');
                    const label = button.getAttribute('data-delete-label');
                    const form = document.getElementById('deleteTripForm');
                    if (form) {
                        form.setAttribute('action', action);
                    }
                    const labelEl = document.getElementById('deleteTripLabel');
                    if (labelEl) {
                        labelEl.textContent = label;
                    }
                });
            </script>
        @endpush
    @endif

    <div class="modal fade" id="cancelTripModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Trip</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Cancel trip <strong id="cancelTripLabel"></strong>? This cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Back</button>
                    <form method="POST" id="cancelTripForm">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-warning">Cancel Trip</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @php
        $currentUserData = [
            'id' => $user?->id,
            'role' => $user?->role,
            'branch_id' => $user?->branch_id,
        ];
    @endphp

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const table = document.getElementById('tripRequestsTable');
                if (!table) {
                    return;
                }
                const tbody = table.querySelector('tbody');
                if (!tbody) {
                    return;
                }

                const currentUser = @json($currentUserData);
                const dataUrl = "{{ route('trips.data') }}";
                const editUrlTemplate = "{{ route('trips.edit', ['tripRequest' => '__ID__']) }}";
                const showUrlTemplate = "{{ route('trips.show', ['tripRequest' => '__ID__']) }}";
                const deleteUrlTemplate = "{{ route('trips.destroy', ['tripRequest' => '__ID__']) }}";
                const cancelUrlTemplate = "{{ route('trips.cancel', ['tripRequest' => '__ID__']) }}";

                const escapeHtml = (value) => String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');

                const canManageTrip = (trip) => {
                    if (!currentUser?.role) return false;
                    if (['super_admin', 'fleet_manager'].includes(currentUser.role)) {
                        return true;
                    }
                    if (currentUser.role === 'branch_admin') {
                        return Number(trip.requested_by_user_id) === Number(currentUser.id);
                    }
                    if (currentUser.role === 'branch_head') {
                        return Number(trip.branch_id) === Number(currentUser.branch_id);
                    }
                    return false;
                };

                const canEditTrip = (trip) => {
                    if (!canManageTrip(trip)) return false;
                    if (['super_admin', 'fleet_manager'].includes(currentUser.role)) {
                        return true;
                    }
                    return trip.status === 'pending';
                };

                const canViewTrip = (trip) => {
                    if (currentUser.role === 'branch_admin') {
                        return Number(trip.requested_by_user_id) === Number(currentUser.id);
                    }
                    return true;
                };

                const statusClass = (status) => {
                    switch ((status || '').toLowerCase()) {
                        case 'approved':
                            return 'success';
                        case 'rejected':
                            return 'danger';
                        case 'assigned':
                            return 'primary';
                        case 'completed':
                            return 'dark';
                        case 'cancelled':
                            return 'secondary';
                        default:
                            return 'secondary';
                    }
                };

                const canCancelTrip = (trip) => {
                    const status = String(trip.status || '').toLowerCase();
                    if (status === 'pending') return true;
                    if (!trip.trip_date_raw) return false;
                    const timeValue = trip.trip_time_raw ? trip.trip_time_raw : '00:00';
                    const tripMoment = new Date(`${trip.trip_date_raw}T${timeValue}`);
                    return status !== 'completed' && new Date() < tripMoment;
                };

                const renderRows = (rows) => {
                    if (window.jQuery && window.jQuery.fn.dataTable && window.jQuery.fn.dataTable.isDataTable(table)) {
                        window.jQuery(table).DataTable().destroy();
                    }

                    tbody.innerHTML = rows.map((trip) => {
                        const restrictedPurpose = currentUser.role === 'branch_admin'
                            && Number(trip.requested_by_user_id) !== Number(currentUser.id);
                        const purposeHtml = restrictedPurpose
                            ? '<span class="text-muted">Restricted</span>'
                            : escapeHtml(trip.purpose);
                        const assignedHtml = trip.assigned
                            ? '<span class="badge bg-primary">Assigned</span>'
                            : '<span class="badge bg-secondary">Unassigned</span>';
                        const editHtml = canEditTrip(trip)
                            ? `<a href="${editUrlTemplate.replace('__ID__', trip.id)}" class="btn btn-sm btn-outline-secondary" data-loading>Edit</a>`
                            : '';
                        const viewHtml = canViewTrip(trip)
                            ? `<a href="${showUrlTemplate.replace('__ID__', trip.id)}" class="btn btn-sm btn-outline-primary" data-loading>View</a>`
                            : '';
                        const deleteHtml = canManageTrip(trip) && trip.status === 'pending'
                            ? `<button type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteTripModal"
                                    data-delete-action="${deleteUrlTemplate.replace('__ID__', trip.id)}"
                                    data-delete-label="${escapeHtml(trip.request_number)}">
                                Delete
                               </button>`
                            : '';
                        const cancelHtml = canManageTrip(trip) && canCancelTrip(trip)
                            ? `<button type="button"
                                    class="btn btn-sm btn-outline-warning"
                                    data-bs-toggle="modal"
                                    data-bs-target="#cancelTripModal"
                                    data-cancel-action="${cancelUrlTemplate.replace('__ID__', trip.id)}"
                                    data-cancel-label="${escapeHtml(trip.request_number)}">
                                Cancel
                               </button>`
                            : '';

                        const statusLabel = trip.status
                            ? trip.status.charAt(0).toUpperCase() + trip.status.slice(1)
                            : 'Pending';
                        const dueBadge = (['super_admin', 'fleet_manager'].includes(currentUser.role) && trip.due_status)
                            ? `<span class="badge bg-${trip.due_status === 'overdue' ? 'danger' : 'warning'} ms-1">${escapeHtml(trip.due_status.charAt(0).toUpperCase() + trip.due_status.slice(1))}</span>`
                            : '';
                        return `
                            <tr>
                                <td>${escapeHtml(trip.request_number)}</td>
                                <td>${purposeHtml}</td>
                                <td>
                                    <div>${escapeHtml(trip.trip_date)}</div>
                                    <small class="text-muted">${escapeHtml(trip.trip_time)}</small>
                                </td>
                                <td>
                                    <span class="badge bg-${statusClass(trip.status)}">${escapeHtml(statusLabel)}</span>
                                    ${dueBadge}
                                </td>
                                <td>${assignedHtml}</td>
                                <td class="text-end">${editHtml} ${viewHtml} ${deleteHtml} ${cancelHtml}</td>
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

                let poller = null;
                const startPollingFallback = () => {
                    if (poller) {
                        return;
                    }
                    poller = setInterval(refreshTable, 30000);
                };

                const refreshTable = async () => {
                    try {
                        const response = await fetch(dataUrl, { headers: { 'Accept': 'application/json' } });
                        if (!response.ok) return;
                        const payload = await response.json();
                        renderRows(payload.data || []);
                    } catch (error) {
                        console.warn('Trip table refresh failed.');
                    }
                };

                const initTripsEcho = () => {
                    if (window.TripEcho && typeof window.TripEcho.private === 'function') {
                        return window.TripEcho;
                    }
                    if (window.ChatEcho && typeof window.ChatEcho.private === 'function') {
                        window.TripEcho = window.ChatEcho;
                        return window.TripEcho;
                    }
                    if (typeof window.Echo !== 'function') {
                        return null;
                    }
                    window.Pusher = window.Pusher ?? Pusher;
                    window.TripEcho = new window.Echo({
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
                    return window.TripEcho;
                };

                const subscribeTripChannels = () => {
                    const echo = initTripsEcho();
                    if (!echo || typeof echo.private !== 'function') {
                        startPollingFallback();
                        return;
                    }

                    if (['super_admin', 'fleet_manager'].includes(currentUser.role)) {
                        echo.private('trips.all')
                            .listen('.trip.changed', refreshTable);
                    }

                    if (currentUser.branch_id) {
                        echo.private(`trips.branch.${currentUser.branch_id}`)
                            .listen('.trip.changed', refreshTable);
                    }

                    if (currentUser.id) {
                        echo.private(`trips.user.${currentUser.id}`)
                            .listen('.trip.changed', refreshTable);
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
                subscribeTripChannels();
            });

            document.addEventListener('click', (event) => {
                const button = event.target.closest('[data-cancel-action]');
                if (!button) {
                    return;
                }
                const action = button.getAttribute('data-cancel-action');
                const label = button.getAttribute('data-cancel-label');
                const form = document.getElementById('cancelTripForm');
                if (form) {
                    form.setAttribute('action', action);
                }
                const labelEl = document.getElementById('cancelTripLabel');
                if (labelEl) {
                    labelEl.textContent = label;
                }
            });
        </script>
    @endpush
</x-admin-layout>
