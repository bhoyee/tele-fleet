<x-admin-layout>
    <style>
        /* MOBILE-FIRST RESPONSIVE STYLES - COMPLETELY REPLACED */
        
        /* Reset all fixed sizes - use responsive units */
        .fleet-pie-wrap {
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
            aspect-ratio: 1; /* Keep charts square */
        }
        
        @media (min-width: 576px) {
            .fleet-pie-wrap {
                max-width: 350px;
            }
        }
        
        @media (min-width: 768px) {
            .fleet-pie-wrap {
                max-width: 400px;
            }
        }
        
        @media (min-width: 992px) {
            .fleet-pie-wrap {
                max-width: 447px;
            }
        }

        .fleet-pie-wrap canvas {
            width: 100% !important;
            height: 100% !important;
        }

        /* Header section - mobile first */
        .page-header-wrapper {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        @media (min-width: 768px) {
            .page-header-wrapper {
                flex-direction: row;
                justify-content: space-between;
                align-items: flex-start;
            }
        }

        .fleet-report-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            width: 100%;
        }
        
        @media (min-width: 576px) {
            .fleet-report-actions {
                flex-direction: row;
                width: auto;
            }
        }

        .fleet-report-actions .btn {
            width: 100%;
            min-width: 140px;
        }
        
        @media (min-width: 576px) {
            .fleet-report-actions .btn {
                width: auto;
            }
        }

        /* FILTER FORM - THIS IS THE MAIN FIX */
        .fleet-report-filters .row {
            --bs-gutter-x: 1rem;
            --bs-gutter-y: 1rem;
        }

        /* Fix for form controls on mobile */
        .fleet-report-filters .form-control,
        .fleet-report-filters .form-select {
            height: 2.75rem;
            font-size: 1rem; /* Prevent iOS zoom */
        }
        
        @media (max-width: 767px) {
            .fleet-report-filters .form-control,
            .fleet-report-filters .form-select {
                min-height: 48px; /* Touch-friendly on mobile */
            }
        }

        /* Summary cards - responsive grid */
        .fleet-summary-cards .row {
            --bs-gutter-x: 1rem;
            --bs-gutter-y: 1rem;
        }

        /* Stats cards - responsive typography */
        .stat-value {
            font-size: clamp(1.25rem, 4vw, 1.75rem);
            font-weight: 600;
            line-height: 1.2;
        }

        /* Tabs - mobile scrolling */
        .fleet-report-tabs {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            padding-bottom: 4px;
            gap: 0.25rem;
        }

        .fleet-report-tabs::-webkit-scrollbar {
            display: none;
        }

        .fleet-report-tabs .nav-item {
            flex: 0 0 auto;
        }

        .fleet-report-tabs .nav-link {
            white-space: nowrap;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            border-radius: 0.375rem;
        }
        
        @media (max-width: 767px) {
            .fleet-report-tabs .nav-link {
                padding: 0.625rem 0.75rem;
                font-size: 0.85rem;
            }
        }

        /* Tables - horizontal scroll on mobile */
        .fleet-report-table {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .fleet-report-table .table {
            min-width: 600px; /* Ensure table doesn't collapse too much */
            font-size: 0.875rem;
        }
        
        @media (min-width: 768px) {
            .fleet-report-table .table {
                min-width: unset;
            }
        }

        /* Branch leaders section - stack on mobile */
        .fleet-leaders-card .row {
            --bs-gutter-x: 1rem;
            --bs-gutter-y: 1rem;
        }

        /* Touch-friendly buttons */
        @media (hover: none) and (pointer: coarse) {
            .btn, .nav-link {
                min-height: 44px;
            }
            
            .form-control, .form-select {
                min-height: 48px;
                font-size: 16px;
            }
        }

        /* Very small screens */
        @media (max-width: 400px) {
            .fleet-report-tabs .nav-link {
                padding: 0.5rem 0.625rem;
                font-size: 0.8rem;
            }
            
            .fleet-pie-wrap {
                max-width: 250px;
            }
        }
    </style>

    <!-- HEADER SECTION - Fixed with proper responsive classes -->
    <div class="page-header-wrapper">
        <div>
            <h1 class="h3 mb-1">Fleet Reports</h1>
            <p class="text-muted mb-0">Operational insights across vehicles, drivers, trips, incidents, and maintenance.</p>
        </div>
        <div class="fleet-report-actions mt-3 mt-md-0">
            <a class="btn btn-outline-primary" href="{{ route('reports.fleet.csv', request()->query()) }}" data-download>Export CSV</a>
            <a class="btn btn-outline-dark" href="{{ route('reports.fleet.pdf', request()->query()) }}" data-download>Export PDF</a>
        </div>
    </div>

    <!-- FILTER FORM - Using proper responsive Bootstrap classes -->
    <div class="card shadow-sm border-0 mb-4 fleet-report-filters">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <!-- Mobile: 1 col, Tablet: 2 cols, Desktop: 4 cols -->
                <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                    <label class="form-label" for="range">Quick Range</label>
                    <select class="form-select" id="range" name="range">
                        <option value="">Custom</option>
                        <option value="today" @selected(request('range') === 'today')>Today</option>
                        <option value="week" @selected(request('range') === 'week')>This Week</option>
                        <option value="month" @selected(request('range') === 'month')>This Month</option>
                        <option value="year" @selected(request('range') === 'year')>This Year</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                    <label class="form-label" for="from">From</label>
                    <input class="form-control" id="from" name="from" type="date" value="{{ request('from') }}">
                </div>
                <div class="col-12 col-sm-6 col-md-6 col-lg-3">
                    <label class="form-label" for="to">To</label>
                    <input class="form-control" id="to" name="to" type="date" value="{{ request('to') }}">
                </div>
                <div class="col-12 col-sm-6 col-md-6 col-lg-3">
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
                <!-- Buttons - Stack on mobile, side by side on larger screens -->
                <div class="col-12">
                    <div class="d-flex flex-column flex-sm-row gap-2 mt-2">
                        <button class="btn btn-primary flex-grow-1" type="submit">Apply Filters</button>
                        <a class="btn btn-outline-secondary flex-grow-1" href="{{ route('reports.fleet') }}" data-loading>Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- TABS - Already responsive -->
    <ul class="nav nav-tabs mb-3 fleet-report-tabs" id="fleetReportTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview-tabpane" type="button" role="tab">Overview</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="trips-tab" data-bs-toggle="tab" data-bs-target="#trips-tabpane" type="button" role="tab">Trips</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="vehicles-tab" data-bs-toggle="tab" data-bs-target="#vehicles-tabpane" type="button" role="tab">Vehicles</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="drivers-tab" data-bs-toggle="tab" data-bs-target="#drivers-tabpane" type="button" role="tab">Drivers</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="incidents-tab" data-bs-toggle="tab" data-bs-target="#incidents-tabpane" type="button" role="tab">Incidents</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance-tabpane" type="button" role="tab">Maintenance</button>
        </li>
    </ul>

    <!-- TAB CONTENT - Fix all the grids -->
    <div class="tab-content" id="fleetReportTabContent">
        <div class="tab-pane fade show active" id="overview-tabpane" role="tabpanel">
            <!-- SUMMARY CARDS - Mobile: 1 col, Tablet: 2 cols, Desktop: 3-4 cols -->
            <div class="row g-3 mb-4 fleet-summary-cards">
                <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-label">Total Trips</div>
                            <div class="stat-value">{{ $stats['total_trips'] }}</div>
                            <div class="text-muted small">{{ $filters['range_label'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-label">Completion Rate</div>
                            <div class="stat-value">{{ $stats['completion_rate'] }}%</div>
                            <div class="text-muted small">{{ $stats['completed_trips'] }} completed</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-label">Approval Rate</div>
                            <div class="stat-value">{{ $stats['approval_rate'] }}%</div>
                            <div class="text-muted small">{{ $stats['approved_trips'] }} approved</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-label">Vehicles Available</div>
                            <div class="stat-value">{{ $stats['vehicles_available'] }}/{{ $stats['total_vehicles'] }}</div>
                            <div class="text-muted small">{{ $stats['vehicles_in_use'] }} in use</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-label">Active Drivers</div>
                            <div class="stat-value">{{ $stats['drivers_active'] }}/{{ $stats['total_drivers'] }}</div>
                            <div class="text-muted small">Active today</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-label">Open Incidents</div>
                            <div class="stat-value">{{ $stats['incidents_open'] }}</div>
                            <div class="text-muted small">{{ $stats['incidents_review'] }} under review</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-label">Maintenance Due</div>
                            <div class="stat-value">{{ $stats['maintenance_due'] }}</div>
                            <div class="text-muted small">{{ $stats['maintenance_overdue'] }} overdue</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-xl-3">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-label">Avg Approval Time</div>
                            <div class="stat-value">{{ $stats['avg_approval_hours'] ?? 'N/A' }}</div>
                            <div class="text-muted small">Hours from request</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CHARTS - Stack on mobile, side by side on desktop -->
            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">Trip Status Mix</div>
                        <div class="card-body">
                            <canvas id="tripStatusChart" height="220"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">Vehicle Status</div>
                        <div class="card-body">
                            <div class="fleet-pie-wrap">
                                <canvas id="vehicleStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">Driver Status</div>
                        <div class="card-body">
                            <div class="fleet-pie-wrap">
                                <canvas id="driverStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">Incident Status</div>
                        <div class="card-body">
                            <div class="fleet-pie-wrap">
                                <canvas id="incidentStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BRANCH LEADERS - Stack on mobile -->
            <div class="row g-3 mt-1">
                <div class="col-12">
                    <div class="card fleet-leaders-card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <div class="fw-semibold">Branch Leaders</div>
                                <div class="text-muted small">Top performing branches for the selected range.</div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="p-3 border rounded-3 bg-light h-100">
                                        <div class="fw-semibold mb-2">Trip Requests</div>
                                        @forelse ($rankings['top_trips'] as $item)
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-truncate">{{ $item['branch'] }}</span>
                                                <span class="badge bg-primary ms-2">{{ $item['trips'] }}</span>
                                            </div>
                                        @empty
                                            <div class="text-muted small">No data.</div>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="p-3 border rounded-3 bg-light h-100">
                                        <div class="fw-semibold mb-2">Driver Usage</div>
                                        @forelse ($rankings['top_driver_usage'] as $item)
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-truncate">{{ $item['branch'] }}</span>
                                                <span class="badge bg-primary ms-2">{{ $item['driver_usage'] }}</span>
                                            </div>
                                        @empty
                                            <div class="text-muted small">No data.</div>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="p-3 border rounded-3 bg-light h-100">
                                        <div class="fw-semibold mb-2">Incident Reports</div>
                                        @forelse ($rankings['top_incidents'] as $item)
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-truncate">{{ $item['branch'] }}</span>
                                                <span class="badge bg-primary ms-2">{{ $item['incidents'] }}</span>
                                            </div>
                                        @empty
                                            <div class="text-muted small">No data.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="card h-100">
                                        <div class="card-header">Trips by Branch</div>
                                        <div class="card-body">
                                            <canvas id="branchTripsChart" height="200"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="card h-100">
                                        <div class="card-header">Driver Usage by Branch</div>
                                        <div class="card-body">
                                            <canvas id="branchDriversChart" height="200"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="card h-100">
                                        <div class="card-header">Incidents by Branch</div>
                                        <div class="card-body">
                                            <canvas id="branchIncidentsChart" height="200"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive mt-4 fleet-report-table">
                                <table class="table align-middle datatable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Branch</th>
                                            <th>Trip Requests</th>
                                            <th>Driver Usage</th>
                                            <th>Incident Reports</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($rankings['branch_table'] as $row)
                                            <tr>
                                                <td class="text-truncate" style="max-width: 120px;">{{ $row['branch'] }}</td>
                                                <td>{{ $row['trips'] }}</td>
                                                <td>{{ $row['driver_usage'] }}</td>
                                                <td>{{ $row['incidents'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- OTHER TABS - Fix all grids similarly -->
        <div class="tab-pane fade" id="trips-tabpane" role="tabpanel">
            <div class="row g-3 mb-3">
                <div class="col-6 col-sm-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="stat-label">Completed Trips</div>
                            <div class="stat-value">{{ $stats['completed_trips'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="stat-label">Pending Trips</div>
                            <div class="stat-value">{{ $stats['pending_trips'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="stat-label">Rejected Trips</div>
                            <div class="stat-value">{{ $stats['rejected_trips'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="stat-label">Assigned Trips</div>
                            <div class="stat-value">{{ $stats['assigned_trips'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 fleet-report-table">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle datatable">
                            <thead class="table-light">
                                <tr>
                                    <th>Request #</th>
                                    <th>Branch</th>
                                    <th>Requester</th>
                                    <th>Trip Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tables['trips'] as $trip)
                                    <tr>
                                        <td class="text-truncate" style="max-width: 100px;">{{ $trip->request_number }}</td>
                                        <td class="text-truncate" style="max-width: 100px;">{{ $trip->branch?->name ?? 'N/A' }}</td>
                                        <td class="text-truncate" style="max-width: 100px;">{{ $trip->requestedBy?->name ?? 'N/A' }}</td>
                                        <td>{{ $trip->trip_date?->format('M d, Y') }}</td>
                                        <td><span class="badge bg-secondary">{{ ucfirst($trip->status) }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Continue fixing other tabs with same pattern -->
        <!-- Vehicles Tab -->
        <div class="tab-pane fade" id="vehicles-tabpane" role="tabpanel">
            <div class="row g-3 mb-3">
                <div class="col-6 col-sm-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="stat-label">Available</div>
                            <div class="stat-value">{{ $stats['vehicles_available'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="stat-label">In Use</div>
                            <div class="stat-value">{{ $stats['vehicles_in_use'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="stat-label">Maintenance</div>
                            <div class="stat-value">{{ $stats['vehicles_maintenance'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="stat-label">Offline</div>
                            <div class="stat-value">{{ $stats['vehicles_offline'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 fleet-report-table">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle datatable">
                            <thead class="table-light">
                                <tr>
                                    <th>Registration</th>
                                    <th>Make</th>
                                    <th>Model</th>
                                    <th>Status</th>
                                    <th>Maintenance</th>
                                    <th>Mileage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tables['vehicles'] as $vehicle)
                                    <tr>
                                        <td class="text-truncate" style="max-width: 100px;">{{ $vehicle->registration_number }}</td>
                                        <td class="text-truncate" style="max-width: 80px;">{{ $vehicle->make }}</td>
                                        <td class="text-truncate" style="max-width: 80px;">{{ $vehicle->model }}</td>
                                        <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $vehicle->report_status)) }}</span></td>
                                        <td><span class="badge bg-light text-dark">{{ ucfirst($vehicle->maintenance_state ?? 'ok') }}</span></td>
                                        <td>{{ number_format($vehicle->current_mileage ?? 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Drivers Tab -->
        <div class="tab-pane fade" id="drivers-tabpane" role="tabpanel">
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-4">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="stat-label">Active Drivers</div>
                            <div class="stat-value">{{ $stats['drivers_active'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="stat-label">Inactive Drivers</div>
                            <div class="stat-value">{{ $stats['drivers_inactive'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="stat-label">Suspended Drivers</div>
                            <div class="stat-value">{{ $stats['drivers_suspended'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 fleet-report-table">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle datatable">
                            <thead class="table-light">
                                <tr>
                                    <th>Driver</th>
                                    <th>Status</th>
                                    <th>License Expiry</th>
                                    <th>Trips in Range</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tables['drivers'] as $driverRow)
                                    <tr>
                                        <td class="text-truncate" style="max-width: 120px;">{{ $driverRow['driver']?->full_name ?? 'N/A' }}</td>
                                        <td><span class="badge bg-secondary">{{ ucfirst($driverRow['driver']?->status ?? 'N/A') }}</span></td>
                                        <td>{{ $driverRow['driver']?->license_expiry?->format('M d, Y') ?? 'N/A' }}</td>
                                        <td>{{ $driverRow['trips_count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Incidents Tab -->
        <div class="tab-pane fade" id="incidents-tabpane" role="tabpanel">
            <div class="row g-3 mb-3">
                <div class="col-6 col-sm-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="stat-label">Open</div>
                            <div class="stat-value">{{ $stats['incidents_open'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="stat-label">Under Review</div>
                            <div class="stat-value">{{ $stats['incidents_review'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="stat-label">Resolved</div>
                            <div class="stat-value">{{ $stats['incidents_resolved'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="stat-label">Cancelled</div>
                            <div class="stat-value">{{ $stats['incidents_cancelled'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 fleet-report-table">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle datatable">
                            <thead class="table-light">
                                <tr>
                                    <th>Reference</th>
                                    <th>Branch</th>
                                    <th>Severity</th>
                                    <th>Status</th>
                                    <th>Incident Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tables['incidents'] as $incident)
                                    <tr>
                                        <td class="text-truncate" style="max-width: 100px;">{{ $incident->reference }}</td>
                                        <td class="text-truncate" style="max-width: 100px;">{{ $incident->branch?->name ?? 'N/A' }}</td>
                                        <td>{{ ucfirst($incident->severity) }}</td>
                                        <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $incident->status)) }}</span></td>
                                        <td>{{ $incident->incident_date?->format('M d, Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Maintenance Tab -->
        <div class="tab-pane fade" id="maintenance-tabpane" role="tabpanel">
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-4">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="stat-label">Scheduled</div>
                            <div class="stat-value">{{ $stats['maintenances_scheduled'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="stat-label">In Progress</div>
                            <div class="stat-value">{{ $stats['maintenances_in_progress'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="stat-label">Completed</div>
                            <div class="stat-value">{{ $stats['maintenances_completed'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 fleet-report-table">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle datatable">
                            <thead class="table-light">
                                <tr>
                                    <th>Vehicle</th>
                                    <th>Status</th>
                                    <th>Scheduled For</th>
                                    <th>Started At</th>
                                    <th>Completed At</th>
                                    <th>Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tables['maintenances'] as $maintenance)
                                    <tr>
                                        <td class="text-truncate" style="max-width: 100px;">{{ $maintenance->vehicle?->registration_number ?? 'N/A' }}</td>
                                        <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $maintenance->status)) }}</span></td>
                                        <td>{{ $maintenance->scheduled_for?->format('M d, Y') }}</td>
                                        <td>{{ $maintenance->started_at?->format('M d, Y H:i') ?? 'N/A' }}</td>
                                        <td>{{ $maintenance->completed_at?->format('M d, Y H:i') ?? 'N/A' }}</td>
                                        <td>{{ $maintenance->cost !== null ? number_format($maintenance->cost, 2) : 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            const chartData = @json($charts);
            
            // Function to make charts responsive
            const buildDoughnut = (canvasId, labels, values, colors) => {
                const canvas = document.getElementById(canvasId);
                if (!canvas || typeof Chart === 'undefined') return;
                return new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels,
                        datasets: [{
                            data: values,
                            backgroundColor: colors,
                            borderWidth: 0,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: { 
                            legend: { 
                                position: 'bottom',
                                labels: {
                                    font: {
                                        size: window.innerWidth < 768 ? 10 : 12
                                    }
                                }
                            } 
                        },
                        cutout: '65%',
                    }
                });
            };

            const buildBar = (canvasId, labels, values, color) => {
                const canvas = document.getElementById(canvasId);
                if (!canvas || typeof Chart === 'undefined') return;
                return new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Trips',
                            data: values,
                            backgroundColor: color,
                            borderRadius: 8,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { 
                                beginAtZero: true,
                                ticks: {
                                    font: {
                                        size: window.innerWidth < 768 ? 10 : 12
                                    }
                                }
                            },
                            x: {
                                ticks: {
                                    font: {
                                        size: window.innerWidth < 768 ? 10 : 12
                                    }
                                }
                            }
                        }
                    }
                });
            };

            // Initialize charts
            document.addEventListener('DOMContentLoaded', function() {
                buildBar('tripStatusChart', chartData.trip_status.labels, chartData.trip_status.values, '#056CA3');
                buildDoughnut('vehicleStatusChart', chartData.vehicle_status.labels, chartData.vehicle_status.values, ['#16a34a', '#2563eb', '#f59e0b', '#94a3b8']);
                buildDoughnut('driverStatusChart', chartData.driver_status.labels, chartData.driver_status.values, ['#0ea5e9', '#f59e0b', '#ef4444']);
                buildDoughnut('incidentStatusChart', chartData.incident_status.labels, chartData.incident_status.values, ['#f97316', '#facc15', '#22c55e', '#94a3b8']);
                buildBar('branchTripsChart', chartData.branch_trips.labels, chartData.branch_trips.values, '#0ea5e9');
                buildBar('branchDriversChart', chartData.branch_driver_usage.labels, chartData.branch_driver_usage.values, '#6366f1');
                buildBar('branchIncidentsChart', chartData.branch_incidents.labels, chartData.branch_incidents.values, '#f97316');

                // Make DataTables responsive
                document.querySelectorAll('button[data-bs-toggle="tab"]').forEach((tab) => {
                    tab.addEventListener('shown.bs.tab', () => {
                        setTimeout(() => {
                            if (window.jQuery && $.fn.dataTable) {
                                $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                            }
                        }, 100);
                    });
                });
            });
        </script>
    @endpush
</x-admin-layout>