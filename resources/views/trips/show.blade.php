<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Trip {{ $tripRequest->request_number }}</h1>
            <p class="text-muted mb-0">Status: {{ ucfirst($tripRequest->status) }}</p>
        </div>
        <a href="{{ route('trips.index') }}" class="btn btn-outline-secondary" data-loading>Back</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="fw-semibold mb-3">Request Details</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small">Branch</div>
                            <div class="fw-semibold">{{ $tripRequest->branch?->name ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Requested By</div>
                            <div class="fw-semibold">{{ $tripRequest->requestedBy?->name ?? 'N/A' }}</div>
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
                            <div class="text-muted small">Trip Time</div>
                            @php
                                $tripTime = $tripRequest->trip_time;
                                if ($tripTime) {
                                    try {
                                        $tripTime = \Illuminate\Support\Carbon::parse($tripTime)->format('g:i A');
                                    } catch (\Exception $e) {
                                        $tripTime = \Illuminate\Support\Carbon::parse($tripRequest->trip_time)->format('g:i A');
                                    }
                                }
                            @endphp
                            <div class="fw-semibold">{{ $tripTime ?: 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Passengers</div>
                            <div class="fw-semibold">{{ $tripRequest->number_of_passengers }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Estimated Trip Days</div>
                            <div class="fw-semibold">
                                @if ($tripRequest->estimated_distance_km)
                                    {{ (int) $tripRequest->estimated_distance_km }} day{{ (int) $tripRequest->estimated_distance_km === 1 ? '' : 's' }}
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="text-muted small">Notes</div>
                            <div class="fw-semibold">{{ $tripRequest->additional_notes ?: 'N/A' }}</div>
                        </div>
                        @if (in_array(auth()->user()?->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_BRANCH_HEAD], true))
                            <div class="col-md-6">
                                <div class="text-muted small">Last Updated By</div>
                                <div class="fw-semibold">{{ $tripRequest->updatedBy?->name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Last Updated At</div>
                                <div class="fw-semibold">{{ $tripRequest->updated_at?->format('M d, Y H:i') ?? 'N/A' }}</div>
                            </div>
                        @endif
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
                                <div class="fw-semibold">{{ $tripRequest->log->start_mileage }} to {{ $tripRequest->log->end_mileage }} km</div>
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
                                <div class="fw-semibold">{{ $tripRequest->log->remarks ?: 'N/A' }}</div>
                            </div>
                            @if (auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                                <div class="col-md-6">
                                    <div class="text-muted small">Entered By</div>
                                    <div class="fw-semibold">{{ $tripRequest->log->enteredBy?->name ?? 'N/A' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small">Last Edited By</div>
                                    <div class="fw-semibold">{{ $tripRequest->log->editedBy?->name ?? 'N/A' }}</div>
                                </div>
                            @endif
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
                        <form method="POST" action="{{ route('trips.assign.store', $tripRequest) }}" class="mb-3">
                            @csrf
                            @method('PATCH')

                            <div class="mb-3">
                                <label class="form-label" for="assigned_vehicle_id">Vehicle</label>
                                <select class="form-select" id="assigned_vehicle_id" name="assigned_vehicle_id" required>
                                    <option value="">Select vehicle</option>
                                    @foreach ($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}">
                                            {{ $vehicle->registration_number }} - {{ $vehicle->make }} {{ $vehicle->model }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('assigned_vehicle_id') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="assigned_driver_id">Driver</label>
                                <select class="form-select" id="assigned_driver_id" name="assigned_driver_id" required>
                                    <option value="">Select driver</option>
                                    @foreach ($drivers as $driver)
                                        <option value="{{ $driver->id }}">{{ $driver->full_name }} ({{ $driver->license_number }})</option>
                                    @endforeach
                                </select>
                                @error('assigned_driver_id') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            @if ($vehicles->isEmpty() || $drivers->isEmpty())
                                <div class="alert alert-warning">
                                    Assignment requires available vehicles and active drivers.
                                </div>
                            @endif

                            <button class="btn btn-primary w-100" type="submit">Assign Vehicle & Driver</button>
                        </form>
                    @endif

                    @if ($tripRequest->status === 'assigned' && in_array(auth()->user()->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true))
                        <a href="{{ route('trips.logbook', $tripRequest) }}" class="btn btn-dark w-100" data-loading>Enter Logbook</a>
                    @endif

                    @if ($tripRequest->status === 'completed' && in_array(auth()->user()->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true))
                        <a href="{{ route('trips.logbook.edit', $tripRequest) }}" class="btn btn-outline-dark w-100" data-loading>Edit Logbook</a>
                    @endif

                    @php
                        $statusStyles = [
                            'approved' => 'bg-info text-dark',
                            'assigned' => 'bg-primary',
                            'completed' => 'bg-success',
                            'cancelled' => 'bg-secondary',
                            'rejected' => 'bg-danger',
                        ];
                    @endphp
                    @if ($tripRequest->status === 'pending')
                        <div class="alert alert-info border mt-3">
                            <div class="text-muted small mb-1">Current Status</div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-warning text-dark">Pending</span>
                                <span class="small text-muted">Awaiting approval</span>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-light border mt-3">
                            <div class="text-muted small mb-1">Current Status</div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge {{ $statusStyles[$tripRequest->status] ?? 'bg-light text-dark' }}">
                                    {{ ucfirst($tripRequest->status) }}
                                </span>
                                <span class="small text-muted">
                                    Updated {{ $tripRequest->updated_at?->diffForHumans() ?? 'recently' }}
                                </span>
                            </div>
                        </div>
                    @endif

                    @if ($tripRequest->status === 'rejected')
                        <div class="alert alert-warning mt-3">
                            <strong>Rejected:</strong> {{ $tripRequest->rejection_reason }}
                        </div>
                    @endif

                    @if (auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                        <button type="button"
                                class="btn btn-outline-danger w-100 mt-3"
                                data-bs-toggle="modal"
                                data-bs-target="#deleteTripModal"
                                data-delete-action="{{ route('trips.destroy', $tripRequest) }}"
                                data-delete-label="{{ $tripRequest->request_number }}">
                            Delete Trip
                        </button>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm border-0 mt-4">
                <div class="card-body">
                    <h5 class="fw-semibold mb-3">Assignment</h5>
                    @if ($tripRequest->requires_reassignment)
                        <div class="alert alert-warning d-flex align-items-start">
                            <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
                            <div>
                                <div class="fw-semibold">Assignment at risk</div>
                                <div class="small text-muted">{{ $tripRequest->assignment_conflict_reason ?? 'Vehicle entered maintenance.' }}</div>
                            </div>
                        </div>
                    @endif
                    <div class="text-muted small mb-1">Vehicle</div>
                    <div class="fw-semibold">{{ $tripRequest->assignedVehicle?->registration_number ?? 'N/A' }}</div>
                    <div class="text-muted small mb-1 mt-3">Driver</div>
                    <div class="fw-semibold">{{ $tripRequest->assignedDriver?->full_name ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
    </div>

    @if (auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
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
                document.querySelectorAll('[data-delete-action]').forEach((button) => {
                    button.addEventListener('click', () => {
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
                });
            </script>
        @endpush
    @endif
</x-admin-layout>
