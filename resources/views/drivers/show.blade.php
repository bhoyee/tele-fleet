<x-admin-layout>
    <style>
        #driverCurrentTripsTable_wrapper,
        #driverPastTripsTable_wrapper {
            width: 100% !important;
            display: block !important;
        }

        #driverCurrentTripsTable,
        #driverPastTripsTable {
            width: 100% !important;
        }
    </style>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Driver Details</h1>
            <p class="text-muted mb-0">Review driver profile and status.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('drivers.index') }}" class="btn btn-outline-secondary">Back</a>
            <a href="{{ route('drivers.edit', $driver) }}" class="btn btn-primary">Edit Driver</a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="text-muted small">Full Name</div>
                    <div class="fw-semibold">{{ $driver->full_name }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">CS Number</div>
                    <div class="fw-semibold">{{ $driver->license_number }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Phone</div>
                    <div class="fw-semibold">{{ $driver->phone ?? 'N/A' }}</div>
                </div>
                @if (!empty($driver->email))
                    <div class="col-md-4">
                        <div class="text-muted small">Email</div>
                        <div class="fw-semibold">{{ $driver->email }}</div>
                    </div>
                @endif
                <div class="col-md-8">
                    <div class="text-muted small">Address</div>
                    <div class="fw-semibold">{{ $driver->address ? \App\Support\TextNormalizer::titleText($driver->address) : 'N/A' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">License Number</div>
                    <div class="fw-semibold">{{ $driver->license_type }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">License Expiry</div>
                    <div class="fw-semibold">{{ $driver->license_expiry?->format('M d, Y') ?? 'N/A' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Status</div>
                    <div class="fw-semibold">
                        @php
                            $statusClass = \App\Models\Driver::statusBadgeClass($driver->status);
                            $statusLabel = \App\Models\Driver::statusLabel($driver->status);
                        @endphp
                        <span class="badge bg-{{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                    </div>
                </div>
                @if (!empty($driver->note))
                    <div class="col-12">
                        <div class="alert alert-warning border-0 mb-0" style="border-left: 4px solid #d97706;">
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi bi-sticky text-warning"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">Status Note</div>
                                    <div class="small text-muted mb-2">
                                        This note explains why the driver is marked as {{ \App\Models\Driver::statusLabel($driver->status) }}.
                                    </div>
                                    <div class="fw-semibold">{!! nl2br(e($driver->note)) !!}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif (in_array($driver->status, ['inactive', 'suspended'], true))
                    <div class="col-12">
                        <div class="alert alert-warning border-0 mb-0" style="border-left: 4px solid #d97706;">
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi bi-exclamation-circle text-warning"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">Status Note Missing</div>
                                    <div class="small text-muted mb-2">
                                        This driver is marked as {{ \App\Models\Driver::statusLabel($driver->status) }}, but no note was saved.
                                    </div>
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('drivers.edit', $driver) }}" data-loading>Edit driver to add note</a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="col-md-4">
                    <div class="text-muted small">Created By</div>
                    <div class="fw-semibold">{{ $driver->createdBy?->name ?? 'N/A' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Created At</div>
                    <div class="fw-semibold">{{ $driver->created_at?->format('M d, Y g:i A') ?? 'N/A' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Last Updated By</div>
                    <div class="fw-semibold">{{ $driver->updatedBy?->name ?? 'N/A' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Last Updated At</div>
                    <div class="fw-semibold">{{ $driver->updated_at?->format('M d, Y g:i A') ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
    </div>

    @if (auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN && $analytics)
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-header">Driver Analytics</div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="text-muted small">Trips ({{ $analytics['range_days'] }} days)</div>
                        <div class="fw-semibold">{{ $analytics['total_trips'] }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Completed Trips</div>
                        <div class="fw-semibold">{{ $analytics['completed_trips'] }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Assigned (Active)</div>
                        <div class="fw-semibold">{{ $analytics['assigned_trips'] }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Completion Rate</div>
                        <div class="fw-semibold">{{ $analytics['completion_rate'] }}%</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Last Trip</div>
                        <div class="fw-semibold">{{ $analytics['last_trip_date']?->format('M d, Y') ?? 'N/A' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Next Scheduled Trip</div>
                        <div class="fw-semibold">{{ $analytics['next_trip_date']?->format('M d, Y') ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>Driver Trips</span>
            <ul class="nav nav-tabs card-header-tabs" id="driverTripTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="driver-current-tab" data-bs-toggle="tab" data-bs-target="#driver-current" type="button" role="tab">
                        Current & Upcoming
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="driver-past-tab" data-bs-toggle="tab" data-bs-target="#driver-past" type="button" role="tab">
                        Past Trips
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="driver-current" role="tabpanel" aria-labelledby="driver-current-tab">
                    <table class="table align-middle datatable" id="driverCurrentTripsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Request #</th>
                                <th>Trip Date</th>
                                <th>Destination</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($activeTrips as $trip)
                                <tr>
                                    <td>{{ $trip->request_number }}</td>
                                    <td>
                                        <div>{{ $trip->trip_date?->format('M d, Y') }}</div>
                                        <small class="text-muted">{{ $trip->trip_time ? \Illuminate\Support\Carbon::parse($trip->trip_time)->format('g:i A') : 'N/A' }}</small>
                                    </td>
                                    <td>{{ $trip->destination }}</td>
                                    <td>
                                        @php
                                            $dueStatus = $trip->dueStatus();
                                            $statusLabel = $dueStatus ? ucfirst($dueStatus) : ucfirst($trip->status);
                                            $statusClass = $dueStatus === 'overdue'
                                                ? 'danger'
                                                : ($dueStatus === 'due'
                                                    ? 'warning'
                                                    : ($trip->status === 'assigned'
                                                        ? 'primary'
                                                        : 'success'));
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('trips.show', $trip) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                            @empty
                                {{-- Let DataTables render its built-in empty state. --}}
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="driver-past" role="tabpanel" aria-labelledby="driver-past-tab">
                    <table class="table align-middle" id="driverPastTripsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Request #</th>
                                <th>Trip Date</th>
                                <th>Destination</th>
                                <th>Final Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pastTrips as $trip)
                                @php
                                    $statusValue = (string) ($trip->status ?? '');
                                    $statusClass = match ($statusValue) {
                                        'completed' => 'success',
                                        'rejected' => 'danger',
                                        'cancelled' => 'secondary',
                                        default => 'secondary',
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $trip->request_number }}</td>
                                    <td>
                                        <div>{{ $trip->trip_date?->format('M d, Y') }}</div>
                                        <small class="text-muted">{{ $trip->trip_time ? \Illuminate\Support\Carbon::parse($trip->trip_time)->format('g:i A') : 'N/A' }}</small>
                                    </td>
                                    <td>{{ $trip->destination }}</td>
                                    <td>
                                        <span class="badge bg-{{ $statusClass }}">
                                            {{ ucfirst(str_replace('_', ' ', $statusValue ?: 'N/A')) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('trips.show', $trip) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                            @empty
                                {{-- Let DataTables render its built-in empty state. --}}
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('#driverTripTabs button[data-bs-toggle="tab"]').forEach((tab) => {
                    const ensurePastTable = () => {
                        if (!window.jQuery || !window.jQuery.fn?.dataTable) {
                            return;
                        }

                        const pastTable = document.getElementById('driverPastTripsTable');
                        if (!pastTable) {
                            return;
                        }

                        let dt;
                        if (window.jQuery.fn.dataTable.isDataTable(pastTable)) {
                            dt = window.jQuery(pastTable).DataTable();
                        } else {
                            dt = window.jQuery(pastTable).DataTable({
                                pageLength: 10,
                                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
                                order: [],
                                searching: true,
                                paging: true,
                                info: true,
                                responsive: true,
                                autoWidth: false,
                            });
                        }

                        const fixLayout = () => {
                            try {
                                dt.columns.adjust();
                                dt.responsive?.recalc?.();
                                dt.draw(false);
                            } catch (error) {
                                // ignore
                            }
                            window.jQuery(pastTable).css('width', '100%');
                            window.jQuery(pastTable).closest('.dataTables_wrapper').css('width', '100%');
                        };

                        fixLayout();
                        setTimeout(fixLayout, 350);
                    };

                    tab.addEventListener('shown.bs.tab', (event) => {
                        if (event?.target?.id !== 'driver-past-tab') {
                            return;
                        }
                        setTimeout(ensurePastTable, 50);
                    });
                });
            });
        </script>
    @endpush
</x-admin-layout>
