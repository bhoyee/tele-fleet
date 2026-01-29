<x-admin-layout>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h1 class="h3 mb-1">{{ $branch->name }}</h1>
            <p class="text-muted mb-0">Branch details and assigned leadership.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('branches.edit', $branch) }}" class="btn btn-outline-primary">Edit Branch</a>
            <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header">Branch Information</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="text-muted small">Code</div>
                            <div class="fw-semibold">{{ $branch->code ?? 'N/A' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">Head Office</div>
                            <div class="fw-semibold">{{ $branch->is_head_office ? 'Yes' : 'No' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">City</div>
                            <div class="fw-semibold">{{ $branch->city ?? 'N/A' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">State</div>
                            <div class="fw-semibold">{{ $branch->state ?? 'N/A' }}</div>
                        </div>
                        <div class="col-sm-12">
                            <div class="text-muted small">Address</div>
                            <div class="fw-semibold">{{ $branch->address ?? 'N/A' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">Phone</div>
                            <div class="fw-semibold">{{ $branch->phone ?? 'N/A' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="text-muted small">Email</div>
                            <div class="fw-semibold">{{ $branch->email ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header">Branch Head</div>
                <div class="card-body">
                    @if ($branchHeads->isEmpty())
                        <div class="text-muted">No branch head assigned.</div>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($branchHeads as $head)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>{{ $head->name }}</span>
                                    <span class="text-muted small">{{ $head->email }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header">Branch Admin</div>
                <div class="card-body">
                    @if ($branchAdmins->isEmpty())
                        <div class="text-muted">No branch admin assigned.</div>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($branchAdmins as $admin)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>{{ $admin->name }}</span>
                                    <span class="text-muted small">{{ $admin->email }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
