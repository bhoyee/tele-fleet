<x-admin-layout>
    @php
        $user = auth()->user();
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Chat</h1>
            <p class="text-muted mb-0">Connect with support and internal teams.</p>
        </div>
        @if (in_array($user->role, [\App\Models\User::ROLE_BRANCH_ADMIN, \App\Models\User::ROLE_BRANCH_HEAD], true))
            <form class="d-flex gap-2" method="POST" action="{{ route('chat.support') }}">
                @csrf
                <select class="form-select" name="issue_type" required>
                    <option value="">Select issue</option>
                    <option value="administrative">Administrative issue</option>
                    <option value="technical">Technical issue</option>
                </select>
                <button class="btn btn-primary" type="submit">Start Chat</button>
            </form>
        @endif
        @if (in_array($user->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true))
            <form class="d-flex gap-2" method="POST" action="{{ route('chat.direct') }}">
                @csrf
                <select class="form-select" name="user_id" required>
                    <option value="">Start chat with...</option>
                    @foreach ($users as $chatUser)
                        <option value="{{ $chatUser->id }}">{{ $chatUser->name }} ({{ str_replace('_', ' ', $chatUser->role) }})</option>
                    @endforeach
                </select>
                <button class="btn btn-primary" type="submit">Start Chat</button>
            </form>
        @endif
    </div>

    <div id="chatAlerts"></div>

    @if ($pendingRequests->isNotEmpty())
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h5 class="fw-semibold mb-3">Pending Requests</h5>
                <div class="d-flex flex-column gap-3">
                    @foreach ($pendingRequests as $requestConversation)
                        @php
                            $requester = $requestConversation->participants->firstWhere('user_id', '!=', $user->id)?->user;
                        @endphp
                        <div class="d-flex justify-content-between align-items-center border rounded-3 p-3">
                            <div>
                                <div class="fw-semibold">{{ $requester?->name ?? 'Unknown' }}</div>
                                <div class="text-muted small">
                                    {{ ucfirst($requestConversation->type) }} request
                                    @if ($requestConversation->issue_type)
                                        · {{ ucfirst($requestConversation->issue_type) }}
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <form method="POST" action="{{ route('chat.accept', $requestConversation) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-success btn-sm" type="submit">Accept</button>
                                </form>
                                <form method="POST" action="{{ route('chat.decline', $requestConversation) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-outline-danger btn-sm" type="submit">Decline</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="fw-semibold mb-3">Conversations</h5>
            <div class="list-group">
                @forelse ($conversations as $conversation)
                    @php
                        $other = $conversation->participants->firstWhere('user_id', '!=', $user->id)?->user;
                        $lastMessage = $conversation->messages->first();
                    @endphp
                    <a href="{{ route('chat.show', $conversation) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-loading>
                        <div>
                            <div class="fw-semibold">{{ $other?->name ?? 'Support' }}</div>
                            <div class="text-muted small">
                                {{ $lastMessage?->message ? \Illuminate\Support\Str::limit($lastMessage->message, 60) : 'No messages yet.' }}
                            </div>
                        </div>
                        <span class="badge bg-{{ $conversation->status === 'active' ? 'success' : ($conversation->status === 'pending' ? 'secondary' : 'dark') }}">
                            {{ ucfirst($conversation->status) }}
                        </span>
                    </a>
                @empty
                    <div class="text-muted small">No conversations yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
        <script>
            window.Pusher = Pusher;

            window.Echo = new Echo({
                broadcaster: 'pusher',
                key: "{{ config('broadcasting.connections.reverb.key') }}",
                wsHost: "{{ config('broadcasting.connections.reverb.options.host') }}",
                wsPort: {{ config('broadcasting.connections.reverb.options.port') }},
                wssPort: {{ config('broadcasting.connections.reverb.options.port') }},
                forceTLS: "{{ config('broadcasting.connections.reverb.options.scheme') }}" === 'https',
                enabledTransports: ['ws', 'wss'],
                authEndpoint: '/broadcasting/auth',
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    }
                },
            });

            window.Echo.private("chat.user.{{ $user->id }}")
                .listen(".chat.request", () => {
                    const alert = document.getElementById('chatAlerts');
                    if (alert) {
                        alert.innerHTML = '<div class="alert alert-info">New chat request received. Refresh the page to view.</div>';
                    }
                })
                .listen(".chat.accepted", () => {
                    const alert = document.getElementById('chatAlerts');
                    if (alert) {
                        alert.innerHTML = '<div class="alert alert-success">A chat request was accepted. Refresh to start messaging.</div>';
                    }
                });
        </script>
    @endpush
</x-admin-layout>
