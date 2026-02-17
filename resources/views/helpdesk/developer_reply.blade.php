<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Tele-Fleet') }} | Reply to Ticket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8fafc;
        }
        .brand img {
            height: 44px;
            width: auto;
            max-width: 260px;
            object-fit: contain;
        }
        .message-bubble {
            border-radius: 14px;
            padding: 12px 14px;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
            <div class="brand d-flex align-items-center gap-2">
                @if (config('app.brand_logo_url'))
                    <img src="{{ config('app.brand_logo_url') }}" alt="{{ config('app.name', 'Tele-Fleet') }} logo">
                @else
                    <strong>{{ config('app.name', 'Tele-Fleet') }}</strong>
                @endif
            </div>
            <div class="text-muted small">
                Ticket: <strong>TCK-{{ str_pad($ticket->id, 5, '0', STR_PAD_LEFT) }}</strong>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <h1 class="h5 mb-1">{{ $ticket->subject }}</h1>
                <div class="text-muted small">
                    Category: Developer Support · Priority: {{ ucfirst($ticket->priority) }} · Status: {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                </div>
                <div class="text-muted small mt-2">
                    Requester: {{ $ticket->user?->name ?? 'N/A' }} · Branch: {{ $ticket->branch?->name ?? 'N/A' }}
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <h2 class="h6 mb-3">Conversation</h2>
                @forelse ($ticket->messages as $message)
                    @php
                        $isExternal = ! $message->user_id;
                        $sender = $message->user?->name
                            ?? ($message->external_name ?: 'Developer');
                        if ($isExternal && $message->external_email) {
                            $sender = trim($sender . ' <' . $message->external_email . '>');
                        }
                        $bubbleClass = $isExternal ? 'bg-white border' : 'bg-primary text-white';
                    @endphp
                    <div class="mb-3">
                        <div class="small text-muted mb-1">{{ $sender }} · {{ $message->created_at?->format('M d, Y H:i') }}</div>
                        <div class="message-bubble {{ $bubbleClass }}">{!! $message->message !!}</div>
                        @if ($message->attachments?->isNotEmpty())
                            <div class="mt-2 small">
                                @foreach ($message->attachments as $att)
                                    <div class="text-muted">{{ $att->original_name }}</div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-muted">No messages yet.</div>
                @endforelse
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h2 class="h6 mb-3">Post a Reply</h2>

                @if ($isClosed)
                    <div class="alert alert-warning mb-0">This ticket is closed. Replies are disabled.</div>
                @else
                    <form method="POST" action="{{ route('developer-support.reply.store', $ticket) }}?{{ http_build_query(request()->query()) }}" enctype="multipart/form-data">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="name">Your name (optional)</label>
                                <input class="form-control" id="name" name="name" value="{{ old('name') }}">
                                @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Your email (optional)</label>
                                <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}">
                                @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="message">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="6" required>{{ old('message') }}</textarea>
                                @error('message') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="attachments">Attachments</label>
                                <input class="form-control" id="attachments" type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx">
                                <div class="form-text">Max 10MB per file.</div>
                                @error('attachments.*') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button class="btn btn-primary" type="submit">Send Reply</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

