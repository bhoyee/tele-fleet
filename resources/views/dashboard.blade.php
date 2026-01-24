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

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Active Vehicles</div>
                    <div class="fs-3 fw-bold">--</div>
                    <div class="text-muted small">Update after seeding data.</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Drivers On Duty</div>
                    <div class="fs-3 fw-bold">--</div>
                    <div class="text-muted small">Track availability in real time.</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Trips This Week</div>
                    <div class="fs-3 fw-bold">--</div>
                    <div class="text-muted small">Approval workflow overview.</div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
