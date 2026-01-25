<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Users</h1>
            <p class="text-muted mb-0">Manage access and roles across branches.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">New User</a>
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
                                <td class="text-end">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteUserModal"
                                            data-delete-action="{{ route('admin.users.destroy', $user) }}"
                                            data-delete-name="{{ $user->name }}">
                                        Delete
                                    </button>
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
                    <h5 class="modal-title">Delete User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Are you sure you want to delete <strong id="deleteUserName"></strong>? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="deleteUserForm">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const deleteUserModal = document.getElementById('deleteUserModal');
            if (deleteUserModal) {
                document.querySelectorAll('[data-delete-action]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const action = button.getAttribute('data-delete-action');
                        const name = button.getAttribute('data-delete-name');
                        document.getElementById('deleteUserForm').setAttribute('action', action);
                        document.getElementById('deleteUserName').textContent = name;
                    });
                });
            }
        </script>
    @endpush
</x-admin-layout>
