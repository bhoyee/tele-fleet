<x-admin-layout>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h1 class="h3 mb-1">System Health</h1>
            <p class="text-muted mb-0">Live status for core services and background jobs.</p>
        </div>
    </div>

    <div class="row g-3 mb-4" id="healthChecks">
        @foreach ($checks as $key => $check)
            @php
                $badgeClass = $check['status'] === 'ok'
                    ? 'success'
                    : ($check['status'] === 'warning' ? 'warning' : 'danger');
            @endphp
            <div class="col-md-4 col-xl-3" data-health-card="{{ $key }}">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-label">{{ $check['label'] }}</div>
                                <div class="stat-value" data-health-status>{{ ucfirst($check['status']) }}</div>
                            </div>
                            <span class="badge bg-{{ $badgeClass }}" data-health-badge>{{ strtoupper($check['status']) }}</span>
                        </div>
                        <div class="text-muted small mt-2" data-health-detail>{{ $check['detail'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header">Queue Snapshot</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Pending Jobs</span>
                        <span class="fw-semibold" data-health-queue="pending">{{ $queueStats['pending'] ?? 'N/A' }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Failed Jobs</span>
                        <span class="fw-semibold" data-health-queue="failed">{{ $queueStats['failed'] ?? 'N/A' }}</span>
                    </div>
                    <div class="alert alert-warning mt-3 mb-0">
                        Ensure queue worker is running: <code>php artisan queue:work</code>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header">Scheduler Snapshot</div>
                <div class="card-body">
                    <p class="text-muted mb-2">The scheduler heartbeat updates every minute when the scheduler is running.</p>
                    <div class="alert alert-info mb-0">
                        Run scheduler locally: <code>php artisan schedule:work</code>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const refreshHealth = () => {
                fetch('{{ route('admin.health.data') }}', { cache: 'no-store' })
                    .then(response => response.json())
                    .then(data => {
                        if (!data?.checks) return;
                        Object.entries(data.checks).forEach(([key, item]) => {
                            const card = document.querySelector(`[data-health-card="${key}"]`);
                            if (!card) return;
                            const statusEl = card.querySelector('[data-health-status]');
                            const badgeEl = card.querySelector('[data-health-badge]');
                            const detailEl = card.querySelector('[data-health-detail]');
                            if (statusEl) statusEl.textContent = (item.status || '').toUpperCase();
                            if (badgeEl) {
                                badgeEl.textContent = (item.status || '').toUpperCase();
                                badgeEl.className = `badge bg-${item.status === 'ok' ? 'success' : (item.status === 'warning' ? 'warning' : 'danger')}`;
                            }
                            if (detailEl) detailEl.textContent = item.detail ?? '';
                        });

                        if (data.queueStats) {
                            const pending = document.querySelector('[data-health-queue="pending"]');
                            const failed = document.querySelector('[data-health-queue="failed"]');
                            if (pending) pending.textContent = data.queueStats.pending ?? 'N/A';
                            if (failed) failed.textContent = data.queueStats.failed ?? 'N/A';
                        }

                    })
                    .catch(() => {});
            };

            refreshHealth();
            setInterval(refreshHealth, 15000);
        </script>
    @endpush
</x-admin-layout>
