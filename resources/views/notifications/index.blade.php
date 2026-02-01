<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Notifications</h1>
            <p class="text-muted mb-0">Review recent updates and actions.</p>
        </div>
        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('notifications.cleanup') }}">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-secondary" type="submit">Remove duplicates</button>
            </form>
            <form method="POST" action="{{ route('notifications.read_all') }}">
                @csrf
                @method('PATCH')
                <button class="btn btn-outline-secondary" type="submit">Mark all read</button>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            @forelse ($notifications as $notification)
                @php
                    $notificationType = class_basename($notification->type ?? '');
                    $isChat = in_array($notificationType, ['ChatMessageNotification', 'ChatRequestNotification'], true);
                    $notificationData = is_array($notification->data ?? null) ? $notification->data : [];
                    $title = $isChat
                        ? 'Chat Update'
                        : ($notificationData['request_number'] ?? 'Trip Update');
                    $ticketLabel = ! empty($notificationData['ticket_id'])
                        ? 'TCK-' . str_pad($notificationData['ticket_id'], 5, '0', STR_PAD_LEFT)
                        : null;
                    $message = match ($notificationType) {
                        'TripRequestCreated' => 'New trip request submitted.',
                        'TripRequestApproved' => 'Trip request approved.',
                        'TripRequestAssigned' => 'Trip assigned to driver/vehicle.',
                        'TripRequestRejected' => 'Trip request rejected.',
                        'TripRequestCancelled' => 'Trip request cancelled.',
                        'TripAssignmentPending' => 'Trip awaiting assignment.',
                        'TripAssignmentConflict' => 'Trip assignment needs attention.',
                        'TripCompletionReminderNotification' => 'Trip completion reminder sent.',
                        'OverdueTripNotification' => 'Trip marked overdue.',
                        'SupportTicketCreated' => 'New support ticket submitted.',
                        'SupportTicketReply' => 'New reply on support ticket.',
                        default => ($notificationData['status'] ?? null)
                            ? ('Status: ' . ucfirst($notificationData['status']))
                            : ($notificationData['purpose'] ?? 'Trip update received.'),
                    };
                @endphp
                <div class="d-flex justify-content-between align-items-start border-bottom py-3">
                    <div>
                        <div class="fw-semibold">
                            {{ $title }}
                            @if (! $notification->read_at)
                                <span class="badge bg-primary ms-2">New</span>
                            @endif
                        </div>
                        <div class="text-muted small">
                            {{ $message }}
                            @if ($ticketLabel)
                                <div>{{ $ticketLabel }}</div>
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
