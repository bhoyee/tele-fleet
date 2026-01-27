<x-admin-layout>
    <style>
        /* Dashboard Specific Styles - Mobile First */
        :root {
            --metric-bg: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            --metric-border: #e2e8f0;
            --metric-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --metric-shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        /* Header - Mobile Responsive */
        .dashboard-header {
            padding: 1rem 0;
        }

        @media (min-width: 768px) {
            .dashboard-header {
                padding: 1.5rem 0;
            }
        }

        /* COMPACT Metrics Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        @media (min-width: 480px) {
            .metrics-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.875rem;
            }
        }

        @media (min-width: 768px) {
            .metrics-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 1rem;
            }
        }

        @media (min-width: 1200px) {
            .metrics-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 1rem;
            }
        }

        /* DataTables Responsive Fix */
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter,
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_paginate {
    padding: 1rem 1.25rem;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    font-size: 0.875rem;
}

.dataTables_wrapper .dataTables_filter input {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    border: 1px solid #e2e8f0 !important;
    border-radius: 8px !important;
    padding: 0.375rem 0.75rem !important;
    margin: 0 0.125rem;
    font-size: 0.875rem;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #056CA3 !important;
    color: white !important;
    border-color: #056CA3 !important;
}

        /* COMPACT Metric Cards */
        .metric-card {
            border: 1px solid var(--metric-border);
            border-radius: 12px;
            background: var(--metric-bg);
            box-shadow: var(--metric-shadow);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
            height: 100%;
            padding: 1rem;
            min-height: 100px;
        }

        @media (min-width: 768px) {
            .metric-card {
                padding: 1.25rem;
                min-height: 110px;
                border-radius: 14px;
            }
        }

        .metric-card:hover {
            transform: translateY(-1px);
            box-shadow: var(--metric-shadow-hover);
            border-color: #056CA3;
        }

        .metric-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(5, 108, 163, 0.1) 0%, rgba(5, 108, 163, 0.05) 100%);
            color: #056CA3;
            font-size: 1rem;
            flex-shrink: 0;
        }

        @media (min-width: 768px) {
            .metric-icon {
                width: 42px;
                height: 42px;
                font-size: 1.125rem;
            }
        }

        .metric-label {
            color: #64748b;
            font-weight: 600;
            font-size: 0.75rem;
            letter-spacing: 0.025em;
            margin-bottom: 0.375rem;
            line-height: 1.2;
        }

        @media (min-width: 768px) {
            .metric-label {
                font-size: 0.8125rem;
            }
        }

        .metric-value {
            font-size: 1.25rem;
            font-weight: 800;
            color: #1e293b;
            line-height: 1;
            margin: 0.375rem 0;
        }

        @media (min-width: 768px) {
            .metric-value {
                font-size: 1.5rem;
            }
        }

        @media (min-width: 992px) {
            .metric-value {
                font-size: 1.625rem;
            }
        }

        .metric-footnote {
            color: #94a3b8;
            font-size: 0.6875rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            margin-top: 0.375rem;
        }

        .metric-subcards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.75rem;
            margin-top: 0.5rem;
        }

        .metric-subcard {
            padding: 0.6rem 0.75rem;
            border-radius: 12px;
            background: rgba(5, 108, 163, 0.06);
            border: 1px solid rgba(5, 108, 163, 0.12);
        }

        .metric-subvalue {
            font-weight: 700;
            font-size: 1.25rem;
            color: #1e293b;
            line-height: 1.2;
        }

        .metric-subtitle {
            font-size: 0.7rem;
            color: #94a3b8;
            margin-top: 0.15rem;
        }

        @media (max-width: 576px) {
            .metric-subcards {
                grid-template-columns: 1fr;
            }
        }

        @media (min-width: 577px) and (max-width: 991px) {
            .metric-subcards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        /* COMPACT Calendar */
        .modern-calendar {
            background: white;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: var(--metric-shadow);
            height: 100%;
            display: flex;
            flex-direction: column;
            margin-bottom: 1rem;
        }

        .calendar-header {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem;
        }

        @media (min-width: 768px) {
            .calendar-header {
                padding: 1.125rem;
            }
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #f1f5f9;
            flex: 1;
            min-height: 240px;
        }

        @media (min-width: 768px) {
            .calendar-grid {
                min-height: 260px;
            }
        }

        /* SMALLER calendar on big screens */
        @media (min-width: 992px) {
            .calendar-grid {
                min-height: 220px;
            }
        }

        @media (min-width: 1200px) {
            .calendar-grid {
                min-height: 200px;
            }
        }

        .calendar-day-header {
            background: #f8fafc;
            padding: 0.5rem 0.25rem;
            text-align: center;
            font-weight: 600;
            color: #64748b;
            font-size: 0.6875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        @media (min-width: 768px) {
            .calendar-day-header {
                padding: 0.625rem;
                font-size: 0.75rem;
            }
        }

        .calendar-day {
            background: white;
            padding: 0.375rem;
            border: 1px solid transparent;
            transition: all 0.2s ease;
            position: relative;
            min-height: 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }

        @media (min-width: 768px) {
            .calendar-day {
                padding: 0.5rem;
                min-height: 52px;
            }
        }

        /* SMALLER on big screens */
        @media (min-width: 992px) {
            .calendar-day {
                padding: 0.375rem;
                min-height: 44px;
            }
        }

        @media (min-width: 1200px) {
            .calendar-day {
                padding: 0.375rem;
                min-height: 40px;
            }
        }

        .calendar-day:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            z-index: 1;
        }

        .calendar-day.disabled {
            background: #f8fafc;
            border-color: transparent;
            cursor: default;
        }

        .calendar-day.disabled .day-number,
        .calendar-day.disabled .availability-count {
            color: #cbd5e1;
        }

        .calendar-day.disabled .availability-indicator {
            display: none;
        }

        .calendar-day.today {
            background: linear-gradient(135deg, rgba(5, 108, 163, 0.1) 0%, rgba(5, 108, 163, 0.05) 100%);
            border-color: #056CA3;
        }

        .day-number {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.75rem;
            margin-bottom: 0.125rem;
        }

        .availability-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.125rem;
            width: 100%;
        }

        .availability-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }

        .availability-dot.high {
            background: #10b981;
        }

        .availability-dot.medium {
            background: #f59e0b;
        }

        .availability-dot.low {
            background: #ef4444;
        }

        .availability-count {
            font-size: 0.6875rem;
            color: #64748b;
            font-weight: 500;
            display: none;
        }

        @media (min-width: 480px) {
            .availability-count {
                display: inline;
            }
        }

        /* SMALLER text on big screens */
        @media (min-width: 992px) {
            .day-number {
                font-size: 0.6875rem;
            }
            
            .availability-count {
                font-size: 0.625rem;
            }
        }

        /* Calendar Tooltip - smaller */
        .calendar-tooltip {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #1e293b;
            color: white;
            padding: 0.375rem 0.625rem;
            border-radius: 6px;
            font-size: 0.6875rem;
            white-space: nowrap;
            z-index: 100;
            display: none;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 4px;
        }

        .calendar-tooltip:after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border-width: 4px;
            border-style: solid;
            border-color: #1e293b transparent transparent transparent;
        }

        .calendar-day:hover .calendar-tooltip {
            display: block;
        }

        /* COMPACT Chart Container */
        .chart-container {
            background: white;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            padding: 1rem;
            height: 100%;
            display: flex;
            flex-direction: column;
            min-height: 280px;
            margin-bottom: 1rem;
        }

        @media (min-width: 768px) {
            .chart-container {
                padding: 1.125rem;
                min-height: 300px;
            }
        }

        /* SMALLER chart on big screens */
        @media (min-width: 992px) {
            .chart-container {
                min-height: 260px;
            }
        }

        @media (min-width: 1200px) {
            .chart-container {
                min-height: 240px;
            }
        }

        .fleet-gauge-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .fleet-gauge-card {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 0.75rem;
            background: #f8fafc;
            text-align: center;
        }

        .fleet-gauge-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1e293b;
            margin-top: 0.35rem;
        }

        .fleet-gauge-label {
            font-size: 0.75rem;
            color: #64748b;
        }

        @media (max-width: 992px) {
            .fleet-gauge-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 576px) {
            .fleet-gauge-grid {
                grid-template-columns: 1fr;
            }
        }

        .chart-wrapper {
            position: relative;
            flex: 1;
            min-height: 180px;
        }

        /* SMALLER chart wrapper on big screens */
        @media (min-width: 992px) {
            .chart-wrapper {
                min-height: 160px;
            }
        }

        /* Calendar Controls - COMPACT */
        .calendar-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 0.375rem;
            margin-top: 0.75rem;
        }
        
        .calendar-controls select {
            flex: 1;
            min-width: 100px;
            font-size: 0.8125rem;
            padding: 0.375rem;
        }
        
        .calendar-controls button {
            flex-shrink: 0;
            padding: 0.375rem 0.625rem;
            font-size: 0.8125rem;
        }

        /* COMPACT Table */
        .modern-table-container {
            background: white;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: var(--metric-shadow);
            display: flex;
            flex-direction: column;
            margin-top: 1rem;
        }

        .modern-table-header {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        @media (min-width: 768px) {
            .modern-table-header {
                padding: 1.125rem;
            }
        }

        .table-responsive-mobile {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            width: 100%;
        }

        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 600px;
        }

        .modern-table thead th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
            font-size: 0.6875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.75rem 0.875rem;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }

        @media (min-width: 768px) {
            .modern-table thead th {
                font-size: 0.75rem;
                padding: 0.875rem 1rem;
            }
        }

        .modern-table tbody tr {
            transition: all 0.1s ease;
        }

        .modern-table tbody tr:hover {
            background: #f8fafc;
        }

        .modern-table tbody td {
            padding: 0.75rem 0.875rem;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
            font-weight: 500;
            font-size: 0.8125rem;
            vertical-align: middle;
        }

        @media (min-width: 768px) {
            .modern-table tbody td {
                padding: 0.875rem 1rem;
                font-size: 0.875rem;
            }
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.025em;
            white-space: nowrap;
            display: inline-block;
        }

        .status-requested {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-approved {
            background: #dcfce7;
            color: #166534;
        }

        .status-assigned {
            background: #fef3c7;
            color: #92400e;
        }

        .status-completed {
            background: #f3e8ff;
            color: #6b21a8;
        }

        /* COMPACT Mobile Table */
        @media (max-width: 767px) {
            .modern-table {
                display: block;
                min-width: 100%;
            }
            
            .modern-table thead {
                display: none;
            }
            
            .modern-table tbody,
            .modern-table tr {
                display: block;
                width: 100%;
            }
            
            .modern-table tr {
                margin-bottom: 0.75rem;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                padding: 0.875rem;
                background: white;
            }
            
            .modern-table td {
                display: block;
                padding: 0.375rem 0;
                border: none;
                text-align: left;
                font-size: 0.8125rem;
            }
            
            .modern-table td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #64748b;
                display: block;
                font-size: 0.75rem;
                margin-bottom: 0.125rem;
            }
            
            .status-badge {
                margin-top: 0.125rem;
                font-size: 0.6875rem;
                padding: 0.25rem 0.5rem;
            }
        }

        /* COMPACT Layout Grid */
        .dashboard-layout-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        @media (min-width: 992px) {
            .dashboard-layout-grid {
                grid-template-columns: 1fr 1fr;
                gap: 1.25rem;
            }
        }

        /* Chat Widget Mobile Improvements */
        .chat-widget-button {
            padding: 0.625rem 0.875rem !important;
            font-size: 0.8125rem !important;
            bottom: 16px !important;
            right: 16px !important;
        }

        @media (min-width: 768px) {
            .chat-widget-button {
                padding: 0.75rem 1.125rem !important;
                bottom: 20px !important;
                right: 20px !important;
            }
        }

        /* COMPACT Dashboard Content */
        .dashboard-content {
            padding: 0.75rem;
        }

        @media (min-width: 768px) {
            .dashboard-content {
                padding: 1rem;
            }
        }

        @media (min-width: 992px) {
            .dashboard-content {
                padding: 1.25rem;
            }
        }

        /* Chart Legend - COMPACT */
        .chart-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid #e2e8f0;
            font-size: 0.75rem;
        }

        .chart-legend-item {
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-dot.requested {
            background: #3b82f6;
        }

        .status-dot.approved {
            background: #f59e0b;
        }

        .status-dot.assigned {
            background: #8b5cf6;
        }

        .status-dot.completed {
            background: #10b981;
        }

        .status-dot.rejected {
            background: #ef4444;
        }

        /* Badge sizes */
        .badge-sm {
            padding: 0.1875rem 0.5rem;
            font-size: 0.6875rem;
        }

        /* Reduce chart doughnut size on big screens */
        @media (min-width: 992px) {
            #tripStatusChart {
                max-height: 180px !important;
            }
        }

        @media (min-width: 1200px) {
            #tripStatusChart {
                max-height: 260px !important;
            }
        }
    </style>

    <div class="dashboard-content">
        <!-- COMPACT Header -->
        <div class="dashboard-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-2 gap-2">
                <div>
                    <h1 class="h4 mb-1 fw-bold text-dark">Dashboard</h1>
                    <p class="text-muted mb-0" style="font-size: 0.875rem;">Welcome back, {{ auth()->user()->name }}</p>
                </div>
                <div class="text-muted" style="font-size: 0.875rem;">
                    <div class="badge bg-primary bg-opacity-10 text-primary px-2 py-1">
                        <i class="bi bi-clock me-1"></i> {{ now()->format('M d, Y') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- COMPACT Metrics Cards -->
        <div class="metrics-grid">
            <!-- Available Vehicles -->
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="metric-icon"><i class="bi bi-car-front"></i></span>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 badge-sm">Fleet</span>
                </div>
                <div class="metric-label">Available Vehicles</div>
                <div class="metric-value">
                    <span data-metric="availableVehicles">{{ $availableVehicles ?? 0 }}</span>
                    /
                    <span data-metric="totalVehicles">{{ $totalVehicles ?? 0 }}</span>
                </div>
                <div class="metric-footnote">
                    <i class="bi bi-check-circle"></i> Ready for assignment
                </div>
            </div>

            <!-- Personal Trip Requests -->
            @if (!is_null($personalTripRequests))
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="metric-icon"><i class="bi bi-clipboard-check"></i></span>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 badge-sm">Month</span>
                </div>
                <div class="metric-label">Trip Requests</div>
                <div class="metric-value" data-metric="personalTripRequests">{{ $personalTripRequests }}</div>
                <div class="metric-footnote">
                    <i class="bi bi-calendar-month"></i> This month
                </div>
            </div>
            @endif

            @if (!is_null($branchTripRequests))
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="metric-icon"><i class="bi bi-journal-text"></i></span>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 badge-sm">Branch</span>
                </div>
                <div class="metric-label">Trip Requests</div>
                <div class="metric-value" data-metric="branchTripRequests">{{ $branchTripRequests }}</div>
                <div class="metric-footnote">
                    <i class="bi bi-calendar-month"></i> This month
                </div>
            </div>
            @endif

            @if (!is_null($branchCompletedTrips))
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="metric-icon"><i class="bi bi-check-circle"></i></span>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 badge-sm">Branch</span>
                </div>
                <div class="metric-label">Trip Completions</div>
                <div class="metric-value" data-metric="branchCompletedTrips">{{ $branchCompletedTrips }}</div>
                <div class="metric-footnote">
                    <i class="bi bi-calendar-check"></i> Completed this month
                </div>
            </div>
            @endif

            @if (!is_null($branchRejectedTrips))
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="metric-icon"><i class="bi bi-x-circle"></i></span>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 badge-sm">Branch</span>
                </div>
                <div class="metric-label">Rejected Trips</div>
                <div class="metric-value" data-metric="branchRejectedTrips">{{ $branchRejectedTrips }}</div>
                <div class="metric-footnote">
                    <i class="bi bi-calendar-x"></i> Rejected this month
                </div>
            </div>
            @endif

            <!-- Drivers On Duty -->
            @if (!is_null($driversOnDuty))
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="metric-icon"><i class="bi bi-person-badge"></i></span>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 badge-sm">Today</span>
                </div>
                <div class="metric-label">Drivers On Duty</div>
                <div class="metric-subcards">
                    <div class="metric-subcard">
                        <div class="metric-subvalue" data-metric="driversAssignedToday">{{ $driversAssignedToday ?? 0 }}</div>
                        <div class="metric-subtitle">Assigned today</div>
                    </div>
                    <div class="metric-subcard">
                        <div class="metric-subvalue" data-metric="driversUnassignedToday">{{ $driversUnassignedToday ?? 0 }}</div>
                        <div class="metric-subtitle">Unassigned</div>
                    </div>
                    <div class="metric-subcard">
                        <div class="metric-subvalue" data-metric="totalDriversRegistered">{{ $totalDriversRegistered ?? 0 }}</div>
                        <div class="metric-subtitle">Drivers registered</div>
                    </div>
                </div>
                <div class="metric-footnote">
                    <i class="bi bi-person-check"></i> Active driver coverage
                </div>
            </div>
            @endif

            @if (!is_null($todayActiveTrips) || !is_null($futureTrips))
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="metric-icon"><i class="bi bi-activity"></i></span>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 badge-sm">Trips</span>
                </div>
                <div class="metric-label">Trip Activity</div>
                <div class="metric-subcards">
                    <div class="metric-subcard">
                        <div class="metric-subvalue" data-metric="todayActiveTrips">{{ $todayActiveTrips ?? 0 }}</div>
                        <div class="metric-subtitle">Today active</div>
                    </div>
                    <div class="metric-subcard">
                        <div class="metric-subvalue" data-metric="futureTrips">{{ $futureTrips ?? 0 }}</div>
                        <div class="metric-subtitle">Future trips</div>
                    </div>
                    <div class="metric-subcard">
                        <div class="metric-subvalue" data-metric="unassignedTrips">{{ $unassignedTrips ?? 0 }}</div>
                        <div class="metric-subtitle">Unassigned</div>
                    </div>
                </div>
                <div class="metric-footnote">
                    <i class="bi bi-graph-up"></i> Approved &amp; assigned pipeline
                </div>
            </div>
            @endif

            <!-- Trips This Month -->
            @if (!is_null($monthTripsTotal))
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="metric-icon"><i class="bi bi-calendar-week"></i></span>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 badge-sm">Month</span>
                </div>
                <div class="metric-label">Trips This Month</div>
                <div class="metric-subcards">
                    <div class="metric-subcard">
                        <div class="metric-subvalue" data-metric="monthTripsCompleted">{{ $monthTripsCompleted ?? 0 }}</div>
                        <div class="metric-subtitle">Completed</div>
                    </div>
                    <div class="metric-subcard">
                        <div class="metric-subvalue" data-metric="monthTripsRejected">{{ $monthTripsRejected ?? 0 }}</div>
                        <div class="metric-subtitle">Rejected</div>
                    </div>
                    <div class="metric-subcard">
                        <div class="metric-subvalue" data-metric="monthTripsAssigned">{{ $monthTripsAssigned ?? 0 }}</div>
                        <div class="metric-subtitle">Assigned</div>
                    </div>
                </div>
                <div class="metric-footnote">
                    <i class="bi bi-graph-up"></i> Monthly summary
                </div>
            </div>
            @endif

            <!-- Pending Approval -->
            @if (!is_null($pendingApproval))
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="metric-icon"><i class="bi bi-hourglass-split"></i></span>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 badge-sm">Pending</span>
                </div>
                <div class="metric-label">Pending Approval</div>
                <div class="metric-value" data-metric="pendingApproval">{{ $pendingApproval }}</div>
                <div class="metric-footnote">
                    <i class="bi bi-clock-history"></i> Awaiting action
                </div>
            </div>
            @endif

            <!-- Incident Reports -->
            @if (!is_null($incidentReports))
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="metric-icon"><i class="bi bi-exclamation-triangle"></i></span>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 badge-sm">Risk</span>
                </div>
                <div class="metric-label">Incident Reports</div>
                <div class="metric-subcards">
                    <div class="metric-subcard">
                        <div class="metric-subvalue" data-metric="incidentOpen">{{ $incidentOpen ?? 0 }}</div>
                        <div class="metric-subtitle">Open</div>
                    </div>
                    <div class="metric-subcard">
                        <div class="metric-subvalue" data-metric="incidentReview">{{ $incidentReview ?? 0 }}</div>
                        <div class="metric-subtitle">Under review</div>
                    </div>
                    <div class="metric-subcard">
                        <div class="metric-subvalue" data-metric="incidentResolved">{{ $incidentResolved ?? 0 }}</div>
                        <div class="metric-subtitle">Resolved</div>
                    </div>
                </div>
                <div class="metric-footnote">
                    <i class="bi bi-shield-exclamation"></i> Under review
                </div>
            </div>
            @endif

            <!-- Maintenance Due -->
            @if (!is_null($maintenanceDue))
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="metric-icon"><i class="bi bi-tools"></i></span>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 badge-sm">Service</span>
                </div>
                <div class="metric-label">Maintenance Due</div>
                <div class="metric-subcards">
                    <div class="metric-subcard">
                        <div class="metric-subvalue" data-metric="maintenanceDue">{{ $maintenanceDue ?? 0 }}</div>
                        <div class="metric-subtitle">Due</div>
                    </div>
                    <div class="metric-subcard">
                        <div class="metric-subvalue" data-metric="maintenanceInProgress">{{ $maintenanceInProgress ?? 0 }}</div>
                        <div class="metric-subtitle">In maintenance</div>
                    </div>
                </div>
                <div class="metric-footnote">
                    <i class="bi bi-calendar-check"></i> Current workload
                </div>
            </div>
            @endif

            @if (!is_null($tripsToday))
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="metric-icon"><i class="bi bi-calendar3"></i></span>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 badge-sm">Trips</span>
                </div>
                <div class="metric-label">Trip Requests</div>
                <div class="metric-subcards">
                    <div class="metric-subcard">
                        <div class="metric-subvalue" data-metric="tripsToday">{{ $tripsToday ?? 0 }}</div>
                        <div class="metric-subtitle">Today</div>
                    </div>
                    <div class="metric-subcard">
                        <div class="metric-subvalue" data-metric="tripsThisWeek">{{ $tripsThisWeek ?? 0 }}</div>
                        <div class="metric-subtitle">This week</div>
                    </div>
                    <div class="metric-subcard">
                        <div class="metric-subvalue" data-metric="tripsThisMonth">{{ $tripsThisMonth ?? 0 }}</div>
                        <div class="metric-subtitle">This month</div>
                    </div>
                </div>
                <div class="metric-footnote">
                    <i class="bi bi-graph-up"></i> Total requests
                </div>
            </div>
            @endif

            @if (!is_null($uncompletedTrips))
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="metric-icon"><i class="bi bi-clipboard-x"></i></span>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 badge-sm">Active</span>
                </div>
                <div class="metric-label">Uncompleted Trips</div>
                <div class="metric-subcards">
                    <div class="metric-subcard">
                        <div class="metric-subvalue" data-metric="dueTrips">{{ $dueTrips ?? 0 }}</div>
                        <div class="metric-subtitle">Due</div>
                    </div>
                    <div class="metric-subcard">
                        <div class="metric-subvalue" data-metric="overdueTrips">{{ $overdueTrips ?? 0 }}</div>
                        <div class="metric-subtitle">Overdue</div>
                    </div>
                    <div class="metric-subcard">
                        <div class="metric-subvalue" data-metric="uncompletedTrips">{{ $uncompletedTrips ?? 0 }}</div>
                        <div class="metric-subtitle">Total active</div>
                    </div>
                </div>
                <div class="metric-footnote">
                    <i class="bi bi-flag"></i> Based on estimated trip days
                </div>
            </div>
            @endif
        </div>

        @php
            $showBranchCharts = in_array(auth()->user()->role, [
                \App\Models\User::ROLE_BRANCH_ADMIN,
                \App\Models\User::ROLE_BRANCH_HEAD,
                \App\Models\User::ROLE_FLEET_MANAGER,
                \App\Models\User::ROLE_SUPER_ADMIN,
            ], true);
            $showFleetOverview = in_array(auth()->user()->role, [
                \App\Models\User::ROLE_FLEET_MANAGER,
                \App\Models\User::ROLE_SUPER_ADMIN,
            ], true);
            $showTripStatusChart = in_array(auth()->user()->role, [
                \App\Models\User::ROLE_BRANCH_ADMIN,
                \App\Models\User::ROLE_BRANCH_HEAD,
            ], true);
        @endphp

        @if ($showBranchCharts)
        <!-- COMPACT Calendar & Chart Section -->
        <div class="dashboard-layout-grid">
            <!-- COMPACT Calendar -->
            <div class="modern-calendar">
                <div class="calendar-header">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-2 gap-2">
                        <div>
                            <h5 class="fw-bold mb-1" style="font-size: 0.9375rem;">Vehicle Availability</h5>
                            <p class="text-muted mb-0" style="font-size: 0.75rem;">Calendar view</p>
                        </div>
                        <div class="text-end">
                        <span class="fw-bold text-dark" id="availabilityMonth" style="font-size: 0.875rem;">{{ now()->format('F Y') }}</span>
                        </div>
                    </div>
                    <div class="calendar-controls">
                        <button class="btn btn-sm btn-outline-primary" type="button" id="calendarPrev">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <select class="form-select form-select-sm" id="calendarMonthSelect">
                            @php
                                $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                                          'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                            @endphp
                            @foreach($months as $index => $month)
                                <option value="{{ $index + 1 }}" {{ (now()->format('n') == $index + 1) ? 'selected' : '' }}>
                                    {{ $month }}
                                </option>
                            @endforeach
                        </select>
                        <select class="form-select form-select-sm" id="calendarYearSelect">
                            @php
                                $currentYear = now()->format('Y');
                                $minYear = $calendarMinYear ?? $currentYear;
                                $maxYear = $calendarMaxYear ?? ($currentYear + 1);
                            @endphp
                            @for($year = $minYear; $year <= $maxYear; $year++)
                                <option value="{{ $year }}" {{ $currentYear == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endfor
                        </select>
                        <button class="btn btn-sm btn-outline-primary" type="button" id="calendarNext">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <div class="calendar-grid" id="calendarGrid">
                    <!-- Calendar will be populated by JavaScript -->
                </div>
            </div>

            <!-- COMPACT Chart -->
            <div class="chart-container">
                @if ($showFleetOverview)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="fw-bold mb-1" style="font-size: 0.9375rem;">Fleet Status Overview</h5>
                            <p class="text-muted mb-0" style="font-size: 0.75rem;">Current fleet availability</p>
                        </div>
                        <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 badge-sm">Live</span>
                    </div>
                    <div class="fleet-gauge-grid">
                        <div class="fleet-gauge-card">
                            <canvas id="fleetGaugeAvailable"></canvas>
                            <div class="fleet-gauge-value" data-metric="vehiclesAvailable">{{ $vehiclesAvailable ?? 0 }}</div>
                            <div class="fleet-gauge-label">Available</div>
                        </div>
                        <div class="fleet-gauge-card">
                            <canvas id="fleetGaugeInUse"></canvas>
                            <div class="fleet-gauge-value" data-metric="vehiclesInUse">{{ $vehiclesInUse ?? 0 }}</div>
                            <div class="fleet-gauge-label">In use</div>
                        </div>
                        <div class="fleet-gauge-card">
                            <canvas id="fleetGaugeMaintenance"></canvas>
                            <div class="fleet-gauge-value" data-metric="vehiclesMaintenance">{{ $vehiclesMaintenance ?? 0 }}</div>
                            <div class="fleet-gauge-label">Maintenance</div>
                        </div>
                    </div>
                @elseif ($showTripStatusChart)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="fw-bold mb-1" style="font-size: 0.9375rem;">Trip Status</h5>
                            <p class="text-muted mb-0" style="font-size: 0.75rem;">Current distribution</p>
                        </div>
                        <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 badge-sm">Current Month</span>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="tripStatusChart"></canvas>
                    </div>
                    <div class="chart-legend">
                        <div class="chart-legend-item">
                            <div class="status-dot requested"></div>
                            <small class="text-muted">Requested</small>
                        </div>
                        <div class="chart-legend-item">
                            <div class="status-dot approved"></div>
                            <small class="text-muted">Approved</small>
                        </div>
                        <div class="chart-legend-item">
                            <div class="status-dot assigned"></div>
                            <small class="text-muted">Assigned</small>
                        </div>
                        <div class="chart-legend-item">
                            <div class="status-dot completed"></div>
                            <small class="text-muted">Completed</small>
                        </div>
                        <div class="chart-legend-item">
                            <div class="status-dot rejected"></div>
                            <small class="text-muted">Rejected</small>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- COMPACT Pending Trips Table -->
        <div class="modern-table-container">
            <div class="modern-table-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-1" style="font-size: 0.9375rem;">Pending Trips</h5>
                        <p class="text-muted mb-0" style="font-size: 0.75rem;">Pending approval and assignment</p>
                    </div>
                </div>
            </div>
            <div class="table-responsive-mobile">
                <table class="modern-table datatable" id="upcomingTripsTable">
                    <thead>
                        <tr>
                            <th>Request #</th>
                            <th>Trip Date</th>
                            <th>Time</th>
                            <th>Destination</th>
                            <th>Status</th>
                            <th class="d-none d-md-table-cell">Vehicle</th>
                            <th class="d-none d-lg-table-cell">Driver</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($upcomingTrips as $trip)
                            <tr>
                                <td data-label="Request #">
                                    <div class="fw-bold">{{ $trip->request_number }}</div>
                                </td>
                                <td data-label="Trip Date">
                                    <div class="fw-bold">{{ optional($trip->trip_date)->format('M d') }}</div>
                                    <small class="text-muted" style="font-size: 0.75rem;">{{ optional($trip->trip_date)->format('D') }}</small>
                                </td>
                                <td data-label="Time">
                                    @php
                                        $tripTime = $trip->trip_time;
                                        if ($tripTime) {
                                            try {
                                                $tripTime = \Illuminate\Support\Carbon::parse($tripTime)->format('g:i A');
                                            } catch (\Exception $e) {
                                                $tripTime = \Illuminate\Support\Carbon::parse($trip->trip_time)->format('g:i A');
                                            }
                                        }
                                    @endphp
                                    <div class="fw-medium">{{ $tripTime ?: 'N/A' }}</div>
                                </td>
                                <td data-label="Destination" class="fw-medium">
                                    {{ Str::limit($trip->destination, 20) }}
                                </td>
                                <td data-label="Status">
                                    @php
                                        $statusClass = match(strtolower($trip->status)) {
                                            'requested' => 'status-requested',
                                            'approved' => 'status-approved',
                                            'assigned' => 'status-assigned',
                                            'completed' => 'status-completed',
                                            default => 'bg-light text-dark'
                                        };
                                    @endphp
                                    <span class="status-badge {{ $statusClass }}">
                                        {{ $trip->status }}
                                    </span>
                                </td>
                                <td data-label="Vehicle" class="d-none d-md-table-cell">
                                    <div class="fw-medium">{{ $trip->assignedVehicle?->registration_number ?? '—' }}</div>
                                    @if($trip->assignedVehicle?->model)
                                        <small class="text-muted d-none d-lg-inline" style="font-size: 0.75rem;">{{ $trip->assignedVehicle->model }}</small>
                                    @endif
                                </td>
                                <td data-label="Driver" class="d-none d-lg-table-cell">
                                    {{ $trip->assignedDriver?->full_name ?? '—' }}
                                </td>
                                <td class="text-end" data-label="Action">
                                    @if (in_array(auth()->user()->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true))
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('trips.show', $trip) }}" data-loading>View</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-calendar-x fs-4"></i>
                                        <p class="mt-2 mb-0" style="font-size: 0.875rem;">No pending trips found</p>
                                    </div>
                                </td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="d-none d-md-table-cell"></td>
                                <td class="d-none d-lg-table-cell"></td>
                                <td class="text-end"></td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            // Initialize variables
            const metricElements = document.querySelectorAll('[data-metric]');
            const metricUrl = "{{ route('dashboard.metrics') }}";
            const calendarUrl = "{{ route('dashboard.calendar') }}";
            const tripStatusUrl = "{{ route('dashboard.trip-status') }}";
            const upcomingTripsUrl = "{{ route('dashboard.upcoming-trips') }}";
            const tripShowUrlTemplate = "{{ route('trips.show', '__ID__') }}";
            const showBranchCharts = {{ $showBranchCharts ? 'true' : 'false' }};
            const showFleetOverview = {{ $showFleetOverview ? 'true' : 'false' }};
            const showTripStatusChart = {{ $showTripStatusChart ? 'true' : 'false' }};
            
            let selectedMonth = new Date().getMonth() + 1;
            let selectedYear = new Date().getFullYear();
            let tripChart = null;
            let fleetGaugeCharts = {};

            // Update metrics function
            const updateMetrics = async () => {
                try {
                    const response = await fetch(metricUrl, { 
                        headers: { 'Accept': 'application/json' } 
                    });
                    if (!response.ok) return;
                    const data = await response.json();
                    metricElements.forEach((el) => {
                        const key = el.dataset.metric;
                        if (data[key] !== null && data[key] !== undefined) {
                            const suffix = el.dataset.suffix ?? '';
                            el.textContent = `${data[key]}${suffix}`;
                        }
                    });
                    if (showFleetOverview) {
                        updateFleetGauges(data);
                    }
                } catch (error) {
                    console.warn('Dashboard metrics refresh failed.');
                }
            };

            const createGaugeChart = (canvasId, color) => {
                const canvas = document.getElementById(canvasId);
                if (!canvas || typeof Chart === 'undefined') {
                    return null;
                }
                return new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        datasets: [{
                            data: [0, 1],
                            backgroundColor: [color, '#e5e7eb'],
                            borderWidth: 0,
                        }],
                    },
                    options: {
                        cutout: '78%',
                        rotation: -90,
                        circumference: 180,
                        plugins: {
                            legend: { display: false },
                            tooltip: { enabled: false },
                        },
                    },
                });
            };

            const initFleetGauges = () => {
                if (!showFleetOverview) {
                    return;
                }
                fleetGaugeCharts.available = createGaugeChart('fleetGaugeAvailable', '#16a34a');
                fleetGaugeCharts.inUse = createGaugeChart('fleetGaugeInUse', '#2563eb');
                fleetGaugeCharts.maintenance = createGaugeChart('fleetGaugeMaintenance', '#f59e0b');
            };

            const updateFleetGauges = (data) => {
                const total = Number(data.totalVehicles ?? 0);
                if (!total) {
                    return;
                }
                const available = Number(data.vehiclesAvailable ?? 0);
                const inUse = Number(data.vehiclesInUse ?? 0);
                const maintenance = Number(data.vehiclesMaintenance ?? 0);

                const updateGauge = (chart, value) => {
                    if (!chart) return;
                    chart.data.datasets[0].data = [value, Math.max(0, total - value)];
                    chart.update();
                };

                updateGauge(fleetGaugeCharts.available, available);
                updateGauge(fleetGaugeCharts.inUse, inUse);
                updateGauge(fleetGaugeCharts.maintenance, maintenance);
            };

            // Build COMPACT calendar with hover tooltips
            const buildCalendar = (days, maxAvailable) => {
                const calendarGrid = document.getElementById('calendarGrid');
                if (!calendarGrid) return;
                
                // Clear existing content
                calendarGrid.innerHTML = '';
                
                // Add day headers for mobile
                const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                dayHeaders.forEach(day => {
                    const header = document.createElement('div');
                    header.className = 'calendar-day-header';
                    header.textContent = day;
                    calendarGrid.appendChild(header);
                });
                
                // Get today's date
                const today = new Date();
                const todayFormatted = today.getFullYear() + '-' + 
                    String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                    String(today.getDate()).padStart(2, '0');
                
                // Add days to calendar
                days.forEach((day) => {
                    const tile = document.createElement('div');
                    tile.className = 'calendar-day';
                    
                    if (day.date === todayFormatted) {
                        tile.classList.add('today');
                    }
                    
                    const availableRaw = day.available;
                    if (availableRaw === null) {
                        tile.classList.add('disabled');
                    }
                    const available = Number(availableRaw ?? 0);
                    const maxValue = Number(maxAvailable ?? 0) || 1;
                    const ratio = Math.min(1, available / maxValue);
                    
                    let dotClass = 'high';
                    if (ratio >= 0.7) {
                        dotClass = 'high';
                    } else if (ratio >= 0.4) {
                        dotClass = 'medium';
                    } else {
                        dotClass = 'low';
                    }
                    
                    const dateParts = day.date.split('-');
                    const dayNumber = parseInt(dateParts[2]);
                    
                    if (availableRaw === null) {
                        tile.innerHTML = `<div class="day-number">${dayNumber}</div>`;
                        calendarGrid.appendChild(tile);
                        return;
                    }

                    // Create tooltip
                    const tooltip = document.createElement('div');
                    tooltip.className = 'calendar-tooltip';
                    tooltip.textContent = `${available} available vehicles`;

                    tile.innerHTML = `
                        <div class="day-number">${dayNumber}</div>
                        <div class="availability-indicator">
                            <div class="availability-dot ${dotClass}"></div>
                            <span class="availability-count">${available}</span>
                        </div>
                    `;

                    tile.appendChild(tooltip);
                    calendarGrid.appendChild(tile);
                });
            };

            // Initialize and update COMPACT chart
            const initializeChart = (data) => {
                const ctx = document.getElementById('tripStatusChart');
                if (!ctx) return;
                
                // Destroy existing chart if it exists
                if (tripChart) {
                    tripChart.destroy();
                }
                
                const chartData = [
                    data.pending || 0,
                    data.approved || 0,
                    data.assigned || 0,
                    data.completed || 0,
                    data.rejected || 0
                ];
                
                // Create responsive chart with SMALLER size
                tripChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Requested', 'Approved', 'Assigned', 'Completed', 'Rejected'],
                        datasets: [{
                            data: chartData,
                            backgroundColor: [
                                '#3b82f6', // Requested - Blue
                                '#f59e0b', // Approved - Amber
                                '#8b5cf6', // Assigned - Purple
                                '#10b981', // Completed - Green
                                '#ef4444'  // Rejected - Red
                            ],
                            borderWidth: 0,
                            hoverOffset: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `${context.label}: ${context.raw} trips`;
                                    }
                                },
                                bodyFont: {
                                    size: 12
                                },
                                titleFont: {
                                    size: 12
                                }
                            }
                        },
                        cutout: '50%'
                    }
                });
            };

            // Update calendar data
            const updateCalendar = async () => {
                if (!showBranchCharts) return;
                
                try {
                    const query = new URLSearchParams({ 
                        month: selectedMonth, 
                        year: selectedYear 
                    });
                    const response = await fetch(`${calendarUrl}?${query.toString()}`, { 
                        headers: { 'Accept': 'application/json' } 
                    });
                    if (!response.ok) return;
                    
                    const data = await response.json();
                    const availabilityMonth = document.getElementById('availabilityMonth');
                if (availabilityMonth && data.month && data.year) {
                    availabilityMonth.textContent = `${data.month} ${data.year}`;
                }
                    
                    if (data.year && data.month_number) {
                        selectedYear = data.year;
                        selectedMonth = data.month_number;
                    }
                    
                    // Update select elements
                    const monthSelect = document.getElementById('calendarMonthSelect');
                    const yearSelect = document.getElementById('calendarYearSelect');
                    if (monthSelect) monthSelect.value = selectedMonth;
                    if (yearSelect) yearSelect.value = selectedYear;
                    
                    buildCalendar(data.days || [], data.max_available || 0);
                } catch (error) {
                    console.warn('Calendar refresh failed.');
                }
            };

            // Update trip status data
            const updateTripStatus = async () => {
                if (!showTripStatusChart) return;
                
                try {
                    const response = await fetch(tripStatusUrl, { 
                        headers: { 'Accept': 'application/json' } 
                    });
                    if (!response.ok) return;
                    const data = await response.json();
                    initializeChart(data);
                } catch (error) {
                    console.warn('Trip status refresh failed.');
                }
            };

            const updateUpcomingTrips = async () => {
                if (!showBranchCharts) return;
                const table = document.getElementById('upcomingTripsTable');
                if (!table) return;

                try {
                    const response = await fetch(upcomingTripsUrl, { headers: { 'Accept': 'application/json' } });
                    if (!response.ok) return;
                    const payload = await response.json();
                    const rows = payload.data || [];
                    const tbody = table.querySelector('tbody');
                    if (!tbody) return;

                    const escaped = (value) => String(value ?? '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');

                    if (window.jQuery && window.jQuery.fn.dataTable && window.jQuery.fn.dataTable.isDataTable(table)) {
                        window.jQuery(table).DataTable().destroy();
                    }

                    if (!rows.length) {
                        tbody.innerHTML = `
                            <tr>
                                <td class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-calendar-x fs-4"></i>
                                        <p class="mt-2 mb-0" style="font-size: 0.875rem;">No pending trips found</p>
                                    </div>
                                </td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="d-none d-md-table-cell"></td>
                                <td class="d-none d-lg-table-cell"></td>
                                <td class="text-end"></td>
                            </tr>
                        `;
                    } else {
                        const canView = ['super_admin', 'fleet_manager'].includes('{{ auth()->user()->role }}');
                        tbody.innerHTML = rows.map((trip) => {
                            const status = String(trip.status ?? '');
                            const statusClass = status.toLowerCase() === 'requested'
                                ? 'status-requested'
                                : (status.toLowerCase() === 'approved'
                                    ? 'status-approved'
                                    : (status.toLowerCase() === 'assigned'
                                        ? 'status-assigned'
                                        : (status.toLowerCase() === 'completed'
                                            ? 'status-completed'
                                            : 'bg-light text-dark')));

                            return `
                                <tr>
                                    <td data-label="Request #">
                                        <div class="fw-bold">${escaped(trip.request_number)}</div>
                                    </td>
                                    <td data-label="Trip Date">
                                        <div class="fw-bold">${escaped(trip.trip_date)}</div>
                                        <small class="text-muted" style="font-size: 0.75rem;">${escaped(trip.trip_day)}</small>
                                    </td>
                                    <td data-label="Time">
                                        <div class="fw-medium">${escaped(trip.trip_time)}</div>
                                    </td>
                                    <td data-label="Destination" class="fw-medium">
                                        ${escaped(trip.destination)}
                                    </td>
                                    <td data-label="Status">
                                        <span class="status-badge ${statusClass}">
                                            ${escaped(status)}
                                        </span>
                                    </td>
                                    <td data-label="Vehicle" class="d-none d-md-table-cell">
                                        <div class="fw-medium">${escaped(trip.vehicle)}</div>
                                        ${trip.vehicle_model ? `<small class="text-muted d-none d-lg-inline" style="font-size: 0.75rem;">${escaped(trip.vehicle_model)}</small>` : ''}
                                    </td>
                                    <td data-label="Driver" class="d-none d-lg-table-cell">
                                        ${escaped(trip.driver)}
                                    </td>
                                    <td class="text-end" data-label="Action">
                                        ${canView ? `<a class="btn btn-sm btn-outline-primary" href="${tripShowUrlTemplate.replace('__ID__', trip.id)}" data-loading>View</a>` : '<span class="text-muted">—</span>'}
                                    </td>
                                </tr>
                            `;
                        }).join('');
                    }

                    if (window.jQuery && window.jQuery.fn.dataTable) {
                        window.jQuery(table).DataTable({
                            pageLength: 10,
                            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
                            order: [],
                            searching: true,
                            paging: true,
                            info: true,
                        });
                    }
                } catch (error) {
                    console.warn('Upcoming trips refresh failed.');
                }
            };

            // Initialize dashboard if branch charts should be shown
            if (showBranchCharts) {
                document.addEventListener('DOMContentLoaded', function() {
                    initFleetGauges();
                    // Calendar controls
                    const monthSelect = document.getElementById('calendarMonthSelect');
                    const yearSelect = document.getElementById('calendarYearSelect');
                    const prevBtn = document.getElementById('calendarPrev');
                    const nextBtn = document.getElementById('calendarNext');
                    
                    // Month/year selector changes
                    if (monthSelect) {
                        monthSelect.addEventListener('change', () => {
                            selectedMonth = Number(monthSelect.value);
                            updateCalendar();
                        });
                    }
                    
                    if (yearSelect) {
                        yearSelect.addEventListener('change', () => {
                            selectedYear = Number(yearSelect.value);
                            updateCalendar();
                        });
                    }
                    
                    // Calendar navigation buttons
                    if (prevBtn) {
                        prevBtn.addEventListener('click', () => {
                            let newMonth = selectedMonth - 1;
                            let newYear = selectedYear;
                            if (newMonth < 1) {
                                newMonth = 12;
                                newYear -= 1;
                            }
                            selectedMonth = newMonth;
                            selectedYear = newYear;
                            updateCalendar();
                        });
                    }
                    
                    if (nextBtn) {
                        nextBtn.addEventListener('click', () => {
                            let newMonth = selectedMonth + 1;
                            let newYear = selectedYear;
                            if (newMonth > 12) {
                                newMonth = 1;
                                newYear += 1;
                            }
                            selectedMonth = newMonth;
                            selectedYear = newYear;
                            updateCalendar();
                        });
                    }
                    
                    // Initialize data
                    updateCalendar();
                    updateTripStatus();
                    updateUpcomingTrips();
                    
                    // Auto-refresh
                    setInterval(updateMetrics, 30000);
                    setInterval(updateCalendar, 60000);
                    setInterval(updateTripStatus, 60000);
                    setInterval(updateUpcomingTrips, 60000);
                });
            }

            // Make chart responsive on window resize
            window.addEventListener('resize', function() {
                if (tripChart) {
                    tripChart.resize();
                }
            });

            // Initialize metrics on page load
            updateMetrics();
        </script>
    @endpush
</x-admin-layout>


