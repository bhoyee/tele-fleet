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
                <table class="table align-middle datatable">
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
                        @foreach ($vehicles as $vehicle)
                            <tr>
                                <td>{{ $vehicle->registration_number }}</td>
                                <td>{{ $vehicle->make }} {{ $vehicle->model }}</td>
                                <td>{{ $vehicle->branch?->name ?? 'N/A' }}</td>
                                <td>{{ number_format($vehicle->current_mileage) }} km</td>
                                <td>
                                    <span class="badge bg-{{ $vehicle->status === 'available' ? 'success' : ($vehicle->status === 'in_use' ? 'primary' : ($vehicle->status === 'maintenance' ? 'warning' : 'secondary')) }}">
                                        {{ ucfirst(str_replace('_', ' ', $vehicle->status)) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#archiveVehicleModal"
                                            data-action="{{ route('vehicles.destroy', $vehicle) }}"
                                            data-name="{{ $vehicle->registration_number }}">
                                        Archive
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="archiveVehicleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Archive Vehicle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Archive vehicle <strong id="archiveVehicleName"></strong>? You can restore it later if needed.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="archiveVehicleForm">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Archive Vehicle</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const archiveVehicleModal = document.getElementById('archiveVehicleModal');
            if (archiveVehicleModal) {
                archiveVehicleModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const action = button.getAttribute('data-action');
                    const name = button.getAttribute('data-name');
                    document.getElementById('archiveVehicleForm').setAttribute('action', action);
                    document.getElementById('archiveVehicleName').textContent = name;
                });
            }
        </script>
    @endpush
</x-admin-layout>
