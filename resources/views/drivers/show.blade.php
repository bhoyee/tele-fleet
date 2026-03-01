<x-admin-layout>
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
                    <div class="text-muted small">License Number</div>
                    <div class="fw-semibold">{{ $driver->license_number }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Phone</div>
                    <div class="fw-semibold">{{ $driver->phone ?? 'N/A' }}</div>
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
                        <div class="text-muted small">Note</div>
                        <div class="fw-semibold">{!! nl2br(e($driver->note)) !!}</div>
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
        <div class="card-header">Current & Upcoming Trips</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
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
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No active or upcoming trips for this driver.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
