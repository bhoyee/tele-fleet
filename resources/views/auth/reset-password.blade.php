<x-guest-layout>
    <h1 class="h3 fw-bold mb-2">Choose a new password</h1>
    <p class="text-muted mb-4">Enter your email and new password below.</p>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
            @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input id="password" class="form-control" type="password" name="password" required autocomplete="new-password">
            @error('password') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
            <label class="form-label" for="password_confirmation">Confirm Password</label>
            <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>

        <div class="d-flex justify-content-end">
            <button class="btn btn-primary" type="submit">Reset password</button>
        </div>
    </form>
</x-guest-layout>
