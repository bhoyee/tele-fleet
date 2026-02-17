<x-admin-layout>
    @php
        $currentUser = auth()->user();
        $isManager = $currentUser && in_array($currentUser->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true);
        $isSuperAdmin = $currentUser && $currentUser->role === \App\Models\User::ROLE_SUPER_ADMIN;

        $statusClass = match($ticket->status) {
            'open' => 'bg-warning text-dark',
            'in_progress' => 'bg-info text-dark',
            'resolved' => 'bg-success',
            'closed' => 'bg-secondary',
            default => 'bg-secondary',
        };

        $priorityClass = match($ticket->priority) {
            'low' => 'bg-light text-dark',
            'medium' => 'bg-warning text-dark',
            'high' => 'bg-danger',
            'critical' => 'bg-dark',
            default => 'bg-secondary',
        };

        $categoryLabel = match($ticket->category) {
            'administrative' => 'Administrative',
            'technical' => 'Technical',
            'developer_support' => 'Developer Support',
            default => ucfirst((string) $ticket->category),
        };

        $isClosed = $ticket->status === 'closed';
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h1 class="h3 mb-1">Ticket TCK-{{ str_pad($ticket->id, 5, '0', STR_PAD_LEFT) }}</h1>
            <p class="text-muted mb-0">{{ $ticket->subject }}</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a class="btn btn-outline-secondary" href="{{ route('helpdesk.index') }}">Back to Tickets</a>
            @if ($isSuperAdmin)
                <button class="btn btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteTicketModal">Delete</button>
            @endif
            <span class="badge {{ $statusClass }} align-self-center">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mt-3">
                <div class="card-body">
                    <h5 class="mb-3">Conversation</h5>

                    @if ($ticket->category === 'developer_support')
                        <div class="alert alert-info border d-flex align-items-start gap-2">
                            <i class="bi bi-info-circle mt-1"></i>
                            <div class="small">
                                <div class="fw-semibold">Developer Support</div>
                                <div>
                                    Developer replies appear here when they use the secure “Reply to Ticket” link in their email.
                                    (Email-only replies require inbound inbox/IMAP fetching.)
                                </div>
                            </div>
                        </div>
                    @endif

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
                        <div class="mb-3">
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

                    <form method="POST" action="{{ route('helpdesk.messages.store', $ticket) }}" class="mt-4" id="helpdeskReplyForm" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label">Reply</label>
                            <textarea class="form-control" name="message" id="helpdeskReplyMessage" rows="4" @disabled($isClosed)>{{ old('message') }}</textarea>
                            @error('message') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Attachments (images, PDF, DOC/DOCX)</label>
                            <input class="form-control" type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx" @disabled($isClosed)>
                            <div class="text-muted small mt-1">Max size per file: 10MB.</div>
                            @error('attachments.*') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <button class="btn btn-primary" type="submit" id="helpdeskReplySubmit" @disabled($isClosed)>Send Reply</button>
                        @if ($isClosed)
                            <div class="text-muted small mt-2">This ticket is closed. Replies are disabled.</div>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <h5 class="mb-3">Ticket Details</h5>
                    <div class="mb-2">
                        <div class="text-muted small">Category</div>
                        <div class="fw-semibold">{{ $categoryLabel }}</div>
                    </div>
                    <div class="mb-2">
                        <div class="text-muted small">Priority</div>
                        <span class="badge {{ $priorityClass }}">{{ ucfirst($ticket->priority) }}</span>
                    </div>
                    <div class="mb-2">
                        <div class="text-muted small">Status</div>
                        <span class="badge {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                    </div>
                    <div class="mb-2">
                        <div class="text-muted small">Requester</div>
                        <div class="fw-semibold">{{ $ticket->user?->name ?? 'N/A' }}</div>
                    </div>
                    <div class="mb-2">
                        <div class="text-muted small">Branch</div>
                        <div class="fw-semibold">{{ $ticket->branch?->name ?? 'N/A' }}</div>
                    </div>
                    <div class="mb-0">
                        <div class="text-muted small">Created</div>
                        <div class="fw-semibold">{{ $ticket->created_at?->format('M d, Y H:i') }}</div>
                    </div>
                </div>
            </div>

            @if ($ticket->attachments?->isNotEmpty())
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-body">
                        <h5 class="mb-3">Attachments</h5>
                        <div class="list-group">
                            @foreach ($ticket->attachments as $attachment)
                                <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="{{ route('helpdesk.attachments.download', [$ticket, $attachment]) }}">
                                    <span class="text-truncate">{{ $attachment->original_name }}</span>
                                    <span class="badge bg-light text-dark">Download</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            @if ($isManager)
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="mb-3">Update Status</h5>
                        <form method="POST" action="{{ route('helpdesk.update', $ticket) }}">
                            @csrf
                            @method('PATCH')
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="open" @selected($ticket->status === 'open')>Open</option>
                                    <option value="in_progress" @selected($ticket->status === 'in_progress')>In Progress</option>
                                    <option value="resolved" @selected($ticket->status === 'resolved')>Resolved</option>
                                    <option value="closed" @selected($ticket->status === 'closed')>Closed</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Priority</label>
                                <select class="form-select" name="priority" required>
                                    <option value="low" @selected($ticket->priority === 'low')>Low</option>
                                    <option value="medium" @selected($ticket->priority === 'medium')>Medium</option>
                                    <option value="high" @selected($ticket->priority === 'high')>High</option>
                                    <option value="critical" @selected($ticket->priority === 'critical')>Critical</option>
                                </select>
                            </div>
                            <button class="btn btn-primary w-100" type="submit">Save Changes</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if ($isSuperAdmin)
        <div class="modal fade" id="deleteTicketModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Ticket</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">Delete this ticket permanently? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST" action="{{ route('helpdesk.destroy', $ticket) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete Ticket</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
        <script>
            const initHelpdeskReplyEditor = () => {
                if (!window.tinymce) return;
                const replyField = document.getElementById('helpdeskReplyMessage');
                if (!replyField || replyField.disabled) return;
                tinymce.init({
                    selector: '#helpdeskReplyMessage',
                    height: 220,
                    menubar: false,
                    plugins: 'lists link',
                    toolbar: 'undo redo | bold italic | bullist numlist | link',
                });
            };

            document.addEventListener('DOMContentLoaded', initHelpdeskReplyEditor);

            const replyForm = document.getElementById('helpdeskReplyForm');
            const replySubmit = document.getElementById('helpdeskReplySubmit');

            const applyLoadingState = (button) => {
                if (!button || button.classList.contains('btn-loading')) return;
                const label = document.createElement('span');
                label.className = 'btn-label';
                label.textContent = button.textContent.trim();
                const spinner = document.createElement('span');
                spinner.className = 'spinner-border spinner-border-sm btn-spinner ms-2';
                spinner.setAttribute('role', 'status');
                spinner.setAttribute('aria-hidden', 'true');
                button.textContent = '';
                button.appendChild(label);
                button.appendChild(spinner);
                button.classList.add('btn-loading');
                button.setAttribute('disabled', 'disabled');
            };

            if (replyForm && replySubmit) {
                replyForm.addEventListener('submit', (event) => {
                    if (window.tinymce) {
                        tinymce.triggerSave();
                    }
                    const messageValue = replyForm.querySelector('[name="message"]')?.value?.trim();
                    if (!messageValue) {
                        event.preventDefault();
                        alert('Please enter a reply message.');
                        return;
                    }
                    if (replySubmit.disabled) {
                        event.preventDefault();
                        return;
                    }
                    applyLoadingState(replySubmit);
                });
            }
        </script>
    @endpush
</x-admin-layout>
