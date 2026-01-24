<x-guest-layout>
    <h1 class="h3 fw-bold mb-2">Confirm password</h1>
    <p class="text-muted mb-4">Please confirm your password before continuing.</p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input id="password" class="form-control" type="password" name="password" required autocomplete="current-password">
            @error('password') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>

        <div class="d-flex justify-content-end">
            <button class="btn btn-primary" type="submit">Confirm</button>
        </div>
    </form>
</x-guest-layout>
