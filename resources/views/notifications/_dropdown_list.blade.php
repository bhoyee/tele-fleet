@forelse ($latestNotifications as $notification)
    @php
        $notificationData = is_array($notification->data ?? null) ? $notification->data : [];
        $notificationType = class_basename($notification->type ?? '');
        $chatTypes = ['ChatRequestNotification', 'ChatClosedNotification', 'ChatMessageNotification'];
        $isChat = in_array($notificationType, $chatTypes, true);
        $isSystemHealth = $notificationType === 'SystemHealthAlert';
        $tripLabel = $notificationData['request_number']
            ?? (! empty($notificationData['trip_request_id'])
                ? ('Trip #'.$notificationData['trip_request_id'])
                : null);
        $tripTitle = $tripLabel ? "{$tripLabel} Update" : 'Trip Update';
        $ticketLabel = ! empty($notificationData['ticket_id'])
            ? 'TCK-' . str_pad($notificationData['ticket_id'], 5, '0', STR_PAD_LEFT)
            : null;
        $ticketTitle = $ticketLabel ? "{$ticketLabel} Update" : 'Ticket Update';
        $title = match ($notificationType) {
            'ChatRequestNotification' => 'Chat Request',
            'ChatClosedNotification' => 'Chat Closed',
            'ChatMessageNotification' => 'Chat Message',
            'SystemHealthAlert' => $notificationData['title'] ?? 'System Health',
            'SupportTicketCreated' => $ticketTitle,
            'SupportTicketReply' => $ticketTitle,
            default => $tripTitle,
        };

        $message = match ($notificationType) {
            'ChatRequestNotification' => 'New chat request received.',
            'ChatClosedNotification' => 'Chat has been closed.',
            'ChatMessageNotification' => 'New chat message received.',
            'SystemHealthAlert' => $notificationData['message'] ?? 'System health alert.',
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
            default => ! empty($notificationData['status'])
                ? ('Status: ' . ucfirst($notificationData['status']))
                : ($notificationData['purpose'] ?? 'Trip update received.'),
        };

        $viewUrl = ! empty($notificationData['trip_request_id'])
            ? route('trips.show', $notificationData['trip_request_id'])
            : (! empty($notificationData['ticket_id'])
                ? route('helpdesk.show', $notificationData['ticket_id'])
                : ($isChat && ! empty($notificationData['conversation_id'])
                    ? null
                    : null));
    @endphp
    <div class="px-3 py-2 border-bottom">
        <div class="d-flex justify-content-between">
            <div class="fw-semibold small">
                {{ $title }}
                @if (! $notification->read_at)
                    <span class="badge bg-primary ms-1">New</span>
                @endif
            </div>
            <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
        </div>
        <div class="text-muted small">
            {{ $message }}
            @if ($ticketLabel)
                <div>{{ $ticketLabel }}</div>
            @endif
        </div>
        <div class="d-flex gap-2 mt-2">
            @if (! $notification->read_at)
                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-outline-primary btn-sm" type="submit">Mark read</button>
                </form>
            @endif
            @if ($viewUrl)
                <a class="btn btn-light btn-sm" href="{{ $viewUrl }}">View</a>
            @elseif ($isChat && ! empty($notificationData['conversation_id']) && config('app.realtime_enabled'))
                <button class="btn btn-light btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#chatWidget">Open chat</button>
            @endif
        </div>
    </div>
@empty
    <div class="px-3 py-4 text-center text-muted">No notifications yet.</div>
@endforelse
