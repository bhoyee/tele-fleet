<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Vehicles</h1>
            <p class="text-muted mb-0">Track fleet assets and current status.</p>
        </div>
        <a href="{{ route('vehicles.create') }}" class="btn btn-primary">New Vehicle</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Registration</th>
                            <th>Make/Model</th>
                            <th>Branch</th>
                            <th>Mileage</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vehicles as $vehicle)
                            <tr>
                                <td>{{ $vehicle->registration_number }}</td>
                                <td>{{ $vehicle->make }} {{ $vehicle->model }}</td>
                                <td>{{ $vehicle->branch?->name ?? '—' }}</td>
                                <td>{{ number_format($vehicle->current_mileage) }} km</td>
                                <td>
                                    <span class="badge bg-{{ $vehicle->status === 'available' ? 'success' : ($vehicle->status === 'in_use' ? 'primary' : ($vehicle->status === 'maintenance' ? 'warning' : 'secondary')) }}">
                                        {{ ucfirst(str_replace('_', ' ', $vehicle->status)) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="POST" action="{{ route('vehicles.destroy', $vehicle) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Archive this vehicle?')">Archive</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No vehicles found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($vehicles->hasPages())
            <div class="card-footer bg-white">
                {{ $vehicles->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
