<x-admin-layout>
    <style>
        #vehicleCurrentTripsTable_wrapper,
        #vehiclePastTripsTable_wrapper {
            width: 100% !important;
            display: block !important;
        }

        #vehicleCurrentTripsTable,
        #vehiclePastTripsTable {
            width: 100% !important;
        }
    </style>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Vehicle Details</h1>
            <p class="text-muted mb-0">Review vehicle information and maintenance history.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('vehicles.index') }}" class="btn btn-outline-secondary">Back</a>
            <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-primary">Edit Vehicle</a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="text-muted small">Registration</div>
                    <div class="fw-semibold">{{ $vehicle->registration_number }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Make / Model</div>
                    <div class="fw-semibold">{{ $vehicle->make }} {{ $vehicle->model }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Year</div>
                    <div class="fw-semibold">{{ $vehicle->year ?? 'N/A' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Current Mileage</div>
                    <div class="fw-semibold">{{ number_format($vehicle->current_mileage ?? 0) }} km</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Last Maintenance Mileage</div>
                    <div class="fw-semibold">{{ number_format($vehicle->last_maintenance_mileage ?? 0) }} km</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Status</div>
                    <div class="fw-semibold">
                        @php
                            $statusClass = match ($currentStatus ?? $vehicle->status) {
                                'available' => 'success',
                                'in_use' => 'primary',
                                'maintenance' => 'warning',
                                'offline' => 'secondary',
                                default => 'light text-dark',
                            };
                            $displayStatusValue = $currentStatus ?? $vehicle->status;
                        @endphp
                        <span class="badge bg-{{ $statusClass }}">
                            {{ ucfirst(str_replace('_', ' ', $displayStatusValue)) }}
                        </span>
                        @if (! empty($statusWasCorrected))
                            <div class="text-muted small mt-1">
                                Status was corrected from {{ ucfirst(str_replace('_', ' ', $previousStatus ?? '')) }}.
                            </div>
                        @elseif (! empty($currentStatus) && $vehicle->status !== $currentStatus)
                            <div class="text-muted small mt-1">
                                Stored status: {{ ucfirst(str_replace('_', ' ', $vehicle->status)) }}
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Maintenance State</div>
                    <div class="fw-semibold">
                        @php
                            $maintenanceState = $vehicle->maintenance_state ?? 'ok';
                            $maintenanceClass = match ($maintenanceState) {
                                'overdue' => 'danger',
                                'due' => 'warning',
                                'ok' => 'success',
                                default => 'secondary',
                            };
                        @endphp
                        <span class="badge bg-{{ $maintenanceClass }}">
                            {{ ucfirst($maintenanceState) }}
                        </span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Insurance Expiry</div>
                    <div class="fw-semibold">{{ $vehicle->insurance_expiry?->format('M d, Y') ?? 'N/A' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Registration Expiry</div>
                    <div class="fw-semibold">{{ $vehicle->registration_expiry?->format('M d, Y') ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
    </div>

    @if (auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN && $analytics)
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header">Vehicle Analytics</div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="text-muted small">Utilization ({{ $analytics['range_days'] }} days)</div>
                        <div class="fw-semibold">{{ $analytics['utilization'] }}%</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Trips in Range</div>
                        <div class="fw-semibold">{{ $analytics['total_trips'] }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Assigned Days</div>
                        <div class="fw-semibold">{{ $analytics['assigned_days'] }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Fleet Utilization (Avg)</div>
                        <div class="fw-semibold">{{ $analytics['fleet_utilization'] }}%</div>
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

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>Vehicle Trips</span>
            <ul class="nav nav-tabs card-header-tabs" id="vehicleTripTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="vehicle-current-tab" data-bs-toggle="tab" data-bs-target="#vehicle-current" type="button" role="tab">
                        Current & Upcoming
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="vehicle-past-tab" data-bs-toggle="tab" data-bs-target="#vehicle-past" type="button" role="tab">
                        Past Trips
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="vehicle-current" role="tabpanel" aria-labelledby="vehicle-current-tab">
                    <table class="table align-middle datatable" id="vehicleCurrentTripsTable">
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

                <div class="tab-pane fade" id="vehicle-past" role="tabpanel" aria-labelledby="vehicle-past-tab">
                    <table class="table align-middle" id="vehiclePastTripsTable">
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
                document.querySelectorAll('#vehicleTripTabs button[data-bs-toggle="tab"]').forEach((tab) => {
                    const ensurePastTable = () => {
                        if (!window.jQuery || !window.jQuery.fn?.dataTable) {
                            return;
                        }

                        const pastTable = document.getElementById('vehiclePastTripsTable');
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
                        if (event?.target?.id !== 'vehicle-past-tab') {
                            return;
                        }
                        setTimeout(ensurePastTable, 50);
                    });
                });
            });
        </script>
    @endpush

    <div class="card shadow-sm border-0">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Maintenance Timeline</span>
            <a href="{{ route('maintenances.create') }}" class="btn btn-sm btn-outline-primary">Schedule Maintenance</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Scheduled</th>
                            <th>Status</th>
                            <th>Description</th>
                            <th>Cost</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($maintenanceTimeline as $maintenance)
                            @php
                                $statusClass = $maintenance->status === 'completed'
                                    ? 'success'
                                    : ($maintenance->status === 'in_progress'
                                        ? 'primary'
                                        : ($maintenance->status === 'cancelled'
                                            ? 'secondary'
                                            : 'warning'));
                            @endphp
                            <tr>
                                <td>{{ $maintenance->scheduled_for?->format('M d, Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $statusClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $maintenance->status)) }}
                                    </span>
                                </td>
                                <td>{{ $maintenance->description }}</td>
                                <td>{{ $maintenance->cost !== null ? number_format($maintenance->cost, 2) : 'N/A' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('maintenances.show', $maintenance) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No maintenance records yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
