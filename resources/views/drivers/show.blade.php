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
                    <div class="text-muted small">Status</div>
                    <div class="fw-semibold">
                        @php
                            $statusClass = match ($driver->status) {
                                'active' => 'success',
                                'inactive' => 'secondary',
                                'suspended' => 'danger',
                                default => 'light text-dark',
                            };
                        @endphp
                        <span class="badge bg-{{ $statusClass }}">
                            {{ ucfirst($driver->status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
