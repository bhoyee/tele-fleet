<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Incident {{ $incident->reference }}</h1>
            <p class="text-muted mb-0">{{ $incident->title }}</p>
        </div>
        <a href="{{ route('incidents.index') }}" class="btn btn-outline-secondary" data-loading>Back</a>
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
                        <span class="badge bg-{{ $incident->status === 'resolved' ? 'success' : ($incident->status === 'under_review' ? 'warning text-dark' : 'secondary') }}">
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
