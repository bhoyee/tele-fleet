<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Dashboard</h1>
            <p class="text-muted mb-0">Welcome back, {{ auth()->user()->name }}.</p>
        </div>
        <div class="text-muted small">
            {{ now()->format('M d, Y') }}
        </div>
    </div>

    <style>
        .metric-card {
            border: 1px solid rgba(5, 108, 163, 0.12);
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(5, 108, 163, 0.08), rgba(5, 108, 163, 0));
            box-shadow: 0 12px 24px rgba(5, 108, 163, 0.08);
            position: relative;
            overflow: hidden;
        }

        .metric-card::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 120px;
            height: 120px;
            background: radial-gradient(circle, rgba(5, 108, 163, 0.2), transparent 60%);
            opacity: 0.6;
        }

        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(5, 108, 163, 0.15);
            color: #056CA3;
            font-size: 1.35rem;
        }

        .metric-label {
            color: #4b5563;
            font-weight: 600;
        }

        .metric-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: #0f172a;
        }

        .metric-footnote {
            color: #6b7280;
            font-size: 0.85rem;
        }
    </style>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card metric-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="metric-icon"><i class="bi bi-car-front"></i></span>
                        <span class="badge bg-light text-primary">Fleet</span>
                    </div>
                    <div class="metric-label">Available Vehicles (Current)</div>
                    <div class="metric-value" data-metric="availableVehicles">{{ $availableVehicles }}</div>
                    <div class="metric-footnote">Ready for assignment.</div>
                </div>
            </div>
        </div>

        @if (! is_null($personalTripRequests))
            <div class="col-md-4">
                <div class="card metric-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="metric-icon"><i class="bi bi-clipboard-check"></i></span>
                            <span class="badge bg-light text-primary">This Month</span>
                        </div>
                        <div class="metric-label">Total Personal Trip Requests</div>
                        <div class="metric-value" data-metric="personalTripRequests">{{ $personalTripRequests }}</div>
                        <div class="metric-footnote">Requests logged this month.</div>
                    </div>
                </div>
            </div>
        @endif

        @if (! is_null($driversOnDuty))
            <div class="col-md-4">
                <div class="card metric-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="metric-icon"><i class="bi bi-person-badge"></i></span>
                            <span class="badge bg-light text-primary">Operations</span>
                        </div>
                        <div class="metric-label">Drivers On Duty</div>
                        <div class="metric-value" data-metric="driversOnDuty">{{ $driversOnDuty }}</div>
                        <div class="metric-footnote">Active driver assignments.</div>
                    </div>
                </div>
            </div>
        @endif

        @if (! is_null($tripsThisWeek))
            <div class="col-md-4">
                <div class="card metric-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="metric-icon"><i class="bi bi-calendar-week"></i></span>
                            <span class="badge bg-light text-primary">Week</span>
                        </div>
                        <div class="metric-label">Trips This Week</div>
                        <div class="metric-value" data-metric="tripsThisWeek">{{ $tripsThisWeek }}</div>
                        <div class="metric-footnote">Week-to-date activity.</div>
                    </div>
                </div>
            </div>
        @endif

        @if (! is_null($pendingApproval))
            <div class="col-md-4">
                <div class="card metric-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="metric-icon"><i class="bi bi-hourglass-split"></i></span>
                            <span class="badge bg-light text-primary">Queue</span>
                        </div>
                        <div class="metric-label">Pending Approval</div>
                        <div class="metric-value" data-metric="pendingApproval">{{ $pendingApproval }}</div>
                        <div class="metric-footnote">Awaiting action.</div>
                    </div>
                </div>
            </div>
        @endif

        @if (! is_null($incidentReports))
            <div class="col-md-4">
                <div class="card metric-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="metric-icon"><i class="bi bi-exclamation-triangle"></i></span>
                            <span class="badge bg-light text-primary">Risk</span>
                        </div>
                        <div class="metric-label">Incident Reports</div>
                        <div class="metric-value" data-metric="incidentReports">{{ $incidentReports }}</div>
                        <div class="metric-footnote">Open or under review.</div>
                    </div>
                </div>
            </div>
        @endif

        @if (! is_null($maintenanceDue))
            <div class="col-md-4">
                <div class="card metric-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="metric-icon"><i class="bi bi-tools"></i></span>
                            <span class="badge bg-light text-primary">Service</span>
                        </div>
                        <div class="metric-label">Maintenance Due</div>
                        <div class="metric-value" data-metric="maintenanceDue">{{ $maintenanceDue }}</div>
                        <div class="metric-footnote">Expiring within 30 days.</div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            const metricElements = document.querySelectorAll('[data-metric]');
            const metricUrl = "{{ route('dashboard.metrics') }}";

            const updateMetrics = async () => {
                try {
                    const response = await fetch(metricUrl, { headers: { 'Accept': 'application/json' } });
                    if (!response.ok) {
                        return;
                    }
                    const data = await response.json();
                    metricElements.forEach((el) => {
                        const key = el.dataset.metric;
                        if (Object.prototype.hasOwnProperty.call(data, key) && data[key] !== null) {
                            el.textContent = data[key];
                        }
                    });
                } catch (error) {
                    console.warn('Dashboard metrics refresh failed.');
                }
            };

            setInterval(updateMetrics, 30000);
        </script>
    @endpush
</x-admin-layout>
