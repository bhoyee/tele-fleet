<section>
    <h2 class="h5 fw-semibold mb-2">Profile information</h2>
    <p class="text-muted mb-4">Update your name and email address.</p>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="mb-3">
            <label class="form-label" for="name">Name</label>
            <input id="name" name="name" type="text" class="form-control" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
            @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input id="email" name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}" required autocomplete="username">
            @error('email') <div class="text-danger small">{{ $message }}</div> @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2 text-muted small">
                    Your email address is unverified.
                    <button form="send-verification" class="btn btn-link p-0 align-baseline">Resend verification email</button>
                </div>

                @if (session('status') === 'verification-link-sent')
                    <div class="mt-2 text-success small">A new verification link has been sent.</div>
                @endif
            @endif
        </div>

        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-primary" type="submit">Save changes</button>
            @if (session('status') === 'profile-updated')
                <span class="text-muted small">Saved.</span>
            @endif
        </div>
    </form>
</section>
