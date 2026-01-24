<section>
    <h2 class="h5 fw-semibold mb-2 text-danger">Delete account</h2>
    <p class="text-muted mb-4">Permanently remove your account and all associated data.</p>

    <form method="post" action="{{ route('profile.destroy') }}">
        @csrf
        @method('delete')

        <div class="mb-3">
            <label class="form-label" for="password">Confirm password</label>
            <input id="password" name="password" type="password" class="form-control" autocomplete="current-password">
            @error('password') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>

        <button class="btn btn-outline-danger" type="submit" onclick="return confirm('Are you sure you want to delete your account?')">
            Delete account
        </button>
    </form>
</section>
