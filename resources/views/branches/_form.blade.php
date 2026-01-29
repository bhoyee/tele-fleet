@php($branch = $branch ?? null)
@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="name">Branch Name</label>
        <input class="form-control" id="name" name="name" value="{{ old('name', $branch?->name ?? '') }}" required>
        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="code">Code</label>
        <input class="form-control" id="code" name="code" value="{{ old('code', $branch?->code ?? '') }}" required>
        @error('code') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="address">Address</label>
        <input class="form-control" id="address" name="address" value="{{ old('address', $branch?->address ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="city">City</label>
        <input class="form-control" id="city" name="city" value="{{ old('city', $branch?->city ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="state">State</label>
        <input class="form-control" id="state" name="state" value="{{ old('state', $branch?->state ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="phone">Phone</label>
        <input class="form-control" id="phone" name="phone" value="{{ old('phone', $branch?->phone ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" id="email" name="email" type="email" value="{{ old('email', $branch?->email ?? '') }}">
    </div>
    <div class="col-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="is_head_office" name="is_head_office" value="1" @checked(old('is_head_office', $branch?->is_head_office ?? false))>
            <label class="form-check-label" for="is_head_office">Head Office</label>
        </div>
    </div>
</div>
