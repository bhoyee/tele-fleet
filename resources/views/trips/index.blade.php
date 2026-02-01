<x-admin-layout>
    <style>
        .trip-actions {
            flex-wrap: wrap;
        }

        .trip-analytics .stat-card {
            min-height: 0;
        }

        #tripRequestsTable {
            width: 100%;
        }

        #tripRequestsTable th,
        #tripRequestsTable td {
            white-space: normal;
        }

        #tripRequestsTable .dtr-control {
            width: 2.25rem;
        }

        .trip-action-icons {
            display: none;
            gap: 0.5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .trip-action-icons .btn {
            padding: 0.35rem 0.5rem;
        }

        .dataTables_wrapper .row {
            align-items: center;
        }

        @media (max-width: 767px) {
            .trip-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .trip-actions {
                width: 100%;
            }

            .trip-actions .btn {
                flex: 1 1 auto;
            }

            .trip-analytics .stat-value {
                font-size: 1.5rem;
            }

            #tripRequestsTable {
                font-size: 0.9rem;
                min-width: 0;
            }

            #tripRequestsTable th,
            #tripRequestsTable td {
                padding: 0.75rem;
                white-space: normal;
            }

            .trip-table-wrap {
                overflow-x: visible;
            }

            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter,
            .dataTables_wrapper .dataTables_info,
            .dataTables_wrapper .dataTables_paginate {
                width: 100%;
                text-align: left;
                padding: 0.75rem 1rem;
            }

            .dataTables_wrapper .dataTables_filter {
                margin-top: 0.5rem;
            }

            .dataTables_wrapper .dataTables_paginate {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                justify-content: flex-start;
            }

            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter {
                float: none;
            }

            .dataTables_wrapper .dataTables_filter input {
                width: 100%;
            }

            .dataTables_wrapper .dataTables_paginate .pagination {
                flex-wrap: wrap;
                gap: 0.35rem;
            }

            .trip-action-buttons {
                display: none !important;
            }

            .trip-action-icons {
                display: inline-flex !important;
            }

        }
    </style>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Trip Requests</h1>
            <p class="text-muted mb-0">Track requests, approvals, and assignments.</p>
        </div>
        <div class="d-flex gap-2 trip-actions">
            @if (auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                @if (!($showArchived ?? false))
                    <a href="{{ route('trips.index', ['archived' => 1]) }}" class="btn btn-outline-secondary">Show Archived</a>
                @else
                    <a href="{{ route('trips.index') }}" class="btn btn-outline-secondary">Back to Active</a>
                @endif
            @endif
            @if (in_array(auth()->user()->role, [\App\Models\User::ROLE_BRANCH_ADMIN, \App\Models\User::ROLE_BRANCH_HEAD, \App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true))
                <a href="{{ route('trips.create') }}" class="btn btn-primary">New Trip</a>
            @endif
        </div>
    </div>

    @if (auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN && $analytics)
        <div class="card shadow-sm border-0 mb-4 trip-analytics">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span>Trip Analytics ({{ $analytics['range_label'] }})</span>
                    <span class="text-muted small">All-time Trips total: {{ number_format($analytics['all_time'] ?? 0) }}</span>
                </div>
                <span class="text-muted small">Approval {{ $analytics['approval_rate'] }}% • Completion {{ $analytics['completion_rate'] }}%</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-label">Total Trips</div>
                            <div class="stat-value">{{ $analytics['total'] }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-label">Pending</div>
                            <div class="stat-value">{{ $analytics['pending'] }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-label">Approved</div>
                            <div class="stat-value">{{ $analytics['approved'] }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-label">Assigned</div>
                            <div class="stat-value">{{ $analytics['assigned'] }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-label">Completed</div>
                            <div class="stat-value">{{ $analytics['completed'] }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-label">Rejected</div>
                            <div class="stat-value">{{ $analytics['rejected'] }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-label">Cancelled</div>
                            <div class="stat-value">{{ $analytics['cancelled'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @php
        $user = auth()->user();
        $hasDeleteAccess = false;
    @endphp

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive trip-table-wrap">
                <table class="table align-middle datatable dt-responsive dtr-inline" id="tripRequestsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="dtr-control" data-priority="1"></th>
                            <th data-priority="1">Request #</th>
                            <th data-priority="6">Purpose</th>
                            <th data-priority="7">Trip Date</th>
                            <th data-priority="2">Status</th>
                            <th data-priority="5">Assignment</th>
                            <th class="text-end action-col" data-priority="10">Actions</th>
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
                                <td class="dtr-control" data-label=""></td>
                                <td data-label="Request #">{{ $trip->request_number }}</td>
                                @php
                                    $shouldBlurPurpose = $user
                                        && $user->role === \App\Models\User::ROLE_BRANCH_ADMIN
                                        && $trip->requested_by_user_id !== $user->id;
                                @endphp
                                <td data-label="Purpose">
                                    @if ($shouldBlurPurpose)
                                        <span class="text-muted">Restricted</span>
                                    @else
                                        {{ $trip->purpose }}
                                    @endif
                                </td>
                                <td data-label="Trip Date">
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
                                <td data-label="Status">
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
                                <td data-label="Assignment">
                                    @if ($trip->assigned_vehicle_id && $trip->assigned_driver_id)
                                        <span class="badge bg-primary">Assigned</span>
                                    @else
                                        <span class="badge bg-secondary">Unassigned</span>
                                    @endif
                                    @if ($trip->requires_reassignment)
                                        <span class="badge bg-warning text-dark ms-2">Reassign</span>
                                    @endif
                                </td>
                                <td class="text-end action-col" data-label="Actions">
                                    <div class="trip-action-buttons d-inline-flex gap-1 flex-wrap justify-content-end">
                                        @if ($canEdit && !($showArchived ?? false))
                                            <a href="{{ route('trips.edit', $trip) }}" class="btn btn-sm btn-outline-secondary" data-loading>Edit</a>
                                        @endif
                                        @if (! ($user?->role === \App\Models\User::ROLE_BRANCH_ADMIN && $trip->requested_by_user_id !== $user->id))
                                            <a href="{{ route('trips.show', $trip) }}" class="btn btn-sm btn-outline-primary" data-loading>View</a>
                                        @endif
                                        @if ($canManage && !($showArchived ?? false))
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteTripModal"
                                                    data-delete-action="{{ route('trips.destroy', $trip) }}"
                                                    data-delete-label="{{ $trip->request_number }}">
                                                Delete
                                            </button>
                                        @endif
                                        @if (($showArchived ?? false) && auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                                            <form method="POST" action="{{ route('trips.restore', $trip->id) }}" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-success" data-loading>Restore</button>
                                            </form>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#forceDeleteTripModal"
                                                    data-delete-action="{{ route('trips.force', $trip->id) }}"
                                                    data-delete-label="{{ $trip->request_number }}">
                                                Delete Permanently
                                            </button>
                                        @endif
                                        @if ($canManage && $canCancel && !($showArchived ?? false))
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-warning"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#cancelTripModal"
                                                    data-cancel-action="{{ route('trips.cancel', $trip) }}"
                                                    data-cancel-label="{{ $trip->request_number }}">
                                                Cancel
                                            </button>
                                        @endif
                                    </div>
                                    <div class="trip-action-icons">
                                        @if ($canEdit && !($showArchived ?? false))
                                            <a href="{{ route('trips.edit', $trip) }}" class="btn btn-outline-secondary" data-loading title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @endif
                                        @if (! ($user?->role === \App\Models\User::ROLE_BRANCH_ADMIN && $trip->requested_by_user_id !== $user->id))
                                            <a href="{{ route('trips.show', $trip) }}" class="btn btn-outline-primary" data-loading title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        @endif
                                        @if ($canManage && !($showArchived ?? false))
                                            <button type="button"
                                                    class="btn btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteTripModal"
                                                    data-delete-action="{{ route('trips.destroy', $trip) }}"
                                                    data-delete-label="{{ $trip->request_number }}"
                                                    title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endif
                                        @if (($showArchived ?? false) && auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                                            <form method="POST" action="{{ route('trips.restore', $trip->id) }}" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-outline-success" data-loading title="Restore">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            </form>
                                            <button type="button"
                                                    class="btn btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#forceDeleteTripModal"
                                                    data-delete-action="{{ route('trips.force', $trip->id) }}"
                                                    data-delete-label="{{ $trip->request_number }}"
                                                    title="Delete permanently">
                                                <i class="bi bi-x-octagon"></i>
                                            </button>
                                        @endif
                                        @if ($canManage && $canCancel && !($showArchived ?? false))
                                            <button type="button"
                                                    class="btn btn-outline-warning"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#cancelTripModal"
                                                    data-cancel-action="{{ route('trips.cancel', $trip) }}"
                                                    data-cancel-label="{{ $trip->request_number }}"
                                                    title="Cancel">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
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

    @if (auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-header">Trip History (Completed, Cancelled, Rejected)</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle datatable dt-responsive dtr-inline trip-history-table">
                        <thead class="table-light">
                            <tr>
                                <th class="dtr-control" data-priority="1"></th>
                                <th data-priority="1">Request #</th>
                                <th data-priority="6">Requester</th>
                                <th data-priority="7">Branch</th>
                                <th data-priority="8">Trip Date</th>
                                <th data-priority="2">Status</th>
                                <th class="text-end action-col" data-priority="10">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($historyTrips ?? [] as $trip)
                                <tr>
                                    <td class="dtr-control" data-label=""></td>
                                    <td>{{ $trip->request_number }}</td>
                                    <td>{{ $trip->requestedBy?->name ?? 'N/A' }}</td>
                                    <td>{{ $trip->branch?->name ?? 'N/A' }}</td>
                                    <td>{{ $trip->trip_date?->format('M d, Y') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $trip->status === 'completed' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($trip->status) }}
                                        </span>
                                    </td>
                                    <td class="text-end action-col">
                                        <a href="{{ route('trips.show', $trip) }}" class="btn btn-sm btn-outline-primary" data-loading>View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-muted">—</td>
                                    <td class="text-muted">—</td>
                                    <td class="text-muted">No history yet.</td>
                                    <td class="text-muted">—</td>
                                    <td class="text-muted">—</td>
                                    <td class="text-muted">—</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if ($hasDeleteAccess && !($showArchived ?? false))
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

    @if (($showArchived ?? false) && auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
        <div class="modal fade" id="forceDeleteTripModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Trip Permanently</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">Permanently delete trip <strong id="forceDeleteTripLabel"></strong>? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST" id="forceDeleteTripForm">
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
                document.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-delete-action]');
                    if (!button) {
                        return;
                    }
                    const form = document.getElementById('forceDeleteTripForm');
                    if (!form) {
                        return;
                    }
                    const action = button.getAttribute('data-delete-action');
                    const label = button.getAttribute('data-delete-label');
                    if (action) {
                        form.setAttribute('action', action);
                    }
                    const labelEl = document.getElementById('forceDeleteTripLabel');
                    if (labelEl && label) {
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
                const showArchived = @json($showArchived ?? false);
                const realtimeEnabled = {{ config('app.realtime_enabled') ? 'true' : 'false' }};
                const dataUrl = "{{ route('trips.data') }}" + (showArchived ? "?archived=1" : "");
                const editUrlTemplate = "{{ route('trips.edit', ['tripRequest' => '__ID__']) }}";
                const showUrlTemplate = "{{ route('trips.show', ['tripRequest' => '__ID__']) }}";
                const deleteUrlTemplate = "{{ route('trips.destroy', ['tripRequest' => '__ID__']) }}";
                const cancelUrlTemplate = "{{ route('trips.cancel', ['tripRequest' => '__ID__']) }}";
                const restoreUrlTemplate = "{{ route('trips.restore', ['tripRequest' => '__ID__']) }}";
                const forceDeleteUrlTemplate = "{{ route('trips.force', ['tripRequest' => '__ID__']) }}";

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
                        const editHtml = canEditTrip(trip) && !showArchived
                            ? `<a href="${editUrlTemplate.replace('__ID__', trip.id)}" class="btn btn-sm btn-outline-secondary" data-loading>Edit</a>`
                            : '';
                        const viewHtml = canViewTrip(trip)
                            ? `<a href="${showUrlTemplate.replace('__ID__', trip.id)}" class="btn btn-sm btn-outline-primary" data-loading>View</a>`
                            : '';
                        const deleteHtml = canManageTrip(trip) && !showArchived
                            ? `<button type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteTripModal"
                                    data-delete-action="${deleteUrlTemplate.replace('__ID__', trip.id)}"
                                    data-delete-label="${escapeHtml(trip.request_number)}">
                                Delete
                               </button>`
                            : '';
                        const restoreHtml = showArchived && currentUser.role === 'super_admin'
                            ? `
                                <form method="POST" action="${restoreUrlTemplate.replace('__ID__', trip.id)}" class="d-inline">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <input type="hidden" name="_method" value="PATCH">
                                    <button type="submit" class="btn btn-sm btn-outline-success" data-loading>Restore</button>
                                </form>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#forceDeleteTripModal"
                                        data-delete-action="${forceDeleteUrlTemplate.replace('__ID__', trip.id)}"
                                        data-delete-label="${escapeHtml(trip.request_number)}">
                                    Delete Permanently
                                </button>
                              `
                            : '';
                        const cancelHtml = canManageTrip(trip) && canCancelTrip(trip) && !showArchived
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
                        const editIcon = canEditTrip(trip) && !showArchived
                            ? `<a href="${editUrlTemplate.replace('__ID__', trip.id)}" class="btn btn-outline-secondary" data-loading title="Edit"><i class="bi bi-pencil"></i></a>`
                            : '';
                        const viewIcon = canViewTrip(trip)
                            ? `<a href="${showUrlTemplate.replace('__ID__', trip.id)}" class="btn btn-outline-primary" data-loading title="View"><i class="bi bi-eye"></i></a>`
                            : '';
                        const deleteIcon = canManageTrip(trip) && !showArchived
                            ? `<button type="button"
                                    class="btn btn-outline-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteTripModal"
                                    data-delete-action="${deleteUrlTemplate.replace('__ID__', trip.id)}"
                                    data-delete-label="${escapeHtml(trip.request_number)}"
                                    title="Delete">
                                <i class="bi bi-trash"></i>
                               </button>`
                            : '';
                        const restoreIcon = showArchived && currentUser.role === 'super_admin'
                            ? `
                                <form method="POST" action="${restoreUrlTemplate.replace('__ID__', trip.id)}" class="d-inline">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <input type="hidden" name="_method" value="PATCH">
                                    <button type="submit" class="btn btn-outline-success" data-loading title="Restore">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                </form>
                                <button type="button"
                                        class="btn btn-outline-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#forceDeleteTripModal"
                                        data-delete-action="${forceDeleteUrlTemplate.replace('__ID__', trip.id)}"
                                        data-delete-label="${escapeHtml(trip.request_number)}"
                                        title="Delete permanently">
                                    <i class="bi bi-x-octagon"></i>
                                </button>
                              `
                            : '';
                        const cancelIcon = canManageTrip(trip) && canCancelTrip(trip) && !showArchived
                            ? `<button type="button"
                                    class="btn btn-outline-warning"
                                    data-bs-toggle="modal"
                                    data-bs-target="#cancelTripModal"
                                    data-cancel-action="${cancelUrlTemplate.replace('__ID__', trip.id)}"
                                    data-cancel-label="${escapeHtml(trip.request_number)}"
                                    title="Cancel">
                                <i class="bi bi-x-circle"></i>
                               </button>`
                            : '';

                        return `
                            <tr>
                                <td class="dtr-control" data-label=""></td>
                                <td data-label="Request #">${escapeHtml(trip.request_number)}</td>
                                <td data-label="Purpose">${purposeHtml}</td>
                                <td data-label="Trip Date">
                                    <div>${escapeHtml(trip.trip_date)}</div>
                                    <small class="text-muted">${escapeHtml(trip.trip_time)}</small>
                                </td>
                                <td data-label="Status">
                                    <span class="badge bg-${statusClass(trip.status)}">${escapeHtml(statusLabel)}</span>
                                    ${dueBadge}
                                </td>
                                <td data-label="Assignment">${assignedHtml}</td>
                                <td class="text-end" data-label="Actions">
                                    <div class="trip-action-buttons d-inline-flex gap-1 flex-wrap justify-content-end">${editHtml} ${viewHtml} ${deleteHtml} ${cancelHtml} ${restoreHtml}</div>
                                    <div class="trip-action-icons">${editIcon} ${viewIcon} ${deleteIcon} ${cancelIcon} ${restoreIcon}</div>
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
                            responsive: {
                                details: {
                                    type: 'column',
                                    target: 0,
                                },
                            },
                            columnDefs: [
                                { orderable: false, className: 'dtr-control', targets: 0 },
                                { responsivePriority: 1, targets: 1 },
                                { responsivePriority: 2, targets: 4 },
                                { responsivePriority: 100, targets: -1 },
                            ],
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
                    if (!realtimeEnabled) {
                        return null;
                    }
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
                    if (!realtimeEnabled) {
                        startPollingFallback();
                        return;
                    }
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

                const historyTable = document.querySelector('.trip-history-table');
                if (historyTable && window.jQuery && window.jQuery.fn.dataTable && !window.jQuery.fn.dataTable.isDataTable(historyTable)) {
                    window.jQuery(historyTable).DataTable({
                        pageLength: 10,
                        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
                        order: [],
                        searching: true,
                        paging: true,
                        info: true,
                        responsive: {
                            details: {
                                type: 'column',
                                target: 0,
                            },
                        },
                        columnDefs: [
                            { orderable: false, className: 'dtr-control', targets: 0 },
                            { responsivePriority: 1, targets: 1 },
                            { responsivePriority: 2, targets: 5 },
                            { responsivePriority: 100, targets: -1 },
                        ],
                    });
                }
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
        .trip-table-wrap {
            overflow-x: visible;
        }
