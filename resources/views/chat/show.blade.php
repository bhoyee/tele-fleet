<x-admin-layout>
    @php
        $user = auth()->user();
        $other = $conversation->participants->firstWhere('user_id', '!=', $user->id)?->user;
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $other?->name ?? 'Support' }}</h1>
            <p class="text-muted mb-0">Conversation {{ $conversation->status }}</p>
        </div>
        <div class="d-flex gap-2">
            @if (in_array($user->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true) && $conversation->status !== \App\Models\ChatConversation::STATUS_CLOSED)
                <form method="POST" action="{{ route('chat.close', $conversation) }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-outline-danger" type="submit">Close Chat</button>
                </form>
            @endif
            <a href="{{ route('chat.index') }}" class="btn btn-outline-secondary" data-loading>Back</a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body" style="max-height: 60vh; overflow-y: auto;" id="chatMessages">
            @foreach ($conversation->messages as $message)
                <div class="d-flex mb-3 {{ $message->user_id === $user->id ? 'justify-content-end' : 'justify-content-start' }}">
                    <div class="p-3 rounded-3 {{ $message->user_id === $user->id ? 'bg-primary text-white' : 'bg-light' }}" style="max-width: 70%;">
                        <div class="small fw-semibold mb-1">{{ $message->user?->name ?? 'User' }}</div>
                        <div>{{ $message->message }}</div>
                        <div class="small opacity-75 mt-1">{{ $message->created_at?->format('M d, H:i') }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @php
        $participant = $conversation->participants->firstWhere('user_id', $user->id);
    @endphp

    @if ($conversation->status === \App\Models\ChatConversation::STATUS_PENDING)
        @if ($participant && ! $participant->accepted_at)
            <div class="d-flex gap-2 mb-3">
                <form method="POST" action="{{ route('chat.accept', $conversation) }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-success" type="submit">Accept Chat</button>
                </form>
                <form method="POST" action="{{ route('chat.decline', $conversation) }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-outline-danger" type="submit">Decline</button>
                </form>
            </div>
        @else
            <div class="alert alert-warning">Waiting for the other user to accept this chat.</div>
        @endif
    @endif

    @if ($conversation->status === \App\Models\ChatConversation::STATUS_ACTIVE)
        <div id="chatFormError"></div>
        <form method="POST" action="{{ route('chat.messages.store', $conversation) }}" class="d-flex gap-2" id="chatForm">
            @csrf
            <input class="form-control" name="message" id="chatMessageInput" placeholder="Type your message..." required>
            <button class="btn btn-primary" type="submit">Send</button>
        </form>
    @elseif ($conversation->status === \App\Models\ChatConversation::STATUS_CLOSED)
        <div class="alert alert-info">This conversation is closed.</div>
    @endif

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
        <script>
            if (!window.ChatEcho || typeof window.ChatEcho.private !== 'function') {
                const EchoConstructor = window.Echo;
                if (typeof EchoConstructor === 'function') {
                    window.Pusher = Pusher;
                    window.ChatEcho = new EchoConstructor({
                        broadcaster: 'pusher',
                        cluster: 'mt1',
                        key: "{{ config('broadcasting.connections.reverb.key') }}",
                        wsHost: "{{ config('broadcasting.connections.reverb.options.host') }}",
                        wsPort: {{ config('broadcasting.connections.reverb.options.port') }},
                        wssPort: {{ config('broadcasting.connections.reverb.options.port') }},
                        forceTLS: "{{ config('broadcasting.connections.reverb.options.scheme') }}" === 'https',
                        enabledTransports: ['ws', 'wss'],
                        disableStats: true,
                        authEndpoint: '/broadcasting/auth',
                        auth: {
                            headers: {
                                'X-CSRF-TOKEN': "{{ csrf_token() }}",
                            }
                        },
                    });
                }
            }

            const messages = document.getElementById('chatMessages');
            const chatForm = document.getElementById('chatForm');
            const chatInput = document.getElementById('chatMessageInput');
            const chatError = document.getElementById('chatFormError');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const appendMessage = (payload) => {
                if (!messages) {
                    return;
                }
                const wrapper = document.createElement('div');
                wrapper.className = 'd-flex mb-3 ' + (payload.user_id === {{ $user->id }} ? 'justify-content-end' : 'justify-content-start');

                const bubble = document.createElement('div');
                bubble.className = 'p-3 rounded-3 ' + (payload.user_id === {{ $user->id }} ? 'bg-primary text-white' : 'bg-light');
                bubble.style.maxWidth = '70%';
                bubble.innerHTML = '<div class="small fw-semibold mb-1">' + (payload.user_id === {{ $user->id }} ? '{{ $user->name }}' : '{{ $other?->name ?? 'User' }}') + '</div>'
                    + '<div>' + payload.message + '</div>'
                    + '<div class="small opacity-75 mt-1">' + payload.created_at + '</div>';

                wrapper.appendChild(bubble);
                messages.appendChild(wrapper);
                messages.scrollTop = messages.scrollHeight;
            };

            if (chatForm && chatInput) {
                chatForm.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    if (chatError) {
                        chatError.innerHTML = '';
                    }
                    const message = chatInput.value.trim();
                    if (!message) {
                        return;
                    }
                    try {
                        const response = await fetch(chatForm.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-CSRF-TOKEN': csrfToken ?? '',
                            },
                            body: new URLSearchParams({ message }),
                        });

                        if (!response.ok) {
                            throw new Error('Message send failed.');
                        }

                        const data = await response.json();
                        appendMessage(data.message);
                        chatInput.value = '';
                    } catch (error) {
                        if (chatError) {
                            chatError.innerHTML = '<div class="alert alert-danger">Unable to send message. Please retry.</div>';
                        }
                    }
                });
            }

            if (window.ChatEcho && typeof window.ChatEcho.private === 'function') {
                window.ChatEcho.private("chat.conversation.{{ $conversation->id }}")
                .listen(".chat.message", (event) => {
                    if (!messages) {
                        return;
                    }
                    appendMessage(event.message);
                });
            }
        </script>
    @endpush
</x-admin-layout>
