<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Edit Trip Request</h1>
            <p class="text-muted mb-0">Update trip details before completion.</p>
        </div>
        <a href="{{ route('trips.show', $tripRequest) }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('trips.update', $tripRequest) }}">
                @csrf
                @method('PATCH')

                <div class="row g-3">
                    @if ($branches->isNotEmpty())
                        <div class="col-md-6">
                            <label class="form-label" for="branch_id">Branch</label>
                            <select class="form-select" id="branch_id" name="branch_id">
                                <option value="">Select branch</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) old('branch_id', $tripRequest->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    @endif
                    <div class="col-md-6">
                        <label class="form-label" for="trip_date">Trip Date</label>
                        <input class="form-control" id="trip_date" name="trip_date" type="date" value="{{ old('trip_date', optional($tripRequest->trip_date)->format('Y-m-d')) }}" required>
                        @error('trip_date') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="purpose">Purpose</label>
                        <input class="form-control" id="purpose" name="purpose" value="{{ old('purpose', $tripRequest->purpose) }}" required>
                        @error('purpose') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="destination">Destination</label>
                        <input class="form-control" id="destination" name="destination" value="{{ old('destination', $tripRequest->destination) }}" required>
                        @error('destination') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="estimated_distance_km">Estimated Distance (km)</label>
                        <input class="form-control" id="estimated_distance_km" name="estimated_distance_km" type="number" step="0.01" value="{{ old('estimated_distance_km', $tripRequest->estimated_distance_km) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="number_of_passengers">Passengers</label>
                        <input class="form-control" id="number_of_passengers" name="number_of_passengers" type="number" min="1" value="{{ old('number_of_passengers', $tripRequest->number_of_passengers ?? 1) }}">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label" for="additional_notes">Additional Notes</label>
                        <textarea class="form-control" id="additional_notes" name="additional_notes" rows="3">{{ old('additional_notes', $tripRequest->additional_notes) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Update Trip</button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
