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
                                <td>
                                    <div>{{ $trip->trip_date?->format('M d, Y') }}</div>
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
                                    <small class="text-muted">{{ $tripTime ?: 'N/A' }}</small>
                                </td>
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
                                    @if ($trip->status === 'pending')
                                        <a href="{{ route('trips.edit', $trip) }}" class="btn btn-sm btn-outline-secondary" data-loading>Edit</a>
                                    @endif
                                    <a href="{{ route('trips.show', $trip) }}" class="btn btn-sm btn-outline-primary" data-loading>View</a>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteTripModal"
                                            data-delete-action="{{ route('trips.destroy', $trip) }}"
                                            data-delete-label="{{ $trip->request_number }}">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteTripModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Trip</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Delete trip <strong id="deleteTripLabel"></strong>? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="deleteTripForm">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete Trip</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.querySelectorAll('[data-delete-action]').forEach((button) => {
                button.addEventListener('click', () => {
                    const action = button.getAttribute('data-delete-action');
                    const label = button.getAttribute('data-delete-label');
                    const form = document.getElementById('deleteTripForm');
                    if (form) {
                        form.setAttribute('action', action);
                    }
                    const labelEl = document.getElementById('deleteTripLabel');
                    if (labelEl) {
                        labelEl.textContent = label;
                    }
                });
            });
        </script>
    @endpush
</x-admin-layout>
