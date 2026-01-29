<x-admin-layout>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h1 class="h3 mb-1">Conversation #{{ $conversation->id }}</h1>
            <p class="text-muted mb-0">Read-only conversation details.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.chats.index') }}" class="btn btn-outline-secondary">Back</a>
            @if ($conversation->status !== 'closed')
                <form method="POST" action="{{ route('admin.chats.close', $conversation) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-outline-warning">Close</button>
                </form>
            @endif
            <button type="button"
                    class="btn btn-outline-danger"
                    data-bs-toggle="modal"
                    data-bs-target="#deleteChatModal"
                    data-delete-action="{{ route('admin.chats.destroy', $conversation) }}"
                    data-delete-label="#{{ $conversation->id }}">
                Delete
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="text-muted small">Type</div>
                    <div class="fw-semibold text-capitalize">{{ $conversation->type }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Status</div>
                    @php
                        $statusClass = $conversation->status === 'closed'
                            ? 'danger'
                            : ($conversation->status === 'active' ? 'success' : ($conversation->status === 'pending' ? 'warning' : 'secondary'));
                    @endphp
                    <div class="fw-semibold">
                        <span class="badge bg-{{ $statusClass }}">{{ ucfirst($conversation->status) }}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Issue Type</div>
                    <div class="fw-semibold">{{ $conversation->issue_type ?? 'N/A' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Created By</div>
                    <div class="fw-semibold">{{ $conversation->creator?->name ?? 'N/A' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Assigned To</div>
                    <div class="fw-semibold">{{ $conversation->assignee?->name ?? 'N/A' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Closed At</div>
                    <div class="fw-semibold">{{ $conversation->closed_at?->format('M d, Y g:i A') ?? 'N/A' }}</div>
                </div>
                <div class="col-md-12">
                    <div class="text-muted small">Participants</div>
                    <div class="fw-semibold">
                        {{ $conversation->participants->pluck('user.name')->filter()->implode(', ') ?: 'N/A' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header">Messages</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Message</th>
                            <th>Sent</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($conversation->messages as $message)
                            @php
                                $rowClass = ($message->user_id === $conversation->created_by_user_id)
                                    ? 'chat-row-user-a'
                                    : 'chat-row-user-b';
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td>{{ $message->user?->name ?? 'N/A' }}</td>
                                <td>{{ $message->message }}</td>
                                <td>{{ $message->created_at?->format('M d, Y g:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-muted">—</td>
                                <td class="text-muted">No messages.</td>
                                <td class="text-muted">—</td>
                            </tr>
                        @endforelse
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

    @push('styles')
        <style>
            .chat-row-user-a td {
                background: rgba(13, 110, 253, 0.12);
            }
            .chat-row-user-b td {
                background: rgba(25, 135, 84, 0.12);
            }
        </style>
    @endpush
</x-admin-layout>
