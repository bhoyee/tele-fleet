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

                @if (!empty($assignmentAlert))
                    <div class="alert {{ $assignmentBlocked ? 'alert-warning' : 'alert-info' }}">
                        {{ $assignmentAlert }}
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="assigned_vehicle_id">Vehicle</label>
                        <select class="form-select tele-select2" id="assigned_vehicle_id" name="assigned_vehicle_id" data-placeholder="Search vehicle..." required @disabled($assignmentBlocked)>
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
                        <select class="form-select tele-select2" id="assigned_driver_id" name="assigned_driver_id" data-placeholder="Search driver..." required @disabled($assignmentBlocked)>
                            <option value="">Select driver</option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}">{{ $driver->full_name }} ({{ $driver->license_number }})</option>
                            @endforeach
                        </select>
                        @error('assigned_driver_id') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                @if ($assignmentOverrideAvailable)
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="force_assign" id="force_assign" value="1" @checked(old('force_assign'))>
                        <label class="form-check-label" for="force_assign">
                            Override and assign despite trip date/time passed
                        </label>
                    </div>

                    <div class="mt-3">
                        <label class="form-label" for="assignment_reason">Reason (required for override)</label>
                        <textarea class="form-control" id="assignment_reason" name="reason" rows="3">{{ old('reason') }}</textarea>
                        <div class="form-text">Required only if you enable override for a past trip.</div>
                        @error('reason') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                @endif

                <div class="mt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary" @disabled($assignmentBlocked)>Assign</button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>

@if ($assignmentOverrideAvailable)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const overrideCheckbox = document.getElementById('force_assign');
                const reasonField = document.getElementById('assignment_reason');
                if (!overrideCheckbox || !reasonField) {
                    return;
                }

                const syncReasonRequired = () => {
                    reasonField.required = overrideCheckbox.checked;
                };

                syncReasonRequired();
                overrideCheckbox.addEventListener('change', syncReasonRequired);
            });
        </script>
    @endpush
@endif
