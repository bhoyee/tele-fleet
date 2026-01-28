<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Edit Vehicle</h1>
            <p class="text-muted mb-0">Update vehicle details and status.</p>
        </div>
        <a href="{{ route('vehicles.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <ul class="nav nav-tabs" id="vehicleTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="vehicle-details-tab" data-bs-toggle="tab" data-bs-target="#vehicle-details" type="button" role="tab">
                        Details
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="vehicle-maintenance-tab" data-bs-toggle="tab" data-bs-target="#vehicle-maintenance" type="button" role="tab">
                        Maintenance Timeline
                    </button>
                </li>
            </ul>

            <div class="tab-content pt-4">
                <div class="tab-pane fade show active" id="vehicle-details" role="tabpanel">
                    <form method="POST" action="{{ route('vehicles.update', $vehicle) }}">
                        @method('PUT')
                        @include('vehicles._form')
                        <div class="mt-4 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
                <div class="tab-pane fade" id="vehicle-maintenance" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table align-middle datatable">
                            <thead class="table-light">
                                <tr>
                                    <th>Scheduled</th>
                                    <th>Status</th>
                                    <th>Description</th>
                                    <th>Cost</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($maintenanceTimeline as $maintenance)
                                    <tr>
                                        <td>{{ $maintenance->scheduled_for?->format('M d, Y') }}</td>
                                        <td>
                                            @php
                                                $statusClass = $maintenance->status === 'completed'
                                                    ? 'success'
                                                    : ($maintenance->status === 'in_progress'
                                                        ? 'primary'
                                                        : ($maintenance->status === 'cancelled'
                                                            ? 'secondary'
                                                            : 'warning'));
                                            @endphp
                                            <span class="badge bg-{{ $statusClass }}">
                                                {{ ucfirst(str_replace('_', ' ', $maintenance->status)) }}
                                            </span>
                                        </td>
                                        <td>{{ $maintenance->description }}</td>
                                        <td>{{ $maintenance->cost !== null ? number_format($maintenance->cost, 2) : '—' }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('maintenances.show', $maintenance) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center text-muted py-4">No maintenance records yet.</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
