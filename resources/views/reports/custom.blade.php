<x-admin-layout>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h1 class="h3 mb-1">Custom Reports</h1>
            <p class="text-muted mb-0">Generate focused reports by dataset, branch, and date range.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('reports.custom.csv', request()->query()) }}" data-download>Export CSV</a>
            <a class="btn btn-outline-dark" href="{{ route('reports.custom.pdf', request()->query()) }}" data-download>Export PDF</a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="type">Report Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="trips" @selected(request('type', $report_type) === 'trips')>Trips</option>
                        <option value="vehicles" @selected(request('type', $report_type) === 'vehicles')>Vehicles</option>
                        <option value="drivers" @selected(request('type', $report_type) === 'drivers')>Drivers</option>
                        <option value="incidents" @selected(request('type', $report_type) === 'incidents')>Incidents</option>
                        <option value="maintenance" @selected(request('type', $report_type) === 'maintenance')>Maintenance</option>
                    </select>
                </div>
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
                <div class="col-md-4">
                    <label class="form-label" for="branch_id">Branch</label>
                    <select class="form-select" id="branch_id" name="branch_id">
                        <option value="">All Branches</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary w-100" type="submit">Generate</button>
                    <a class="btn btn-outline-secondary w-100" href="{{ route('reports.custom') }}" data-loading>Reset</a>
                </div>
            </form>
        </div>
    </div>

    @if (! empty($summary))
        <div class="row g-3 mb-4">
            @foreach ($summary as $label => $value)
                <div class="col-md-3">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-label">{{ $label }}</div>
                            <div class="stat-value">{{ $value }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h5 mb-1">{{ $title }}</h2>
                    <div class="text-muted small">{{ $filters['branch_label'] }} · {{ $filters['range_label'] }}</div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle datatable">
                    <thead class="table-light">
                        <tr>
                            @foreach ($columns as $column)
                                <th>{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                @foreach ($row as $cell)
                                    <td>{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
