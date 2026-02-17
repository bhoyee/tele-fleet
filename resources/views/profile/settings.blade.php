<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">App Settings</h1>
            <p class="text-muted mb-0">Customize your branding.</p>
        </div>
        <a href="{{ route('profile.edit') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('profile.settings.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="app_name">App name</label>
                        <input id="app_name" name="app_name" class="form-control" value="{{ old('app_name', $appName) }}" required>
                        @error('app_name') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="org_name">Organization name</label>
                        <input id="org_name" name="org_name" class="form-control" value="{{ old('org_name', $orgName ?? '') }}" placeholder="{{ $orgName ?? 'Lagos Island State Administration' }}">
                        @error('org_name') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="support_email">Support/Developer email</label>
                        <input id="support_email" name="support_email" class="form-control" value="{{ old('support_email', $supportEmail ?? '') }}" placeholder="developer@example.com">
                        <div class="form-text">Used for “Contact Developer” tickets (Super Admin only).</div>
                        @error('support_email') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="logo">Logo</label>
                        <input id="logo" name="logo" type="file" class="form-control" accept="image/*">
                        <div class="form-text">Recommended: PNG/WebP with transparent background.</div>
                        @error('logo') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <div class="text-muted small mb-2">Preview</div>
                    <div class="d-flex align-items-center gap-3 p-3 border rounded-3 bg-white">
                        @if (!empty($appLogoUrl))
                            <img src="{{ $appLogoUrl }}" alt="Logo" style="height: 40px; width: 40px; object-fit: contain;">
                        @else
                            <div class="d-flex align-items-center justify-content-center rounded-circle" style="height: 40px; width: 40px; background: rgba(5, 108, 163, 0.12); color: #056CA3;">
                                <i class="bi bi-truck"></i>
                            </div>
                        @endif
                        <div class="fw-semibold">{{ old('app_name', $appName) }}</div>
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Save settings</button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
