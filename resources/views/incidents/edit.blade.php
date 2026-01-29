<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Edit Incident</h1>
            <p class="text-muted mb-0">Update the incident details while it remains open.</p>
        </div>
        <a href="{{ route('incidents.show', $incident) }}" class="btn btn-outline-secondary" data-loading>Back</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('incidents.update', $incident) }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="branch_id">Branch</label>
                        <select class="form-select" id="branch_id" name="branch_id">
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(old('branch_id', $incident->branch_id) == $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="trip_request_id">Trip</label>
                        <select class="form-select" id="trip_request_id" name="trip_request_id">
                            <option value="">No linked trip</option>
                            @foreach ($trips as $trip)
                                <option value="{{ $trip->id }}" @selected(old('trip_request_id', $incident->trip_request_id) == $trip->id)>{{ $trip->request_number }} - {{ $trip->destination }}</option>
                            @endforeach
                        </select>
                        @error('trip_request_id') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="vehicle_id">Vehicle</label>
                        <select class="form-select" id="vehicle_id" name="vehicle_id">
                            <option value="">Select vehicle</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" @selected(old('vehicle_id', $incident->vehicle_id) == $vehicle->id)>{{ $vehicle->registration_number }} - {{ $vehicle->model }}</option>
                            @endforeach
                        </select>
                        @error('vehicle_id') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="driver_id">Driver</label>
                        <select class="form-select" id="driver_id" name="driver_id">
                            <option value="">Select driver</option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}" @selected(old('driver_id', $incident->driver_id) == $driver->id)>{{ $driver->full_name }}</option>
                            @endforeach
                        </select>
                        @error('driver_id') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="title">Title</label>
                        <input class="form-control" id="title" name="title" type="text" value="{{ old('title', $incident->title) }}" required>
                        @error('title') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="severity">Severity</label>
                        <select class="form-select" id="severity" name="severity" required>
                            <option value="minor" @selected(old('severity', $incident->severity) === 'minor')>Minor</option>
                            <option value="major" @selected(old('severity', $incident->severity) === 'major')>Major</option>
                            <option value="critical" @selected(old('severity', $incident->severity) === 'critical')>Critical</option>
                        </select>
                        @error('severity') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="incident_date">Incident Date</label>
                        <input class="form-control" id="incident_date" name="incident_date" type="date" value="{{ old('incident_date', optional($incident->incident_date)->format('Y-m-d')) }}" required>
                        @error('incident_date') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="incident_time">Incident Time</label>
                        @php
                            $incidentTimeValue = $incident->incident_time
                                ? \Illuminate\Support\Carbon::parse($incident->incident_time)->format('H:i')
                                : '';
                        @endphp
                        <input class="form-control" id="incident_time" name="incident_time" type="time" value="{{ old('incident_time', $incidentTimeValue) }}">
                        @error('incident_time') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12">
                        <label class="form-label" for="location">Location</label>
                        <input class="form-control" id="location" name="location" type="text" value="{{ old('location', $incident->location) }}">
                        @error('location') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12">
                        <label class="form-label" for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required>{{ old('description', $incident->description) }}</textarea>
                        @error('description') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-12">
                        <label class="form-label" for="attachments">Add Attachments</label>
                        <input class="form-control" id="attachments" name="attachments[]" type="file" multiple>
                        <div class="form-text">Existing files remain unless you delete the incident.</div>
                        @error('attachments') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button class="btn btn-primary" type="submit">Update Incident</button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
