<x-admin-layout>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h1 class="h3 mb-1">User Details</h1>
            <p class="text-muted mb-0">Profile overview and activity history.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Back</a>
            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">Edit User</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header">Profile</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small">Name</div>
                            <div class="fw-semibold">{{ $user->name }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Email</div>
                            <div class="fw-semibold">{{ $user->email }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Role</div>
                            <div class="fw-semibold text-capitalize">{{ str_replace('_', ' ', $user->role) }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Status</div>
                            <span class="badge {{ $user->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                {{ ucfirst($user->status) }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Branch</div>
                            <div class="fw-semibold">{{ $user->branch?->name ?? 'Head Office' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Phone</div>
                            <div class="fw-semibold">{{ $user->phone ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Last Login</div>
                            <div class="fw-semibold">{{ $user->last_login_at?->format('M d, Y g:i A') ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Created</div>
                            <div class="fw-semibold">{{ $user->created_at?->format('M d, Y g:i A') ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header">Quick Summary</div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="user-avatar" style="width: 56px; height: 56px;">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="fw-semibold">{{ $user->name }}</div>
                            <div class="text-muted small">{{ $user->email }}</div>
                        </div>
                    </div>
                    <div class="text-muted small mb-2">Recent activity summary</div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-light text-dark">Logs: {{ $activities->count() }}</span>
                        <span class="badge bg-light text-dark">Role: {{ str_replace('_', ' ', $user->role) }}</span>
                        <span class="badge bg-light text-dark">Status: {{ ucfirst($user->status) }}</span>
                    </div>
                    <p class="text-muted small mt-3 mb-0">Activity logs capture actions triggered by this user across the system.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header">User Activity Log</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle datatable">
                    <thead class="table-light">
                        <tr>
                            <th>Action</th>
                            <th>Subject</th>
                            <th>Details</th>
                            <th>IP</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activities as $activity)
                            <tr>
                                <td class="text-capitalize">{{ str_replace('_', ' ', $activity->action) }}</td>
                                <td>
                                    {{ $activity->model_type ? class_basename($activity->model_type) : 'N/A' }}
                                    @if ($activity->model_id)
                                        <span class="text-muted">
                                            @if (class_basename($activity->model_type) === 'TripRequest')
                                                {{ $tripRequestMap[$activity->model_id] ?? ('Trip #'.$activity->model_id) }}
                                            @else
                                                #{{ $activity->model_id }}
                                            @endif
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $details = [];
                                        if (! empty($activity->new_values)) {
                                            $details[] = 'Updated';
                                        }
                                        if (! empty($activity->old_values) && empty($activity->new_values)) {
                                            $details[] = 'Removed';
                                        }
                                    @endphp
                                    {{ $details ? implode(', ', $details) : '—' }}
                                </td>
                                <td>{{ $activity->ip_address ?? 'N/A' }}</td>
                                <td>{{ $activity->created_at?->format('M d, Y g:i A') ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-muted">—</td>
                                <td class="text-muted">—</td>
                                <td class="text-muted">No activity logs yet.</td>
                                <td class="text-muted">—</td>
                                <td class="text-muted">—</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header">Login History</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle datatable">
                    <thead class="table-light">
                        <tr>
                            <th>Login Time</th>
                            <th>IP Address</th>
                            <th>Guard</th>
                            <th>User Agent</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($loginHistory as $login)
                            <tr>
                                <td>{{ $login->logged_in_at?->format('M d, Y g:i A') ?? 'N/A' }}</td>
                                <td>{{ $login->ip_address ?? 'N/A' }}</td>
                                <td>{{ $login->guard ?? 'web' }}</td>
                                <td class="text-truncate" style="max-width: 240px;">{{ $login->user_agent ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-muted">—</td>
                                <td class="text-muted">—</td>
                                <td class="text-muted">No login history yet.</td>
                                <td class="text-muted">—</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
