@php($user = $user ?? null)
@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="name">Name</label>
        <input class="form-control" id="name" name="name" value="{{ old('name', $user?->name ?? '') }}" required>
        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" id="email" name="email" type="email" value="{{ old('email', $user?->email ?? '') }}" required>
        @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="phone">Phone</label>
        <input class="form-control" id="phone" name="phone" value="{{ old('phone', $user?->phone ?? '') }}">
        @error('phone') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="role">Role</label>
        <select class="form-select" id="role" name="role" required>
            <option value="">Select role</option>
            @foreach ($roles as $roleValue => $roleLabel)
                <option value="{{ $roleValue }}" @selected(old('role', $user?->role ?? '') === $roleValue)>{{ $roleLabel }}</option>
            @endforeach
        </select>
        @error('role') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="branch_id">Branch</label>
        <select class="form-select" id="branch_id" name="branch_id">
            <option value="">Select branch</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) old('branch_id', $user?->branch_id ?? '') === (string) $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
        @error('branch_id') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="status">Status</label>
        <select class="form-select" id="status" name="status" required>
            @foreach ($statuses as $statusValue => $statusLabel)
                <option value="{{ $statusValue }}" @selected(old('status', $user?->status ?? '') === $statusValue)>{{ $statusLabel }}</option>
            @endforeach
        </select>
        @error('status') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" id="password" name="password" type="password">
        <div class="form-text">Leave blank to auto-generate a temporary password.</div>
        @error('password') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="password_confirmation">Confirm Password</label>
        <input class="form-control" id="password_confirmation" name="password_confirmation" type="password">
    </div>
</div>
