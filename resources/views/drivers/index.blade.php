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
                <table class="table align-middle">
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
                        @forelse ($drivers as $driver)
                            <tr>
                                <td>{{ $driver->full_name }}</td>
                                <td>{{ $driver->license_number }}</td>
                                <td>{{ $driver->branch?->name ?? '—' }}</td>
                                <td>{{ $driver->phone }}</td>
                                <td>
                                    <span class="badge {{ $driver->status === 'active' ? 'bg-success' : ($driver->status === 'inactive' ? 'bg-secondary' : 'bg-warning') }}">
                                        {{ ucfirst($driver->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('drivers.edit', $driver) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="POST" action="{{ route('drivers.destroy', $driver) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Archive this driver?')">Archive</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No drivers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($drivers->hasPages())
            <div class="card-footer bg-white">
                {{ $drivers->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
