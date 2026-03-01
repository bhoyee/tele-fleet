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

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card stat-card h-100 tele-user-filter" role="button" tabindex="0" data-user-filter="all" data-tele-tooltip title="Show all users">
                <div class="card-body">
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value">{{ $userStats['total'] ?? 0 }}</div>
                    <div class="row g-2 mt-2">
                        @php
                            $roleCounts = (array) ($userStats['roles'] ?? []);
                            $roleBadges = [
                                \App\Models\User::ROLE_SUPER_ADMIN => ['label' => 'Super Admin', 'class' => 'primary'],
                                \App\Models\User::ROLE_FLEET_MANAGER => ['label' => 'Fleet Manager', 'class' => 'info text-dark'],
                                \App\Models\User::ROLE_BRANCH_HEAD => ['label' => 'Branch Head', 'class' => 'warning text-dark'],
                                \App\Models\User::ROLE_BRANCH_ADMIN => ['label' => 'Branch Admin', 'class' => 'secondary'],
                            ];
                        @endphp
                        @foreach ($roleBadges as $roleKey => $meta)
                            <div class="col-6">
                                <button type="button"
                                        class="p-2 rounded-3 border bg-white d-flex justify-content-between align-items-center w-100 tele-user-filter tele-user-filter-sub"
                                        style="border-color: rgba(5, 108, 163, 0.12);"
                                        data-user-filter="role"
                                        data-user-role="{{ $roleKey }}"
                                        data-tele-tooltip
                                        title="Filter by {{ $meta['label'] }}">
                                    <span class="small text-muted">{{ $meta['label'] }}</span>
                                    <span class="badge bg-{{ $meta['class'] }}">{{ $roleCounts[$roleKey] ?? 0 }}</span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card stat-card h-100 tele-user-filter" role="button" tabindex="0" data-user-filter="status" data-user-status="active" data-tele-tooltip title="Filter active users">
                <div class="card-body">
                    <div class="stat-label">Active Users</div>
                    <div class="stat-value">{{ $userStats['active'] ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card stat-card h-100 tele-user-filter" role="button" tabindex="0" data-user-filter="status" data-user-status="inactive" data-tele-tooltip title="Filter inactive users">
                <div class="card-body">
                    <div class="stat-label">Inactive Users</div>
                    <div class="stat-value">{{ $userStats['inactive'] ?? 0 }}</div>
                </div>
            </div>
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
                                <td>{{ \App\Support\TextNormalizer::personName($user->name) }}</td>
                                <td>{{ $user->email }}</td>
                                <td class="text-capitalize">{{ str_replace('_', ' ', $user->role) }}</td>
                                <td>{{ $user->branch?->name ? \App\Support\TextNormalizer::titleText($user->branch->name) : 'N/A' }}</td>
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
                                        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-primary" data-tele-tooltip title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary" data-tele-tooltip title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @if ($user->id !== auth()->id())
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteUserModal"
                                                    data-delete-action="{{ route('admin.users.destroy', $user) }}"
                                                    data-delete-name="{{ \App\Support\TextNormalizer::personName($user->name) }}"
                                                    data-tele-tooltip
                                                    title="Archive">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endif
                                    @else
                                        <form method="POST" action="{{ route('admin.users.restore', $user) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-success" data-tele-tooltip title="Restore">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        </form>
                                        @if ($user->id !== auth()->id())
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#forceDeleteUserModal"
                                                    data-force-action="{{ route('admin.users.force', $user) }}"
                                                    data-force-name="{{ $user->name }}"
                                                    data-tele-tooltip
                                                    title="Delete permanently">
                                                <i class="bi bi-x-octagon"></i>
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
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const table = document.querySelector('table.datatable');
                if (!table || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.dataTable) {
                    return;
                }

                const dt = window.jQuery(table).DataTable();

                const findColumnIndex = (label) => {
                    const headers = dt.columns().header().toArray();
                    const match = headers.findIndex((th) => (th?.textContent ?? '').trim().toLowerCase() === label.toLowerCase());
                    return match >= 0 ? match : null;
                };

                const roleCol = findColumnIndex('Role');
                const statusCol = findColumnIndex('Status');

                const clearFilters = () => {
                    dt.search('');
                    dt.columns().search('');
                };

                const applyRoleFilter = (roleKey) => {
                    if (roleCol === null) {
                        return;
                    }

                    const roleLabel = String(roleKey || '').replaceAll('_', ' ').trim();
                    clearFilters();
                    dt.column(roleCol).search('^' + roleLabel + '$', true, false);
                };

                const applyStatusFilter = (status) => {
                    if (statusCol === null) {
                        return;
                    }

                    const statusLabel = status === 'active' ? 'Active' : 'Inactive';
                    clearFilters();
                    // Status cells contain badge markup; the extracted text can include whitespace/newlines.
                    dt.column(statusCol).search('^\\s*' + statusLabel + '\\s*$', true, false);
                };

                const scrollToTable = () => {
                    table.scrollIntoView({ behavior: 'smooth', block: 'start' });
                };

                const handleFilterClick = (node) => {
                    const filter = node.getAttribute('data-user-filter');
                    if (!filter) {
                        return;
                    }

                    if (filter === 'all') {
                        clearFilters();
                        dt.draw();
                        scrollToTable();
                        return;
                    }

                    if (filter === 'role') {
                        const roleKey = node.getAttribute('data-user-role');
                        applyRoleFilter(roleKey);
                        dt.draw();
                        scrollToTable();
                        return;
                    }

                    if (filter === 'status') {
                        const status = node.getAttribute('data-user-status');
                        applyStatusFilter(status);
                        dt.draw();
                        scrollToTable();
                    }
                };

                document.addEventListener('click', (event) => {
                    const target = event.target.closest('[data-user-filter]');
                    if (!target) {
                        return;
                    }
                    handleFilterClick(target);
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter' && event.key !== ' ') {
                        return;
                    }
                    const target = event.target.closest('[data-user-filter]');
                    if (!target) {
                        return;
                    }
                    event.preventDefault();
                    handleFilterClick(target);
                });
            });
        </script>
    @endpush
</x-admin-layout>
