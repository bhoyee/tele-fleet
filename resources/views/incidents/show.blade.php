<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Incident {{ $incident->reference }}</h1>
            <p class="text-muted mb-0">{{ $incident->title }}</p>
        </div>
        <div class="d-flex gap-2">
            @if ($incident->status === \App\Models\IncidentReport::STATUS_OPEN)
                <a href="{{ route('incidents.edit', $incident) }}" class="btn btn-outline-secondary" data-loading>Edit</a>
                <button type="button"
                        class="btn btn-outline-warning"
                        data-bs-toggle="modal"
                        data-bs-target="#cancelIncidentModal"
                        data-cancel-action="{{ route('incidents.cancel', $incident) }}"
                        data-cancel-label="{{ $incident->reference }}">
                    Cancel
                </button>
                @if (auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                    <button type="button"
                            class="btn btn-outline-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#deleteIncidentModal"
                            data-delete-action="{{ route('incidents.destroy', $incident) }}"
                            data-delete-label="{{ $incident->reference }}">
                        Delete
                    </button>
                @endif
            @endif
            <a href="{{ route('incidents.index') }}" class="btn btn-outline-secondary" data-loading>Back</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h5 class="fw-semibold mb-3">Incident Details</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small">Branch</div>
                            <div class="fw-semibold">{{ $incident->branch?->name ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Reported By</div>
                            <div class="fw-semibold">{{ $incident->reportedBy?->name ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Incident Date</div>
                            <div class="fw-semibold">{{ $incident->incident_date?->format('M d, Y') }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Incident Time</div>
                            <div class="fw-semibold">{{ $incident->incident_time ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Location</div>
                            <div class="fw-semibold">{{ $incident->location ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Severity</div>
                            <div class="fw-semibold text-capitalize">{{ $incident->severity }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Vehicle</div>
                            <div class="fw-semibold">{{ $incident->vehicle?->registration_number ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Driver</div>
                            <div class="fw-semibold">{{ $incident->driver?->full_name ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-12">
                            <div class="text-muted small">Description</div>
                            <div class="fw-semibold">{{ $incident->description }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @if (! empty($incident->attachments))
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="fw-semibold mb-3">Attachments</h5>
                        <div class="d-flex flex-column gap-2">
                            @foreach ($incident->attachments as $attachment)
                                <a class="text-decoration-none" href="{{ route('incidents.attachments.download', [$incident, basename($attachment)]) }}">
                                    {{ basename($attachment) }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="fw-semibold mb-3">Status</h5>
                    <div class="mb-3">
                        <span class="badge bg-{{ $incident->status === 'resolved' ? 'success' : ($incident->status === 'under_review' ? 'warning text-dark' : ($incident->status === 'cancelled' ? 'secondary' : 'info')) }}">
                            {{ str_replace('_', ' ', ucfirst($incident->status)) }}
                        </span>
                    </div>

                    @if (in_array(auth()->user()?->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true))
                        <form method="POST" action="{{ route('incidents.status', $incident) }}">
                            @csrf
                            @method('PATCH')

                            <div class="mb-3">
                                <label class="form-label" for="status">Update Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="open" @selected($incident->status === 'open')>Open</option>
                                    <option value="under_review" @selected($incident->status === 'under_review')>Under Review</option>
                                    <option value="resolved" @selected($incident->status === 'resolved')>Resolved</option>
                                </select>
                                @error('status') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="resolution_notes">Resolution Notes</label>
                                <textarea class="form-control" id="resolution_notes" name="resolution_notes" rows="3">{{ old('resolution_notes', $incident->resolution_notes) }}</textarea>
                                @error('resolution_notes') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>

                            <button class="btn btn-primary w-100" type="submit">Update Status</button>
                        </form>
                    @endif

                    @if ($incident->status === 'resolved')
                        <div class="mt-3">
                            <div class="text-muted small">Closed By</div>
                            <div class="fw-semibold">{{ $incident->closedBy?->name ?? 'N/A' }}</div>
                            <div class="text-muted small mt-2">Closed At</div>
                            <div class="fw-semibold">{{ $incident->closed_at?->format('M d, Y H:i') ?? 'N/A' }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

@if ($incident->status === \App\Models\IncidentReport::STATUS_OPEN)
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
    @endif

    @push('scripts')
        <script>
            document.addEventListener('click', (event) => {
                const cancelButton = event.target.closest('[data-cancel-action]');
                if (cancelButton) {
                    const action = cancelButton.getAttribute('data-cancel-action');
                    const label = cancelButton.getAttribute('data-cancel-label');
                    const form = document.getElementById('cancelIncidentForm');
                    if (form) {
                        form.setAttribute('action', action);
                    }
                    const labelEl = document.getElementById('cancelIncidentLabel');
                    if (labelEl) {
                        labelEl.textContent = label;
                    }
                }

                const deleteButton = event.target.closest('[data-delete-action]');
                if (deleteButton) {
                    const action = deleteButton.getAttribute('data-delete-action');
                    const label = deleteButton.getAttribute('data-delete-label');
                    const form = document.getElementById('deleteIncidentForm');
                    if (form) {
                        form.setAttribute('action', action);
                    }
                    const labelEl = document.getElementById('deleteIncidentLabel');
                    if (labelEl) {
                        labelEl.textContent = label;
                    }
                }
            });
        </script>
    @endpush
@endif
