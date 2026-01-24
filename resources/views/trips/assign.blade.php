<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Assign Trip</h1>
            <p class="text-muted mb-0">Select a vehicle and driver.</p>
        </div>
        <a href="{{ route('trips.show', $tripRequest) }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('trips.assign.store', $tripRequest) }}">
                @csrf
                @method('PATCH')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="assigned_vehicle_id">Vehicle</label>
                        <select class="form-select" id="assigned_vehicle_id" name="assigned_vehicle_id" required>
                            <option value="">Select vehicle</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}">
                                    {{ $vehicle->registration_number }} - {{ $vehicle->make }} {{ $vehicle->model }}
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_vehicle_id') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="assigned_driver_id">Driver</label>
                        <select class="form-select" id="assigned_driver_id" name="assigned_driver_id" required>
                            <option value="">Select driver</option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}">{{ $driver->full_name }} ({{ $driver->license_number }})</option>
                            @endforeach
                        </select>
                        @error('assigned_driver_id') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Assign</button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
