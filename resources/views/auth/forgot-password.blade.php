<x-guest-layout>
    <h1 class="h3 fw-bold mb-2">Reset password</h1>
    <p class="text-muted mb-4">We will send a reset link to your email.</p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>

        <div class="d-flex justify-content-end">
            <button class="btn btn-primary" type="submit">Email reset link</button>
        </div>
    </form>
</x-guest-layout>
