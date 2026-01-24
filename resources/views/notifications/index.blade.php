<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Notifications</h1>
            <p class="text-muted mb-0">Review recent updates and actions.</p>
        </div>
        <form method="POST" action="{{ route('notifications.read_all') }}">
            @csrf
            @method('PATCH')
            <button class="btn btn-outline-secondary" type="submit">Mark all read</button>
        </form>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            @forelse ($notifications as $notification)
                <div class="d-flex justify-content-between align-items-start border-bottom py-3">
                    <div>
                        <div class="fw-semibold">
                            {{ $notification->data['request_number'] ?? 'Trip Update' }}
                            @if (! $notification->read_at)
                                <span class="badge bg-primary ms-2">New</span>
                            @endif
                        </div>
                        <div class="text-muted small">
                            {{ $notification->data['purpose'] ?? 'Trip status updated' }}
                            @if (! empty($notification->data['status']))
                                · Status: {{ ucfirst($notification->data['status']) }}
                            @endif
                        </div>
                        <div class="text-muted small">Received {{ $notification->created_at->diffForHumans() }}</div>
                    </div>
                    <div class="text-end">
                        @if (! $notification->read_at)
                            <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-outline-primary" type="submit">Mark read</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4">No notifications yet.</div>
            @endforelse
        </div>
        @if ($notifications->hasPages())
            <div class="card-footer bg-white">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
