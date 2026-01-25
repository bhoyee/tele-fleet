<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">My Trip Requests</h1>
            <p class="text-muted mb-0">Track your submitted trip requests and statuses.</p>
        </div>
        <a href="{{ route('trips.create') }}" class="btn btn-primary" data-loading>New Trip</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle datatable">
                    <thead class="table-light">
                        <tr>
                            <th>Request #</th>
                            <th>Destination</th>
                            <th>Trip Date</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trips as $trip)
                            <tr>
                                <td>{{ $trip->request_number }}</td>
                                <td>{{ $trip->destination }}</td>
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
                                <td class="text-end">
                                    <a href="{{ route('trips.show', $trip) }}" class="btn btn-sm btn-outline-primary" data-loading>View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
