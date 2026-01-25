<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Drivers</h1>
            <p class="text-muted mb-0">Manage driver records and compliance.</p>
        </div>
        <a href="{{ route('drivers.create') }}" class="btn btn-primary">New Driver</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle datatable">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>License</th>
                            <th>Branch</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($drivers as $driver)
                            <tr>
                                <td>{{ $driver->full_name }}</td>
                                <td>{{ $driver->license_number }}</td>
                                <td>{{ $driver->branch?->name ?? 'N/A' }}</td>
                                <td>{{ $driver->phone }}</td>
                                <td>
                                    <span class="badge {{ $driver->status === 'active' ? 'bg-success' : ($driver->status === 'inactive' ? 'bg-secondary' : 'bg-warning') }}">
                                        {{ ucfirst($driver->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('drivers.edit', $driver) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#archiveDriverModal"
                                            data-action="{{ route('drivers.destroy', $driver) }}"
                                            data-name="{{ $driver->full_name }}">
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

    <div class="modal fade" id="archiveDriverModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Archive Driver</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Archive driver <strong id="archiveDriverName"></strong>? You can restore later if needed.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="archiveDriverForm">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Archive Driver</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const archiveDriverModal = document.getElementById('archiveDriverModal');
            if (archiveDriverModal) {
                archiveDriverModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const action = button.getAttribute('data-action');
                    const name = button.getAttribute('data-name');
                    document.getElementById('archiveDriverForm').setAttribute('action', action);
                    document.getElementById('archiveDriverName').textContent = name;
                });
            }
        </script>
    @endpush
</x-admin-layout>
