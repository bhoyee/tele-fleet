<x-guest-layout>
    <h1 class="h3 fw-bold mb-2">Sign in</h1>
    <p class="text-muted mb-4">Access the fleet operations dashboard.</p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input id="password" class="form-control" type="password" name="password" required autocomplete="current-password">
            @error('password') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>

        <div class="form-check mb-3">
            <input id="remember_me" class="form-check-input" type="checkbox" name="remember">
            <label class="form-check-label" for="remember_me">Remember me</label>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            @if (Route::has('password.request'))
                <a class="text-decoration-none" href="{{ route('password.request') }}">Forgot password?</a>
            @endif
            <button class="btn btn-primary" type="submit">Sign in</button>
        </div>
    </form>
</x-guest-layout>
