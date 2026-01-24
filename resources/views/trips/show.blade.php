<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Trip {{ $tripRequest->request_number }}</h1>
            <p class="text-muted mb-0">Status: {{ ucfirst($tripRequest->status) }}</p>
        </div>
        <a href="{{ route('trips.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="fw-semibold mb-3">Request Details</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small">Branch</div>
                            <div class="fw-semibold">{{ $tripRequest->branch?->name ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Requested By</div>
                            <div class="fw-semibold">{{ $tripRequest->requestedBy?->name ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Purpose</div>
                            <div class="fw-semibold">{{ $tripRequest->purpose }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Destination</div>
                            <div class="fw-semibold">{{ $tripRequest->destination }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Trip Date</div>
                            <div class="fw-semibold">{{ $tripRequest->trip_date?->format('M d, Y') }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Passengers</div>
                            <div class="fw-semibold">{{ $tripRequest->number_of_passengers }}</div>
                        </div>
                        <div class="col-md-12">
                            <div class="text-muted small">Notes</div>
                            <div class="fw-semibold">{{ $tripRequest->additional_notes ?: '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($tripRequest->log)
                <div class="card shadow-sm border-0 mt-4">
                    <div class="card-body">
                        <h5 class="fw-semibold mb-3">Logbook Entry</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-muted small">Mileage</div>
                                <div class="fw-semibold">{{ $tripRequest->log->start_mileage }} → {{ $tripRequest->log->end_mileage }} km</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Distance</div>
                                <div class="fw-semibold">{{ $tripRequest->log->distance_traveled }} km</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Driver</div>
                                <div class="fw-semibold">{{ $tripRequest->log->driver_name }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Log Date</div>
                                <div class="fw-semibold">{{ $tripRequest->log->log_date?->format('M d, Y') }}</div>
                            </div>
                            <div class="col-md-12">
                                <div class="text-muted small">Remarks</div>
                                <div class="fw-semibold">{{ $tripRequest->log->remarks ?: '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="fw-semibold mb-3">Workflow Actions</h5>

                    @if ($tripRequest->status === 'pending' && in_array(auth()->user()->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true))
                        <form method="POST" action="{{ route('trips.approve', $tripRequest) }}" class="mb-3">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-success w-100" type="submit">Approve Request</button>
                        </form>

                        <form method="POST" action="{{ route('trips.reject', $tripRequest) }}">
                            @csrf
                            @method('PATCH')
                            <div class="mb-2">
                                <label class="form-label" for="rejection_reason">Rejection Reason</label>
                                <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3"></textarea>
                            </div>
                            <button class="btn btn-outline-danger w-100" type="submit">Reject Request</button>
                        </form>
                    @endif

                    @if ($tripRequest->status === 'approved' && in_array(auth()->user()->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true))
                        <a href="{{ route('trips.assign', $tripRequest) }}" class="btn btn-primary w-100 mb-2">Assign Vehicle & Driver</a>
                    @endif

                    @if ($tripRequest->status === 'assigned' && in_array(auth()->user()->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true))
                        <a href="{{ route('trips.logbook', $tripRequest) }}" class="btn btn-dark w-100">Enter Logbook</a>
                    @endif

                    @if ($tripRequest->status === 'rejected')
                        <div class="alert alert-warning mt-3">
                            <strong>Rejected:</strong> {{ $tripRequest->rejection_reason }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm border-0 mt-4">
                <div class="card-body">
                    <h5 class="fw-semibold mb-3">Assignment</h5>
                    <div class="text-muted small mb-1">Vehicle</div>
                    <div class="fw-semibold">{{ $tripRequest->assignedVehicle?->registration_number ?? '—' }}</div>
                    <div class="text-muted small mb-1 mt-3">Driver</div>
                    <div class="fw-semibold">{{ $tripRequest->assignedDriver?->full_name ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
