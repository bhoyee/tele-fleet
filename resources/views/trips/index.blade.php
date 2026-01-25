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

    @php
        $isSuperAdmin = auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN;
    @endphp

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle datatable">
                    <thead class="table-light">
                        <tr>
                            <th>Request #</th>
                            <th>Purpose</th>
                            <th>Trip Date</th>
                            <th>Status</th>
                            <th>Assignment</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trips as $trip)
                            <tr>
                                <td>{{ $trip->request_number }}</td>
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
                                    @if (in_array(auth()->user()->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true))
                                        <a href="{{ route('trips.edit', $trip) }}" class="btn btn-sm btn-outline-secondary" data-loading>Edit</a>
                                    @endif
                                    <a href="{{ route('trips.show', $trip) }}" class="btn btn-sm btn-outline-primary" data-loading>View</a>
                                    @if ($isSuperAdmin)
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteTripModal"
                                                data-delete-action="{{ route('trips.destroy', $trip) }}"
                                                data-delete-label="{{ $trip->request_number }}">
                                            Delete
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($isSuperAdmin)
        <div class="modal fade" id="deleteTripModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Trip</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">Delete trip <strong id="deleteTripLabel"></strong>? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST" id="deleteTripForm">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete Trip</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                document.querySelectorAll('[data-delete-action]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const action = button.getAttribute('data-delete-action');
                        const label = button.getAttribute('data-delete-label');
                        const form = document.getElementById('deleteTripForm');
                        if (form) {
                            form.setAttribute('action', action);
                        }
                        const labelEl = document.getElementById('deleteTripLabel');
                        if (labelEl) {
                            labelEl.textContent = label;
                        }
                    });
                });
            </script>
        @endpush
    @endif
</x-admin-layout>
