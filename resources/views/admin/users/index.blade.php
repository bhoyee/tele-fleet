<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Users</h1>
            <p class="text-muted mb-0">Manage access and roles across branches.</p>
        </div>
        <div class="d-flex gap-2">
            @if (!($showArchived ?? false))
                <a href="{{ route('admin.users.index', ['archived' => 1]) }}" class="btn btn-outline-secondary">Show Archived</a>
            @else
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Back to Active</a>
            @endif
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">New User</a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            @if (session('generated_password'))
                <div class="alert alert-warning">
                    Temporary password for the new user: <strong>{{ session('generated_password') }}</strong>
                </div>
            @endif
            <div class="table-responsive">
                <table class="table align-middle datatable">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Branch</th>
                            <th>Status</th>
                            @if ($showArchived ?? false)
                                <th>Deleted</th>
                            @endif
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td class="text-capitalize">{{ str_replace('_', ' ', $user->role) }}</td>
                                <td>{{ $user->branch?->name ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge {{ $user->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ ucfirst($user->status) }}
                                    </span>
                                </td>
                                @if ($showArchived ?? false)
                                    <td class="text-muted small">{{ $user->deleted_at?->format('M d, Y H:i') ?? '—' }}</td>
                                @endif
                                <td class="text-end">
                                    @if (!($showArchived ?? false))
                                        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                        @if ($user->id !== auth()->id())
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteUserModal"
                                                    data-delete-action="{{ route('admin.users.destroy', $user) }}"
                                                    data-delete-name="{{ $user->name }}">
                                                Archive
                                            </button>
                                        @endif
                                    @else
                                        <form method="POST" action="{{ route('admin.users.restore', $user) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-success">Restore</button>
                                        </form>
                                        @if ($user->id !== auth()->id())
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#forceDeleteUserModal"
                                                    data-force-action="{{ route('admin.users.force', $user) }}"
                                                    data-force-name="{{ $user->name }}">
                                                Delete Permanently
                                            </button>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Archive User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Archive <strong id="deleteUserName"></strong>? You can restore archived users later.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="deleteUserForm">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Archive User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="forceDeleteUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete User Permanently</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Permanently delete <strong id="forceDeleteUserName"></strong>? This cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="forceDeleteUserForm">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete Permanently</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Use delegated handlers so DataTables redraws/pagination don't break button wiring.
            document.addEventListener('click', (event) => {
                const archiveButton = event.target.closest('[data-delete-action]');
                if (archiveButton) {
                    const action = archiveButton.getAttribute('data-delete-action');
                    const name = archiveButton.getAttribute('data-delete-name');

                    const form = document.getElementById('deleteUserForm');
                    if (form && action) {
                        form.setAttribute('action', action);
                    }

                    const nameEl = document.getElementById('deleteUserName');
                    if (nameEl && name) {
                        nameEl.textContent = name;
                    }
                    return;
                }

                const forceButton = event.target.closest('[data-force-action]');
                if (forceButton) {
                    const action = forceButton.getAttribute('data-force-action');
                    const name = forceButton.getAttribute('data-force-name');

                    const form = document.getElementById('forceDeleteUserForm');
                    if (form && action) {
                        form.setAttribute('action', action);
                    }

                    const nameEl = document.getElementById('forceDeleteUserName');
                    if (nameEl && name) {
                        nameEl.textContent = name;
                    }
                }
            });
        </script>
    @endpush
</x-admin-layout>
