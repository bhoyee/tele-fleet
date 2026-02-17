@php
    $currentUser = $currentUser ?? auth()->user();
@endphp

@forelse ($ticket->messages as $message)
    @php
        $isSelf = $message->user_id === $currentUser?->id;
        $messageClass = $isSelf ? 'bg-primary text-white' : 'bg-light';
        $senderLabel = $message->user?->name ?? null;
        if (! $senderLabel && $message->external_email) {
            $senderLabel = trim(($message->external_name ?: 'Developer') . ' <' . $message->external_email . '>');
        }
        $senderLabel = $senderLabel ?: 'User';
    @endphp
    <div class="mb-3" data-message-id="{{ $message->id }}">
        <div class="small text-muted mb-1">
            {{ $senderLabel }} - {{ $message->created_at?->format('M d, Y H:i') }}
        </div>
        <div class="p-3 rounded-3 {{ $messageClass }}">
            {!! $message->message !!}
        </div>

        @if ($message->attachments->count())
            <div class="mt-2">
                @foreach ($message->attachments as $attachment)
                    <a class="btn btn-sm btn-outline-secondary me-2 mb-2" href="{{ route('helpdesk.attachments.download', [$ticket, $attachment]) }}">
                        <i class="bi bi-paperclip"></i> {{ $attachment->original_name }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@empty
    <div class="text-muted">No replies yet.</div>
@endforelse

