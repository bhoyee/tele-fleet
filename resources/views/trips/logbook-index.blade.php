<x-admin-layout>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h1 class="h3 mb-1">Logbooks</h1>
            <p class="text-muted mb-0">Track trips pending logbook entry and completed logs.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('logbooks.manage') }}">Manage Logbooks</a>
    </div>

    @php
        $isSuperAdmin = auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN;
    @endphp

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle datatable">
                    <thead class="table-light">
                        <tr>
                            <th>Request #</th>
                            <th>Branch</th>
                            <th>Trip Date</th>
                            <th>Vehicle</th>
                            <th>Driver</th>
                            <th>Status</th>
                            <th>Due Status</th>
                            <th>Logbook</th>
                            @if ($isSuperAdmin)
                                <th>Entered By</th>
                                <th>Last Edited By</th>
                            @endif
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trips as $trip)
                            <tr>
                                <td>{{ $trip->request_number }}</td>
                                <td>{{ $trip->branch?->name ?? 'N/A' }}</td>
                                <td>{{ $trip->trip_date?->format('M d, Y') }}</td>
                                <td>{{ $trip->assignedVehicle?->registration_number ?? 'N/A' }}</td>
                                <td>{{ $trip->assignedDriver?->full_name ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-{{ $trip->status === 'completed' ? 'dark' : 'primary' }}">
                                        {{ ucfirst($trip->status) }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $dueStatus = $trip->dueStatus();
                                        $dueLabel = $dueStatus ? ucfirst($dueStatus) : 'On Schedule';
                                        $dueClass = $dueStatus === 'overdue'
                                            ? 'danger'
                                            : ($dueStatus === 'due'
                                                ? 'warning text-dark'
                                                : 'secondary');
                                    @endphp
                                    <span class="badge bg-{{ $dueClass }}">{{ $dueLabel }}</span>
                                </td>
                                <td>
                                    @if ($trip->log)
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                                @if ($isSuperAdmin)
                                    <td>{{ $trip->log?->enteredBy?->name ?? 'N/A' }}</td>
                                    <td>{{ $trip->log?->editedBy?->name ?? 'N/A' }}</td>
                                @endif
                                <td class="text-end">
                                    @if ($trip->log)
                                        <a href="{{ route('logbooks.show', $trip->log->id) }}" class="btn btn-sm btn-outline-primary" data-loading>View Logbook</a>
                                        <a href="{{ route('trips.logbook.edit', $trip) }}" class="btn btn-sm btn-outline-secondary" data-loading>Edit Logbook</a>
                                    @else
                                        <a href="{{ route('trips.logbook', $trip) }}" class="btn btn-sm btn-dark" data-loading>Enter Logbook</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
