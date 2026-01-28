<x-admin-layout>
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
                            $statusClass = match ($vehicle->status) {
                                'available' => 'success',
                                'in_use' => 'primary',
                                'maintenance' => 'warning',
                                'offline' => 'secondary',
                                default => 'light text-dark',
                            };
                        @endphp
                        <span class="badge bg-{{ $statusClass }}">
                            {{ ucfirst(str_replace('_', ' ', $vehicle->status)) }}
                        </span>
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
