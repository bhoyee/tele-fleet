<x-admin-layout>
    @php
        $currentUser = $user ?? auth()->user();
        $isManager = $currentUser && in_array($currentUser->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true);
    @endphp
    <style>
        .tele-helpdesk-filter {
            cursor: pointer;
        }
    </style>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h1 class="h3 mb-1">Help Desk</h1>
            <p class="text-muted mb-0">Submit support tickets and track responses.</p>
        </div>
        <div>
            @if (($currentUser?->role ?? null) === \App\Models\User::ROLE_SUPER_ADMIN)
                <a class="btn btn-outline-primary me-2" href="{{ route('helpdesk.create', ['developer' => 1]) }}">Contact Developer</a>
            @endif
            <a class="btn btn-primary" href="{{ route('helpdesk.create') }}">New Ticket</a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>Help Desk Analytics (<span data-helpdesk-month>{{ $helpdeskAnalytics['month_label'] ?? now()->format('F Y') }}</span>)</span>
            <span class="text-muted small" data-helpdesk-priority>
                Low <span data-helpdesk-priority-pct="low">{{ $helpdeskAnalytics['priority']['low']['percent'] ?? 0 }}</span>% &bull;
                Medium <span data-helpdesk-priority-pct="medium">{{ $helpdeskAnalytics['priority']['medium']['percent'] ?? 0 }}</span>% &bull;
                High <span data-helpdesk-priority-pct="high">{{ $helpdeskAnalytics['priority']['high']['percent'] ?? 0 }}</span>% &bull;
                Critical <span data-helpdesk-priority-pct="critical">{{ $helpdeskAnalytics['priority']['critical']['percent'] ?? 0 }}</span>%
            </span>
        </div>
        <div class="card-body">
            <div class="row g-3 flex-nowrap overflow-auto" id="helpdeskStatsCards">
                <div class="col-8 col-sm-6 col-lg-2">
                    <div class="card stat-card h-100 tele-helpdesk-filter" role="button" tabindex="0" data-helpdesk-filter="all" data-tele-tooltip title="Show tickets created this month">
                        <div class="card-body">
                            <div class="stat-label">Total Tickets</div>
                            <div class="stat-value" data-helpdesk-stat="total">{{ $helpdeskAnalytics['total'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-8 col-sm-6 col-lg-2">
                    <div class="card stat-card h-100 tele-helpdesk-filter" role="button" tabindex="0" data-helpdesk-filter="status" data-helpdesk-status="open" data-tele-tooltip title="Filter open tickets (this month)">
                        <div class="card-body">
                            <div class="stat-label">Open</div>
                            <div class="stat-value" data-helpdesk-stat="open">{{ $helpdeskAnalytics['open'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-8 col-sm-6 col-lg-2">
                    <div class="card stat-card h-100 tele-helpdesk-filter" role="button" tabindex="0" data-helpdesk-filter="status" data-helpdesk-status="in_progress" data-tele-tooltip title="Filter tickets in progress (this month)">
                        <div class="card-body">
                            <div class="stat-label">In Progress</div>
                            <div class="stat-value" data-helpdesk-stat="in_progress">{{ $helpdeskAnalytics['in_progress'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-8 col-sm-6 col-lg-2">
                    <div class="card stat-card h-100 tele-helpdesk-filter" role="button" tabindex="0" data-helpdesk-filter="status" data-helpdesk-status="resolved" data-tele-tooltip title="Filter resolved tickets (this month)">
                        <div class="card-body">
                            <div class="stat-label">Resolved</div>
                            <div class="stat-value" data-helpdesk-stat="resolved">{{ $helpdeskAnalytics['resolved'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-8 col-sm-6 col-lg-2">
                    <div class="card stat-card h-100 tele-helpdesk-filter" role="button" tabindex="0" data-helpdesk-filter="status" data-helpdesk-status="closed" data-tele-tooltip title="Filter closed tickets (this month)">
                        <div class="card-body">
                            <div class="stat-label">Closed</div>
                            <div class="stat-value" data-helpdesk-stat="closed">{{ $helpdeskAnalytics['closed'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
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
                                $categoryLabel = match($ticket->category) {
                                    'administrative' => 'Administrative',
                                    'technical' => 'Technical',
                                    'developer_support' => 'Developer Support',
                                    default => ucfirst((string) $ticket->category),
                                };
                            @endphp
                            <tr>
                                <td class="fw-semibold">TCK-{{ str_pad($ticket->id, 5, '0', STR_PAD_LEFT) }}</td>
                                <td>{{ \App\Support\TextNormalizer::titleText($ticket->subject) }}</td>
                                <td>{{ $categoryLabel }}</td>
                                <td>
                                    <span class="visually-hidden">{{ $ticket->priority }}</span>
                                    <span class="badge {{ $priorityClass }}">{{ ucfirst($ticket->priority) }}</span>
                                </td>
                                <td>
                                    <span class="visually-hidden">{{ $ticket->status }}</span>
                                    <span class="badge {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                                </td>
                                @if ($isManager)
                                    <td>{{ $ticket->user?->name ? \App\Support\TextNormalizer::personName($ticket->user->name) : 'N/A' }}</td>
                                    <td>{{ $ticket->branch?->name ? \App\Support\TextNormalizer::titleText($ticket->branch->name) : 'N/A' }}</td>
                                @endif
                                <td>
                                    <span class="visually-hidden">{{ $ticket->created_at?->format('Y-m-d') }}</span>
                                    {{ $ticket->created_at?->format('M d, Y H:i') }}
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('helpdesk.show', $ticket) }}" data-loading data-tele-tooltip title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const statsUrl = "{{ route('helpdesk.stats') }}";
                const currentMonth = @json(now()->format('Y-m'));

                const applyStats = (stats) => {
                    if (!stats) return;
                    const monthEl = document.querySelector('[data-helpdesk-month]');
                    if (monthEl && stats.month_label) {
                        monthEl.textContent = String(stats.month_label);
                    }
                    ['total', 'open', 'in_progress', 'resolved', 'closed'].forEach((key) => {
                        const el = document.querySelector(`[data-helpdesk-stat="${key}"]`);
                        if (el) {
                            el.textContent = String(stats[key] ?? 0);
                        }
                    });

                    const priority = stats.priority || {};
                    ['low', 'medium', 'high', 'critical'].forEach((key) => {
                        const el = document.querySelector(`[data-helpdesk-priority-pct="${key}"]`);
                        if (el) {
                            el.textContent = String(priority?.[key]?.percent ?? 0);
                        }
                    });
                };

                const refreshStats = () => {
                    fetch(statsUrl, { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
                        .then((res) => res.ok ? res.json() : null)
                        .then((data) => applyStats(data?.stats))
                        .catch(() => {});
                };

                refreshStats();
                setInterval(refreshStats, 30000);
            });
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const table = document.querySelector('table.datatable');
                if (!table) {
                    return;
                }

                const currentMonth = @json(now()->format('Y-m'));

                const escapeRegex = (value) => String(value ?? '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

                const findColumnIndex = (label) => {
                    const headers = Array.from(table.querySelectorAll('thead th'));
                    return headers.findIndex((th) => String(th?.textContent ?? '').trim().toLowerCase() === String(label).trim().toLowerCase());
                };

                const statusCol = findColumnIndex('Status');
                const createdCol = findColumnIndex('Created');

                let activeHelpdeskFilter = { type: 'none', month: null, status: null };

                const applyHelpdeskFilter = () => {
                    if (!window.jQuery?.fn?.dataTable) {
                        return;
                    }
                    if (!window.jQuery.fn.dataTable.isDataTable(table)) {
                        return;
                    }

                    const dt = window.jQuery(table).DataTable();
                    dt.search('');
                    dt.columns().search('');

                    if (createdCol >= 0 && activeHelpdeskFilter.month) {
                        dt.column(createdCol).search(escapeRegex(activeHelpdeskFilter.month) + '-\\d{2}', true, false, true);
                    }

                    if (statusCol >= 0 && activeHelpdeskFilter.type === 'status' && activeHelpdeskFilter.status) {
                        dt.column(statusCol).search('\\b' + escapeRegex(activeHelpdeskFilter.status) + '\\b', true, false, true);
                    }

                    dt.draw();
                };

                const scrollToTable = () => {
                    table.scrollIntoView({ behavior: 'smooth', block: 'start' });
                };

                const handleFilterClick = (node) => {
                    const type = node.getAttribute('data-helpdesk-filter');
                    if (!type) {
                        return;
                    }

                    if (type === 'all') {
                        activeHelpdeskFilter = { type: 'all', month: currentMonth, status: null };
                        applyHelpdeskFilter();
                        scrollToTable();
                        return;
                    }

                    if (type === 'status') {
                        const status = String(node.getAttribute('data-helpdesk-status') || '').trim().toLowerCase();
                        activeHelpdeskFilter = { type: 'status', month: currentMonth, status };
                        applyHelpdeskFilter();
                        scrollToTable();
                    }
                };

                const waitForDataTable = () => {
                    let attempts = 0;
                    const MAX_ATTEMPTS = 20;

                    const tick = () => {
                        attempts += 1;
                        if (window.jQuery?.fn?.dataTable && window.jQuery.fn.dataTable.isDataTable(table)) {
                            return;
                        }
                        if (attempts < MAX_ATTEMPTS) {
                            setTimeout(tick, 150);
                        }
                    };

                    tick();
                };

                waitForDataTable();

                document.addEventListener('click', (event) => {
                    const target = event.target.closest('[data-helpdesk-filter]');
                    if (!target) {
                        return;
                    }
                    handleFilterClick(target);
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter' && event.key !== ' ') {
                        return;
                    }
                    const target = event.target.closest('[data-helpdesk-filter]');
                    if (!target) {
                        return;
                    }
                    event.preventDefault();
                    handleFilterClick(target);
                });
            });
        </script>
    @endpush
</x-admin-layout>
