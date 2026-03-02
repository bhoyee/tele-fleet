<x-admin-layout>
    <style>
        .tele-report-filter-card {
            cursor: pointer;
        }

        .tele-report-filter-card:focus-visible {
            outline: 3px solid rgba(5, 108, 163, 0.35);
            outline-offset: 2px;
        }

        .tele-report-filter-card.tele-report-filter-active {
            box-shadow: var(--shadow-lg);
            border-color: rgba(5, 108, 163, 0.35);
        }
    </style>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">My Requests Report</h1>
            <p class="text-muted mb-0">Analyze and export your trip requests.</p>
        </div>
        <div class="d-flex gap-2">
            <div class="btn-group">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="{{ route('reports.my-requests.excel', request()->query()) }}" data-download>
                            Export CSV (Current)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('reports.my-requests.pdf', request()->query()) }}" data-download>
                            Export PDF (Current)
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="{{ route('reports.my-requests.excel', request()->except(['status'])) }}" data-download>
                            Export CSV (All statuses)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('reports.my-requests.pdf', request()->except(['status'])) }}" data-download>
                            Export PDF (All statuses)
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <a class="card stat-card tele-report-filter-card text-decoration-none {{ request('status') ? '' : 'tele-report-filter-active' }}"
               href="{{ route('reports.my-requests', request()->except(['status'])) }}"
               aria-label="Show all requests">
                <div class="card-body">
                    <div class="stat-label">Total Requests</div>
                    <div class="stat-value">{{ $stats['total'] }}</div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a class="card stat-card tele-report-filter-card text-decoration-none {{ request('status') === 'approved' ? 'tele-report-filter-active' : '' }}"
               href="{{ route('reports.my-requests', array_merge(request()->except(['status']), ['status' => 'approved'])) }}"
               aria-label="Filter requests: approved">
                <div class="card-body">
                    <div class="stat-label">Approved</div>
                    <div class="stat-value">{{ $stats['approved'] }}</div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a class="card stat-card tele-report-filter-card text-decoration-none {{ request('status') === 'pending' ? 'tele-report-filter-active' : '' }}"
               href="{{ route('reports.my-requests', array_merge(request()->except(['status']), ['status' => 'pending'])) }}"
               aria-label="Filter requests: pending">
                <div class="card-body">
                    <div class="stat-label">Pending</div>
                    <div class="stat-value">{{ $stats['pending'] }}</div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a class="card stat-card tele-report-filter-card text-decoration-none {{ request('status') === 'rejected' ? 'tele-report-filter-active' : '' }}"
               href="{{ route('reports.my-requests', array_merge(request()->except(['status']), ['status' => 'rejected'])) }}"
               aria-label="Filter requests: rejected">
                <div class="card-body">
                    <div class="stat-label">Rejected</div>
                    <div class="stat-value">{{ $stats['rejected'] }}</div>
                </div>
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="range">Quick Range</label>
                    <select class="form-select" id="range" name="range">
                        <option value="">Custom</option>
                        <option value="today" @selected(request('range') === 'today')>Today</option>
                        <option value="week" @selected(request('range') === 'week')>This Week</option>
                        <option value="month" @selected(request('range') === 'month')>This Month</option>
                        <option value="year" @selected(request('range') === 'year')>This Year</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="from">From</label>
                    <input class="form-control" id="from" name="from" type="date" value="{{ request('from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="to">To</label>
                    <input class="form-control" id="to" name="to" type="date" value="{{ request('to') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All</option>
                        <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary w-100" type="submit">Filter</button>
                    <a class="btn btn-outline-secondary w-100" href="{{ route('reports.my-requests') }}" data-loading>Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle datatable" id="myRequestsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Request #</th>
                            <th>Destination</th>
                            <th>Trip Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trips as $trip)
                            <tr>
                                <td>{{ $trip->request_number }}</td>
                                <td>{{ \App\Support\TextNormalizer::titleText($trip->destination) }}</td>
                                <td>{{ $trip->trip_date?->format('M d, Y') }}</td>
                                <td>
                                    @php
                                        $displayStatus = $trip->status;
                                        if (in_array($trip->status, ['approved', 'assigned', 'completed'], true)) {
                                            $displayStatus = 'approved';
                                        } elseif ($trip->status === 'rejected') {
                                            $displayStatus = 'rejected';
                                        } else {
                                            $displayStatus = 'pending';
                                        }

                                        $statusClass = $displayStatus === 'approved'
                                            ? 'success'
                                            : ($displayStatus === 'rejected' ? 'danger' : 'secondary');
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">
                                        {{ ucfirst($displayStatus) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
