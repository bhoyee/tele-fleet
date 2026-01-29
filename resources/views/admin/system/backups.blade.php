<x-admin-layout>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h1 class="h3 mb-1">Database Backups</h1>
            <p class="text-muted mb-0">Create and download database backups.</p>
        </div>
        <form method="POST" action="{{ route('system.backups.run') }}">
            @csrf
            <button class="btn btn-primary" type="submit">Run Backup</button>
        </form>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-lg-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="stat-label">Last Backup</div>
                    <div class="stat-value">{{ $lastBackup ? \Illuminate\Support\Carbon::createFromTimestamp($lastBackup['last_modified'])->format('M d, Y H:i') : 'N/A' }}</div>
                    <div class="text-muted small">{{ $lastBackup['name'] ?? 'No backups yet' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="stat-label">Total Backups</div>
                    <div class="stat-value">{{ $files->count() }}</div>
                    <div class="text-muted small">Stored in local backups</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="fw-semibold">Available Backups</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle datatable">
                    <thead class="table-light">
                        <tr>
                            <th>File</th>
                            <th>Size</th>
                            <th>Created</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($files as $file)
                            <tr>
                                <td>{{ $file['name'] }}</td>
                                <td>{{ number_format($file['size'] / 1024, 2) }} KB</td>
                                <td>{{ \Illuminate\Support\Carbon::createFromTimestamp($file['last_modified'])->format('M d, Y H:i') }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('system.backups.download', $file['name']) }}" data-loading>Download</a>
                                    <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteBackupModal" data-delete-action="{{ route('system.backups.delete', $file['name']) }}" data-delete-label="{{ $file['name'] }}">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-muted">No backups created yet.</td>
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

    <div class="modal fade" id="deleteBackupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Backup</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Delete backup <strong id="deleteBackupLabel"></strong>? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="deleteBackupForm">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete Backup</button>
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
                    const form = document.getElementById('deleteBackupForm');
                    if (form) {
                        form.setAttribute('action', action);
                    }
                    const labelEl = document.getElementById('deleteBackupLabel');
                    if (labelEl) {
                        labelEl.textContent = label;
                    }
                });
            });
        </script>
    @endpush
</x-admin-layout>
