<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Trip Requests</h1>
            <p class="text-muted mb-0">Track requests, approvals, and assignments.</p>
        </div>
        @if (in_array(auth()->user()->role, [\App\Models\User::ROLE_BRANCH_ADMIN, \App\Models\User::ROLE_BRANCH_HEAD, \App\Models\User::ROLE_SUPER_ADMIN], true))
            <a href="{{ route('trips.create') }}" class="btn btn-primary">New Trip</a>
        @endif
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Request #</th>
                            <th>Branch</th>
                            <th>Purpose</th>
                            <th>Trip Date</th>
                            <th>Status</th>
                            <th>Assignment</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($trips as $trip)
                            <tr>
                                <td>{{ $trip->request_number }}</td>
                                <td>{{ $trip->branch?->name ?? '—' }}</td>
                                <td>{{ $trip->purpose }}</td>
                                <td>{{ $trip->trip_date?->format('M d, Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $trip->status === 'approved' ? 'success' : ($trip->status === 'rejected' ? 'danger' : ($trip->status === 'assigned' ? 'primary' : ($trip->status === 'completed' ? 'dark' : 'secondary'))) }}">
                                        {{ ucfirst($trip->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($trip->assigned_vehicle_id && $trip->assigned_driver_id)
                                        <span class="badge bg-primary">Assigned</span>
                                    @else
                                        <span class="badge bg-secondary">Unassigned</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('trips.show', $trip) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No trip requests found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($trips->hasPages())
            <div class="card-footer bg-white">
                {{ $trips->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
