@php($vehicle = $vehicle ?? null)
@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="registration_number">Registration Number</label>
        <input class="form-control" id="registration_number" name="registration_number" value="{{ old('registration_number', $vehicle?->registration_number ?? '') }}" required>
        @error('registration_number') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="branch_id">Branch</label>
        <select class="form-select" id="branch_id" name="branch_id" required>
            <option value="">Select branch</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) old('branch_id', $vehicle?->branch_id ?? '') === (string) $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
        @error('branch_id') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="make">Make</label>
        <input class="form-control" id="make" name="make" value="{{ old('make', $vehicle?->make ?? '') }}" required>
        @error('make') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="model">Model</label>
        <input class="form-control" id="model" name="model" value="{{ old('model', $vehicle?->model ?? '') }}" required>
        @error('model') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="year">Year</label>
        <input class="form-control" id="year" name="year" type="number" value="{{ old('year', $vehicle?->year ?? '') }}" required>
        @error('year') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="color">Color</label>
        <input class="form-control" id="color" name="color" value="{{ old('color', $vehicle?->color ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="fuel_type">Fuel Type</label>
        <select class="form-select" id="fuel_type" name="fuel_type" required>
            @foreach (['petrol' => 'Petrol', 'diesel' => 'Diesel', 'hybrid' => 'Hybrid', 'electric' => 'Electric'] as $value => $label)
                <option value="{{ $value }}" @selected(old('fuel_type', $vehicle?->fuel_type ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('fuel_type') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="engine_capacity">Engine Capacity</label>
        <input class="form-control" id="engine_capacity" name="engine_capacity" value="{{ old('engine_capacity', $vehicle?->engine_capacity ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="current_mileage">Current Mileage</label>
        <input class="form-control" id="current_mileage" name="current_mileage" type="number" value="{{ old('current_mileage', $vehicle?->current_mileage ?? 0) }}" required>
        @error('current_mileage') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="insurance_expiry">Insurance Expiry</label>
        <input class="form-control" id="insurance_expiry" name="insurance_expiry" type="date" value="{{ old('insurance_expiry', optional($vehicle?->insurance_expiry)->format('Y-m-d')) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="registration_expiry">Registration Expiry</label>
        <input class="form-control" id="registration_expiry" name="registration_expiry" type="date" value="{{ old('registration_expiry', optional($vehicle?->registration_expiry)->format('Y-m-d')) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="status">Status</label>
        <select class="form-select" id="status" name="status" required>
            @foreach (['available' => 'Available', 'in_use' => 'In Use', 'maintenance' => 'Maintenance', 'offline' => 'Offline'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $vehicle?->status ?? 'available') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>
