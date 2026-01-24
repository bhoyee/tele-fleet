<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Trip Logbook</h1>
            <p class="text-muted mb-0">Record actual trip details.</p>
        </div>
        <a href="{{ route('trips.show', $tripRequest) }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('trips.logbook.store', $tripRequest) }}">
                @csrf
                @method('POST')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="start_mileage">Start Mileage</label>
                        <input class="form-control" id="start_mileage" name="start_mileage" type="number" value="{{ old('start_mileage') }}" required>
                        @error('start_mileage') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="end_mileage">End Mileage</label>
                        <input class="form-control" id="end_mileage" name="end_mileage" type="number" value="{{ old('end_mileage') }}" required>
                        @error('end_mileage') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="fuel_before_trip">Fuel Before Trip</label>
                        <input class="form-control" id="fuel_before_trip" name="fuel_before_trip" type="number" step="0.01" value="{{ old('fuel_before_trip') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="fuel_after_trip">Fuel After Trip</label>
                        <input class="form-control" id="fuel_after_trip" name="fuel_after_trip" type="number" step="0.01" value="{{ old('fuel_after_trip') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="actual_start_time">Actual Start Time</label>
                        <input class="form-control" id="actual_start_time" name="actual_start_time" type="datetime-local" value="{{ old('actual_start_time') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="actual_end_time">Actual End Time</label>
                        <input class="form-control" id="actual_end_time" name="actual_end_time" type="datetime-local" value="{{ old('actual_end_time') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="driver_name">Driver Name</label>
                        <input class="form-control" id="driver_name" name="driver_name" value="{{ old('driver_name', $tripRequest->assignedDriver?->full_name) }}" required>
                        @error('driver_name') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="driver_license_number">Driver License Number</label>
                        <input class="form-control" id="driver_license_number" name="driver_license_number" value="{{ old('driver_license_number', $tripRequest->assignedDriver?->license_number) }}" required>
                        @error('driver_license_number') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="paper_logbook_ref_number">Paper Logbook Ref</label>
                        <input class="form-control" id="paper_logbook_ref_number" name="paper_logbook_ref_number" value="{{ old('paper_logbook_ref_number') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="log_date">Log Date</label>
                        <input class="form-control" id="log_date" name="log_date" type="date" value="{{ old('log_date', now()->toDateString()) }}" required>
                        @error('log_date') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-12">
                        <label class="form-label" for="driver_notes">Driver Notes</label>
                        <textarea class="form-control" id="driver_notes" name="driver_notes" rows="3">{{ old('driver_notes') }}</textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label" for="remarks">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="3">{{ old('remarks') }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-dark">Save Logbook</button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
