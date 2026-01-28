@php($driver = $driver ?? null)
@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="full_name">Full Name</label>
        <input class="form-control" id="full_name" name="full_name" value="{{ old('full_name', $driver?->full_name ?? '') }}" required>
        @error('full_name') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="license_number">License Number</label>
        <input class="form-control" id="license_number" name="license_number" value="{{ old('license_number', $driver?->license_number ?? '') }}" required>
        @error('license_number') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="license_type">License Type</label>
        <input class="form-control" id="license_type" name="license_type" value="{{ old('license_type', $driver?->license_type ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="license_expiry">License Expiry</label>
        <input class="form-control" id="license_expiry" name="license_expiry" type="date" value="{{ old('license_expiry', optional($driver?->license_expiry)->format('Y-m-d')) }}" required>
        @error('license_expiry') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="phone">Phone</label>
        <input class="form-control" id="phone" name="phone" value="{{ old('phone', $driver?->phone ?? '') }}" required>
        @error('phone') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" id="email" name="email" type="email" value="{{ old('email', $driver?->email ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="status">Status</label>
        <select class="form-select" id="status" name="status" required>
            @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $driver?->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="address">Address</label>
        <input class="form-control" id="address" name="address" value="{{ old('address', $driver?->address ?? '') }}">
    </div>
</div>
