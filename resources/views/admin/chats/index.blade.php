<x-admin-layout>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h1 class="h3 mb-1">Chat Management</h1>
            <p class="text-muted mb-0">Review and manage all conversations.</p>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-4">
                    <label class="form-label" for="type">Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">All</option>
                        <option value="support" @selected($type === 'support')>Support</option>
                        <option value="direct" @selected($type === 'direct')>Direct</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All</option>
                        <option value="pending" @selected($status === 'pending')>Pending</option>
                        <option value="active" @selected($status === 'active')>Active</option>
                        <option value="declined" @selected($status === 'declined')>Declined</option>
                        <option value="closed" @selected($status === 'closed')>Closed</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-primary w-100" type="submit">Apply</button>
                    <a href="{{ route('admin.chats.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle datatable">
                    <thead class="table-light">
                        <tr>
                            <th>Conversation</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Participants</th>
                            <th>Last Message</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($conversations as $conversation)
                            @php
                                $lastMessage = $conversation->messages->first();
                                $participantNames = $conversation->participants
                                    ->pluck('user.name')
                                    ->filter()
                                    ->implode(', ');
                            @endphp
                            <tr>
                                <td>#{{ $conversation->id }}</td>
                                <td class="text-capitalize">{{ $conversation->type }}</td>
                                <td>
                                    @php
                                        $statusClass = $conversation->status === 'active'
                                            ? 'success'
                                            : ($conversation->status === 'pending'
                                                ? 'warning'
                                                : ($conversation->status === 'closed'
                                                    ? 'danger'
                                                    : 'secondary'));
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">
                                        {{ ucfirst($conversation->status) }}
                                    </span>
                                </td>
                                <td>{{ $participantNames ?: 'N/A' }}</td>
                                <td>
                                    <div class="fw-semibold text-truncate" style="max-width: 220px;">{{ $lastMessage?->message ?? 'No messages' }}</div>
                                    <small class="text-muted">{{ $lastMessage?->created_at?->format('M d, Y g:i A') ?? 'N/A' }}</small>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.chats.show', $conversation) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    @if ($conversation->status !== 'closed')
                                        <form method="POST" action="{{ route('admin.chats.close', $conversation) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-warning">Close</button>
                                        </form>
                                    @endif
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteChatModal"
                                            data-delete-action="{{ route('admin.chats.destroy', $conversation) }}"
                                            data-delete-label="#{{ $conversation->id }}">
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

    <div class="modal fade" id="deleteChatModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Conversation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Delete conversation <strong id="deleteChatLabel"></strong>? This cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="deleteChatForm">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const deleteChatModal = document.getElementById('deleteChatModal');
            if (deleteChatModal) {
                deleteChatModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const action = button.getAttribute('data-delete-action');
                    const label = button.getAttribute('data-delete-label');
                    document.getElementById('deleteChatForm').setAttribute('action', action);
                    document.getElementById('deleteChatLabel').textContent = label;
                });
            }
        </script>
    @endpush
</x-admin-layout>
