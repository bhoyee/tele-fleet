<section>
    <h2 class="h5 fw-semibold mb-2 text-danger">Delete account</h2>
    <p class="text-muted mb-4">Permanently remove your account and all associated data.</p>

    <form method="post" action="{{ route('profile.destroy') }}">
        @csrf
        @method('delete')

        <div class="mb-3">
            <label class="form-label" for="delete_password">Confirm password</label>
            <input id="delete_password" name="password" type="password" class="form-control" autocomplete="current-password" required>
            <div class="text-danger small d-none" id="deletePasswordClientError">Please enter your current password.</div>
            @if ($errors->getBag('userDeletion')->has('password'))
                <div class="text-danger small">{{ $errors->getBag('userDeletion')->first('password') }}</div>
            @endif
        </div>

        <button class="btn btn-outline-danger" type="button" id="openDeleteAccountModal">
            Delete account
        </button>

        <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteAccountModalLabel">Delete account</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">
                            This will permanently delete your account and all associated data. This action cannot be undone.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-danger" type="submit">Yes, delete my account</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const openButton = document.getElementById('openDeleteAccountModal');
                const passwordInput = document.getElementById('delete_password');
                const clientError = document.getElementById('deletePasswordClientError');
                const modalEl = document.getElementById('deleteAccountModal');
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

                if (!openButton || !passwordInput || !clientError || !modalEl || typeof bootstrap === 'undefined') {
                    return;
                }

                const showClientError = (message) => {
                    clientError.textContent = message;
                    clientError.classList.remove('d-none');
                };

                const hideClientError = () => {
                    clientError.classList.add('d-none');
                };

                passwordInput.addEventListener('input', () => {
                    if (passwordInput.value.trim() !== '') {
                        hideClientError();
                    }
                });

                openButton.addEventListener('click', async () => {
                    if (passwordInput.value.trim() === '') {
                        showClientError('Please enter your current password.');
                        passwordInput.focus();
                        return;
                    }

                    hideClientError();

                    const originalText = openButton.textContent;
                    openButton.disabled = true;
                    openButton.textContent = 'Checking...';

                    try {
                        const response = await fetch("{{ route('profile.password.check') }}", {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({ password: passwordInput.value }),
                        });

                        if (!response.ok) {
                            showClientError('Invalid password. Please try again.');
                            passwordInput.focus();
                            return;
                        }

                        bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    } catch (error) {
                        showClientError('Unable to validate password. Please try again.');
                    } finally {
                        openButton.disabled = false;
                        openButton.textContent = originalText;
                    }
                });
            });
        </script>
    @endpush
</section>
