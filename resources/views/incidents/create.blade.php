<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">New Incident Report</h1>
            <p class="text-muted mb-0">File an incident for review and tracking.</p>
        </div>
        <a href="{{ route('incidents.index') }}" class="btn btn-outline-secondary" data-loading>Back</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('incidents.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="title">Title</label>
                        <input class="form-control" id="title" name="title" value="{{ old('title') }}" required>
                        @error('title') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="severity">Severity</label>
                        <select class="form-select" id="severity" name="severity" required>
                            <option value="">Select severity</option>
                            <option value="minor" @selected(old('severity') === 'minor')>Minor</option>
                            <option value="major" @selected(old('severity') === 'major')>Major</option>
                            <option value="critical" @selected(old('severity') === 'critical')>Critical</option>
                        </select>
                        @error('severity') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="incident_date">Incident Date</label>
                        <input class="form-control" id="incident_date" name="incident_date" type="date" value="{{ old('incident_date', now()->toDateString()) }}" required>
                        @error('incident_date') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="incident_time">Incident Time</label>
                        <input class="form-control" id="incident_time" name="incident_time" type="time" value="{{ old('incident_time') }}">
                        @error('incident_time') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="location">Location</label>
                        <input class="form-control" id="location" name="location" value="{{ old('location') }}">
                        @error('location') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="branch_name">Branch</label>
                        <input id="branch_name" class="form-control" type="text" value="{{ auth()->user()->branch?->name ?? 'N/A' }}" readonly>
                        <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="trip_request_id">Related Trip</label>
                        <select class="form-select" id="trip_request_id" name="trip_request_id" required>
                            <option value="">Select trip</option>
                            @foreach ($trips as $trip)
                                <option value="{{ $trip->id }}"
                                        data-vehicle-id="{{ $trip->assignedVehicle?->id }}"
                                        data-driver-id="{{ $trip->assignedDriver?->id }}"
                                        @selected((string) old('trip_request_id') === (string) $trip->id)>
                                    {{ $trip->request_number }} - {{ $trip->destination }}
                                </option>
                            @endforeach
                        </select>
                        @error('trip_request_id') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="vehicle_id">Vehicle</label>
                        <select class="form-select" id="vehicle_id" name="vehicle_id" disabled>
                            <option value="">Select a trip first</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" @selected((string) old('vehicle_id') === (string) $vehicle->id)>
                                    {{ $vehicle->registration_number }} - {{ $vehicle->make }} {{ $vehicle->model }}
                                </option>
                            @endforeach
                        </select>
                        @error('vehicle_id') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="driver_id">Driver</label>
                        <select class="form-select" id="driver_id" name="driver_id" disabled>
                            <option value="">Select a trip first</option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}" @selected((string) old('driver_id') === (string) $driver->id)>
                                    {{ $driver->full_name }} ({{ $driver->license_number }})
                                </option>
                            @endforeach
                        </select>
                        @error('driver_id') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-12">
                        <label class="form-label" for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required>{{ old('description') }}</textarea>
                        @error('description') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-12">
                        <label class="form-label" for="attachments">Attachments</label>
                        <input class="form-control" id="attachments" name="attachments[]" type="file" multiple>
                        @error('attachments') <div class="text-danger small">{{ $message }}</div> @enderror
                        @error('attachments.*') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Submit Incident</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            const tripSelect = document.getElementById('trip_request_id');
            const vehicleSelect = document.getElementById('vehicle_id');
            const driverSelect = document.getElementById('driver_id');

            const resetSelect = (select, placeholder) => {
                if (!select) {
                    return;
                }
                select.value = '';
                select.disabled = true;
                if (select.options.length) {
                    select.options[0].text = placeholder;
                }
            };

            const applyTripSelection = () => {
                const selected = tripSelect.options[tripSelect.selectedIndex];
                const vehicleId = selected?.getAttribute('data-vehicle-id');
                const driverId = selected?.getAttribute('data-driver-id');

                if (!vehicleId || !driverId) {
                    resetSelect(vehicleSelect, 'No assigned vehicle');
                    resetSelect(driverSelect, 'No assigned driver');
                    return;
                }

                vehicleSelect.disabled = false;
                driverSelect.disabled = false;
                if (vehicleSelect.options.length) {
                    vehicleSelect.options[0].text = 'Assigned vehicle';
                }
                if (driverSelect.options.length) {
                    driverSelect.options[0].text = 'Assigned driver';
                }
                vehicleSelect.value = vehicleId;
                driverSelect.value = driverId;
            };

            if (tripSelect) {
                tripSelect.addEventListener('change', applyTripSelection);
                applyTripSelection();
            }
        </script>
    @endpush
</x-admin-layout>
