<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Incident Reports</h1>
            <p class="text-muted mb-0">Track safety events and follow ups.</p>
        </div>
        <div class="d-flex gap-2">
            @if (auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                <a class="btn btn-outline-primary" href="{{ route('incidents.export.csv') }}" data-download>Export CSV</a>
                <a class="btn btn-outline-dark" href="{{ route('incidents.export.pdf') }}" data-download>Export PDF</a>
            @endif
            @if (auth()->user()?->role === \App\Models\User::ROLE_BRANCH_ADMIN)
                <a href="{{ route('incidents.create') }}" class="btn btn-primary" data-loading>New Incident</a>
            @endif
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle datatable">
                    <thead class="table-light">
                        <tr>
                            <th>Reference</th>
                            <th>Severity</th>
                            <th>Status</th>
                            <th>Incident Date</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($incidents as $incident)
                            <tr>
                                <td>{{ $incident->reference }}</td>
                                <td class="text-capitalize">{{ $incident->severity }}</td>
                                <td>
                                    <span class="badge bg-{{ $incident->status === 'resolved' ? 'success' : ($incident->status === 'under_review' ? 'warning text-dark' : 'secondary') }}">
                                        {{ str_replace('_', ' ', ucfirst($incident->status)) }}
                                    </span>
                                </td>
                                <td>{{ $incident->incident_date?->format('M d, Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('incidents.show', $incident) }}" class="btn btn-sm btn-outline-primary" data-loading>View</a>
                                    @if (auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteIncidentModal"
                                                data-delete-action="{{ route('incidents.destroy', $incident) }}"
                                                data-delete-label="{{ $incident->reference }}">
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

    @if (auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
        <div class="modal fade" id="deleteIncidentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Incident</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">Delete incident <strong id="deleteIncidentLabel"></strong>? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST" id="deleteIncidentForm">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete Incident</button>
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
                        const form = document.getElementById('deleteIncidentForm');
                        if (form) {
                            form.setAttribute('action', action);
                        }
                        const labelEl = document.getElementById('deleteIncidentLabel');
                        if (labelEl) {
                            labelEl.textContent = label;
                        }
                    });
                });
            </script>
        @endpush
    @endif
</x-admin-layout>
