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
                        <div class="col-md-6">
                            <div class="text-muted small">Last Updated By</div>
                            <div class="fw-semibold">{{ $tripRequest->updatedBy?->name ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Last Updated At</div>
                            <div class="fw-semibold">{{ $tripRequest->updated_at?->format('M d, Y H:i') ?? 'N/A' }}</div>
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

                    @if (in_array($tripRequest->status, ['approved', 'assigned'], true) && in_array(auth()->user()->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true))
                        <form method="POST" action="{{ route('trips.assign.store', $tripRequest) }}" class="mb-3">
                            @csrf
                            @method('PATCH')

                            <div class="mb-3">
                                <label class="form-label" for="assigned_vehicle_id">Vehicle</label>
                                <select class="form-select" id="assigned_vehicle_id" name="assigned_vehicle_id" @required(! $tripRequest->assigned_vehicle_id)>
                                    <option value="">Select vehicle</option>
                                    @foreach ($vehicles as $vehicle)
                                        <option value="{{ $vehicle->id }}" @selected((string) $tripRequest->assigned_vehicle_id === (string) $vehicle->id)>
                                            {{ $vehicle->registration_number }} - {{ $vehicle->make }} {{ $vehicle->model }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('assigned_vehicle_id') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="assigned_driver_id">Driver</label>
                                <select class="form-select" id="assigned_driver_id" name="assigned_driver_id" @required(! $tripRequest->assigned_driver_id)>
                                    <option value="">Select driver</option>
                                    @foreach ($drivers as $driver)
                                        <option value="{{ $driver->id }}" @selected((string) $tripRequest->assigned_driver_id === (string) $driver->id)>
                                            {{ $driver->full_name }} ({{ $driver->license_number }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('assigned_driver_id') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            @if ($tripRequest->assigned_vehicle_id || $tripRequest->assigned_driver_id)
                                <div class="mb-3">
                                    <label class="form-label" for="assignment_reason">Reason for reassignment</label>
                                    <textarea class="form-control" id="assignment_reason" name="reason" rows="3" required>{{ old('reason') }}</textarea>
                                    <div class="form-text">Required whenever you change the assigned vehicle or driver.</div>
                                    @error('reason') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            @endif

                            @if ($vehicles->isEmpty() || $drivers->isEmpty())
                                <div class="alert alert-warning">
                                    Assignment requires available vehicles and active drivers.
                                </div>
                            @endif

                            <button class="btn btn-primary w-100" type="submit">
                                {{ $tripRequest->assigned_vehicle_id || $tripRequest->assigned_driver_id ? 'Reassign Vehicle & Driver' : 'Assign Vehicle & Driver' }}
                            </button>
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

                    @if ($tripRequest->assignments?->isNotEmpty())
                        <hr class="my-4">
                        <h6 class="fw-semibold mb-3">Assignment History</h6>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 w-100">
                                <thead class="table-light">
                                    <tr>
                                        <th>When</th>
                                        <th>Changed By</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tripRequest->assignments as $assignment)
                                        <tr>
                                            <td class="text-muted small">{{ $assignment->created_at?->format('M d, Y H:i') ?? '—' }}</td>
                                            <td>{{ $assignment->changedBy?->name ?? '—' }}</td>
                                            <td>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-primary"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#assignmentDetailsModal"
                                                        data-assignment-id="{{ $assignment->id }}"
                                                        data-assignment-details="{{ json_encode([
                                                            'when' => $assignment->created_at?->format('M d, Y H:i'),
                                                            'changed_by' => $assignment->changedBy?->name,
                                                            'reason' => $assignment->reason,
                                                            'from_vehicle' => $assignment->fromVehicle?->registration_number ?? 'N/A',
                                                            'from_driver' => $assignment->fromDriver?->full_name ?? 'N/A',
                                                            'to_vehicle' => $assignment->toVehicle?->registration_number ?? 'N/A',
                                                            'to_driver' => $assignment->toDriver?->full_name ?? 'N/A',
                                                        ]) }}">
                                                    View
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Assignment Details Modal -->
    <div class="modal fade" id="assignmentDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assignment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-muted small">When</div>
                            <div class="fw-semibold" id="modal-when">—</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Changed By</div>
                            <div class="fw-semibold" id="modal-changed-by">—</div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">Reason</div>
                            <div class="fw-semibold" id="modal-reason">—</div>
                        </div>
                        
                        <div class="col-12 mt-3">
                            <h6 class="fw-semibold mb-2">From</h6>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="text-muted small">Vehicle</div>
                                    <div class="fw-semibold" id="modal-from-vehicle">—</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Driver</div>
                                    <div class="fw-semibold" id="modal-from-driver">—</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12 mt-3">
                            <h6 class="fw-semibold mb-2">To</h6>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="text-muted small">Vehicle</div>
                                    <div class="fw-semibold" id="modal-to-vehicle">—</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Driver</div>
                                    <div class="fw-semibold" id="modal-to-driver">—</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
    @endif

    @push('scripts')
        <script>
            // Delete Trip Modal Handler
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

            // Assignment Details Modal Handler
            const assignmentDetailsModal = document.getElementById('assignmentDetailsModal');
            if (assignmentDetailsModal) {
                assignmentDetailsModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const details = JSON.parse(button.getAttribute('data-assignment-details'));
                    
                    // Populate modal with data
                    document.getElementById('modal-when').textContent = details.when || '—';
                    document.getElementById('modal-changed-by').textContent = details.changed_by || '—';
                    document.getElementById('modal-reason').textContent = details.reason || '—';
                    document.getElementById('modal-from-vehicle').textContent = details.from_vehicle || '—';
                    document.getElementById('modal-from-driver').textContent = details.from_driver || '—';
                    document.getElementById('modal-to-vehicle').textContent = details.to_vehicle || '—';
                    document.getElementById('modal-to-driver').textContent = details.to_driver || '—';
                });
            }
        </script>
    @endpush
</x-admin-layout>
