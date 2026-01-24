<section>
    <h2 class="h5 fw-semibold mb-2">Update password</h2>
    <p class="text-muted mb-4">Use a strong password to secure your account.</p>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="mb-3">
            <label class="form-label" for="current_password">Current password</label>
            <input id="current_password" name="current_password" type="password" class="form-control" autocomplete="current-password">
            @error('current_password') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">New password</label>
            <input id="password" name="password" type="password" class="form-control" autocomplete="new-password">
            @error('password') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
            <label class="form-label" for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password">
        </div>

        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-primary" type="submit">Update password</button>
            @if (session('status') === 'password-updated')
                <span class="text-muted small">Updated.</span>
            @endif
        </div>
    </form>
</section>
