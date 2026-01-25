<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Logbooks</h1>
            <p class="text-muted mb-0">Track trips pending logbook entry and completed logs.</p>
        </div>
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
                            <th>Branch</th>
                            <th>Trip Date</th>
                            <th>Vehicle</th>
                            <th>Driver</th>
                            <th>Status</th>
                            <th>Logbook</th>
                            @if ($isSuperAdmin)
                                <th>Entered By</th>
                                <th>Last Edited By</th>
                            @endif
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trips as $trip)
                            <tr>
                                <td>{{ $trip->request_number }}</td>
                                <td>{{ $trip->branch?->name ?? 'N/A' }}</td>
                                <td>{{ $trip->trip_date?->format('M d, Y') }}</td>
                                <td>{{ $trip->assignedVehicle?->registration_number ?? 'N/A' }}</td>
                                <td>{{ $trip->assignedDriver?->full_name ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-{{ $trip->status === 'completed' ? 'dark' : 'primary' }}">
                                        {{ ucfirst($trip->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($trip->log)
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                                @if ($isSuperAdmin)
                                    <td>{{ $trip->log?->enteredBy?->name ?? 'N/A' }}</td>
                                    <td>{{ $trip->log?->editedBy?->name ?? 'N/A' }}</td>
                                @endif
                                <td class="text-end">
                                    @if ($trip->log)
                                        <a href="{{ route('trips.logbook.edit', $trip) }}" class="btn btn-sm btn-outline-primary" data-loading>Edit Logbook</a>
                                        @if ($isSuperAdmin)
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteLogbookModal"
                                                    data-delete-action="{{ route('trips.logbook.destroy', $trip) }}"
                                                    data-delete-label="{{ $trip->request_number }}">
                                                Delete Logbook
                                            </button>
                                        @endif
                                    @else
                                        <a href="{{ route('trips.logbook', $trip) }}" class="btn btn-sm btn-dark" data-loading>Enter Logbook</a>
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
        <div class="modal fade" id="deleteLogbookModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Logbook</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">Delete logbook for <strong id="deleteLogbookLabel"></strong>? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST" id="deleteLogbookForm">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete Logbook</button>
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
                        const form = document.getElementById('deleteLogbookForm');
                        if (form) {
                            form.setAttribute('action', action);
                        }
                        const labelEl = document.getElementById('deleteLogbookLabel');
                        if (labelEl) {
                            labelEl.textContent = label;
                        }
                    });
                });
            </script>
        @endpush
    @endif
</x-admin-layout>
