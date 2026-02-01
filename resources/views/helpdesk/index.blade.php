<x-admin-layout>
    @php
        $currentUser = $user ?? auth()->user();
        $isManager = $currentUser && in_array($currentUser->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true);
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h1 class="h3 mb-1">Help Desk</h1>
            <p class="text-muted mb-0">Submit support tickets and track responses.</p>
        </div>
        <div>
            <a class="btn btn-primary" href="{{ route('helpdesk.create') }}">New Ticket</a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle datatable">
                    <thead class="table-light">
                        <tr>
                            <th>Ticket</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            @if ($isManager)
                                <th>Requester</th>
                                <th>Branch</th>
                            @endif
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tickets as $ticket)
                            @php
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
                                $categoryLabel = $ticket->category === 'administrative' ? 'Administrative' : 'Technical';
                            @endphp
                            <tr>
                                <td class="fw-semibold">TCK-{{ str_pad($ticket->id, 5, '0', STR_PAD_LEFT) }}</td>
                                <td>{{ $ticket->subject }}</td>
                                <td>{{ $categoryLabel }}</td>
                                <td><span class="badge {{ $priorityClass }}">{{ ucfirst($ticket->priority) }}</span></td>
                                <td><span class="badge {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span></td>
                                @if ($isManager)
                                    <td>{{ $ticket->user?->name ?? 'N/A' }}</td>
                                    <td>{{ $ticket->branch?->name ?? 'N/A' }}</td>
                                @endif
                                <td>{{ $ticket->created_at?->format('M d, Y H:i') }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('helpdesk.show', $ticket) }}" data-loading>View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
