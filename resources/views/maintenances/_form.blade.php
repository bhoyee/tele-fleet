@php
    $maintenance = $maintenance ?? null;
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="vehicle_id">Vehicle</label>
        <select class="form-select" id="vehicle_id" name="vehicle_id" required>
            <option value="">Select vehicle</option>
            @foreach ($vehicles as $vehicle)
                <option value="{{ $vehicle->id }}" @selected(old('vehicle_id', $maintenance?->vehicle_id) == $vehicle->id)>
                    {{ $vehicle->registration_number }} - {{ $vehicle->make }} {{ $vehicle->model }}
                </option>
            @endforeach
        </select>
        @error('vehicle_id') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="status">Status</label>
        <select class="form-select" id="status" name="status" required>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(old('status', $maintenance?->status) === $status)>
                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                </option>
            @endforeach
        </select>
        @error('status') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="scheduled_for">Scheduled For</label>
        <input class="form-control" id="scheduled_for" type="date" name="scheduled_for"
               value="{{ old('scheduled_for', optional($maintenance?->scheduled_for)->format('Y-m-d')) }}" required>
        @error('scheduled_for') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="started_at">Started At</label>
        <input class="form-control" id="started_at" type="datetime-local" name="started_at"
               value="{{ old('started_at', optional($maintenance?->started_at)->format('Y-m-d\\TH:i')) }}">
        @error('started_at') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="completed_at">Completed At</label>
        <input class="form-control" id="completed_at" type="datetime-local" name="completed_at"
               value="{{ old('completed_at', optional($maintenance?->completed_at)->format('Y-m-d\\TH:i')) }}">
        @error('completed_at') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label class="form-label" for="description">Maintenance Summary</label>
        <input class="form-control" id="description" name="description"
               value="{{ old('description', $maintenance?->description) }}" required>
        @error('description') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label class="form-label" for="notes">Notes</label>
        <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $maintenance?->notes) }}</textarea>
        @error('notes') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="cost">Cost (optional)</label>
        <input class="form-control" id="cost" type="number" step="0.01" min="0" name="cost"
               value="{{ old('cost', $maintenance?->cost) }}">
        @error('cost') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="odometer">Odometer (km)</label>
        <input class="form-control" id="odometer" type="number" min="0" name="odometer"
               value="{{ old('odometer', $maintenance?->odometer) }}">
        @error('odometer') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>
