<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Maintenance Details</h1>
            <p class="text-muted mb-0">Vehicle service record overview.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('maintenances.edit', $maintenance) }}" class="btn btn-outline-secondary">Edit</a>
            <a href="{{ route('maintenances.index') }}" class="btn btn-outline-primary">Back</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="fw-semibold mb-3">Schedule</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="text-muted small">Status</div>
                            <div class="fw-semibold">{{ ucfirst(str_replace('_', ' ', $maintenance->status)) }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Scheduled For</div>
                            <div class="fw-semibold">{{ $maintenance->scheduled_for?->format('M d, Y') }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Started At</div>
                            <div class="fw-semibold">{{ $maintenance->started_at?->format('M d, Y g:i A') ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Completed At</div>
                            <div class="fw-semibold">{{ $maintenance->completed_at?->format('M d, Y g:i A') ?? '—' }}</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h5 class="fw-semibold mb-3">Work Summary</h5>
                    <div class="mb-3">
                        <div class="text-muted small">Description</div>
                        <div class="fw-semibold">{{ $maintenance->description }}</div>
                    </div>
                    <div>
                        <div class="text-muted small">Notes</div>
                        <div class="fw-semibold">{{ $maintenance->notes ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h5 class="fw-semibold mb-3">Vehicle</h5>
                    <div class="text-muted small">Registration</div>
                    <div class="fw-semibold">{{ $maintenance->vehicle?->registration_number ?? 'N/A' }}</div>
                    <div class="text-muted small mt-3">Model</div>
                    <div class="fw-semibold">{{ $maintenance->vehicle?->make }} {{ $maintenance->vehicle?->model }}</div>
                    <div class="text-muted small mt-3">Branch</div>
                    <div class="fw-semibold">{{ $maintenance->branch?->name ?? 'N/A' }}</div>
                </div>
            </div>
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="fw-semibold mb-3">Costs & Metrics</h5>
                    <div class="text-muted small">Cost</div>
                    <div class="fw-semibold">{{ $maintenance->cost !== null ? number_format($maintenance->cost, 2) : '—' }}</div>
                    <div class="text-muted small mt-3">Odometer</div>
                    <div class="fw-semibold">{{ $maintenance->odometer !== null ? number_format($maintenance->odometer) . ' km' : '—' }}</div>
                    <div class="text-muted small mt-3">Logged By</div>
                    <div class="fw-semibold">{{ $maintenance->createdBy?->name ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
