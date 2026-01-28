<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Maintenance Settings</h1>
            <p class="text-muted mb-0">Set the mileage target for maintenance alerts.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.maintenance-settings.update') }}">
                @csrf
                @method('PATCH')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="maintenance_mileage_target">Maintenance Mileage Target (km)</label>
                        <input class="form-control" id="maintenance_mileage_target" name="maintenance_mileage_target" type="number"
                               min="1000" max="50000" value="{{ old('maintenance_mileage_target', $target) }}" required>
                        @error('maintenance_mileage_target') <div class="text-danger small">{{ $message }}</div> @enderror
                        <div class="form-text">Alerts trigger at 98% of the target and again when overdue.</div>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-4">
                    <button class="btn btn-primary" type="submit">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
