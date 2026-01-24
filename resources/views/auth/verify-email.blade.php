<x-guest-layout>
    <h1 class="h3 fw-bold mb-2">Verify your email</h1>
    <p class="text-muted mb-4">
        Thanks for signing up. Before getting started, please verify your email address by clicking the link we sent.
        If you did not receive the email, we can send another.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success">
            A new verification link has been sent to your email address.
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button class="btn btn-primary" type="submit">Resend verification email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-outline-secondary" type="submit">Log out</button>
        </form>
    </div>
</x-guest-layout>
