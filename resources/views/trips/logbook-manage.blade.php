<x-admin-layout>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h1 class="h3 mb-1">Manage Logbooks</h1>
            <p class="text-muted mb-0">Review and manage entered logbooks.</p>
        </div>
        @php
            $isSuperAdmin = auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN;
        @endphp
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary @if (! $showArchived) active @endif" href="{{ route('logbooks.manage') }}">Active</a>
            @if ($isSuperAdmin)
                <a class="btn btn-outline-secondary @if ($showArchived) active @endif" href="{{ route('logbooks.manage', ['archived' => 1]) }}">Deleted</a>
            @endif
        </div>
    </div>

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
                            <th>Log Date</th>
                            <th>Status</th>
                            <th>Entered By</th>
                            <th>Last Edited By</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            @php
                                $trip = $log->tripRequest;
                                $status = $trip?->status ?? 'N/A';
                                $statusClass = match ($status) {
                                    'completed' => 'dark',
                                    'assigned' => 'primary',
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    'pending' => 'secondary',
                                    default => 'secondary',
                                };
                            @endphp
                            <tr>
                                <td>{{ $trip?->request_number ?? 'N/A' }}</td>
                                <td>{{ $trip?->branch?->name ?? 'N/A' }}</td>
                                <td>{{ $trip?->trip_date?->format('M d, Y') ?? 'N/A' }}</td>
                                <td>{{ $trip?->assignedVehicle?->registration_number ?? 'N/A' }}</td>
                                <td>{{ $trip?->assignedDriver?->full_name ?? 'N/A' }}</td>
                                <td>{{ $log->log_date?->format('M d, Y') ?? 'N/A' }}</td>
                                <td><span class="badge bg-{{ $statusClass }}">{{ ucfirst($status) }}</span></td>
                                <td>{{ $log->enteredBy?->name ?? 'N/A' }}</td>
                                <td>{{ $log->editedBy?->name ?? 'N/A' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('logbooks.show', $log->id) }}@if($showArchived){{ '?archived=1' }}@endif" class="btn btn-sm btn-outline-primary" data-loading>View</a>
                                    @if (! $showArchived && $trip)
                                        <a href="{{ route('trips.logbook.edit', $trip) }}" class="btn btn-sm btn-outline-secondary" data-loading>Edit</a>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#archiveLogbookModal"
                                                data-delete-action="{{ route('logbooks.archive', $log->id) }}"
                                                data-delete-label="{{ $trip?->request_number ?? 'Logbook' }}">
                                            Delete
                                        </button>
                                    @elseif ($isSuperAdmin)
                                        <button type="button"
                                                class="btn btn-sm btn-outline-success"
                                                data-bs-toggle="modal"
                                                data-bs-target="#restoreLogbookModal"
                                                data-restore-action="{{ route('logbooks.restore', $log->id) }}"
                                                data-restore-label="{{ $trip?->request_number ?? 'Logbook' }}">
                                            Restore
                                        </button>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#forceDeleteLogbookModal"
                                                data-force-action="{{ route('logbooks.force', $log->id) }}"
                                                data-force-label="{{ $trip?->request_number ?? 'Logbook' }}">
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

    <div class="modal fade" id="archiveLogbookModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Logbook</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Delete logbook for <strong id="archiveLogbookLabel"></strong>? This will remove it from active lists.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="archiveLogbookForm">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if ($isSuperAdmin)
        <div class="modal fade" id="restoreLogbookModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Restore Logbook</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">Restore logbook for <strong id="restoreLogbookLabel"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST" id="restoreLogbookForm">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-success">Restore</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="forceDeleteLogbookModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Logbook Permanently</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">Permanently delete logbook for <strong id="forceLogbookLabel"></strong>? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST" id="forceDeleteLogbookForm">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            const setModalAction = (selector, action, label, labelSelector) => {
                const form = document.getElementById(selector);
                if (form) {
                    form.setAttribute('action', action);
                }
                const labelEl = document.getElementById(labelSelector);
                if (labelEl) {
                    labelEl.textContent = label;
                }
            };

            document.querySelectorAll('[data-delete-action]').forEach((button) => {
                button.addEventListener('click', () => {
                    setModalAction('archiveLogbookForm', button.getAttribute('data-delete-action'), button.getAttribute('data-delete-label'), 'archiveLogbookLabel');
                });
            });

            document.querySelectorAll('[data-restore-action]').forEach((button) => {
                button.addEventListener('click', () => {
                    setModalAction('restoreLogbookForm', button.getAttribute('data-restore-action'), button.getAttribute('data-restore-label'), 'restoreLogbookLabel');
                });
            });

            document.querySelectorAll('[data-force-action]').forEach((button) => {
                button.addEventListener('click', () => {
                    setModalAction('forceDeleteLogbookForm', button.getAttribute('data-force-action'), button.getAttribute('data-force-label'), 'forceLogbookLabel');
                });
            });
        </script>
    @endpush
</x-admin-layout>
