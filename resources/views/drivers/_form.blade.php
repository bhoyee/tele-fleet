@php
    $driver = $driver ?? null;
    $selectedStatus = old('status', $driver?->status ?? 'active');
    $requiresNote = in_array($selectedStatus, ['inactive', 'suspended'], true);

    $phoneValue = old('phone');
    if ($phoneValue === null) {
        $phoneValue = $driver?->phone ?? '';
    }

    if (is_string($phoneValue) && str_starts_with($phoneValue, '+')) {
        $digits = preg_replace('/\D+/', '', $phoneValue) ?? '';
        // Display Nigeria E.164 numbers in local format for editing (e.g. +234806... -> 0806...).
        if (str_starts_with($digits, '234') && strlen($digits) >= 13) {
            $phoneValue = '0' . substr($digits, 3);
        } else {
            $phoneValue = $digits;
        }
    }
@endphp
@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="full_name">Full Name</label>
        <input class="form-control" id="full_name" name="full_name" value="{{ old('full_name', $driver?->full_name ?? '') }}" required>
        <div class="invalid-feedback" id="driverNameInvalidFeedback">Full name must contain only letters and spaces (e.g. "Ibrahim Musa").</div>
        @error('full_name') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="license_number">License Number</label>
        <input class="form-control" id="license_number" name="license_number" value="{{ old('license_number', $driver?->license_number ?? '') }}" required>
        @error('license_number') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="license_type">License Type</label>
        <input class="form-control" id="license_type" name="license_type" value="{{ old('license_type', $driver?->license_type ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="license_expiry">License Expiry</label>
        <input class="form-control" id="license_expiry" name="license_expiry" type="date" value="{{ old('license_expiry', optional($driver?->license_expiry)->format('Y-m-d')) }}" required>
        @error('license_expiry') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="phone">Phone</label>
        <input class="form-control" id="phone" name="phone" type="tel" inputmode="numeric" pattern="[0-9]*" placeholder="08065428869" value="{{ $phoneValue }}" required>
        @error('phone') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" id="email" name="email" type="email" value="{{ old('email', $driver?->email ?? '') }}">
        <div class="invalid-feedback" id="driverEmailInvalidFeedback">This email address is already registered.</div>
        @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="status">Status</label>
        <select class="form-select" id="status" name="status" required>
            @foreach (['active' => 'Active', 'inactive' => 'Assigned to Officer', 'suspended' => 'On Leave'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $driver?->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="address">Address</label>
        <input class="form-control" id="address" name="address" value="{{ old('address', $driver?->address ?? '') }}">
    </div>
    <div class="col-12" id="driverStatusNoteWrap" style="{{ $requiresNote ? '' : 'display:none;' }}">
        <label class="form-label" for="note">Note</label>
        <textarea class="form-control" id="note" name="note" rows="3" {{ $requiresNote ? 'required' : '' }}>{{ old('note', $driver?->note ?? '') }}</textarea>
        <div class="form-text">Required when the driver is On Leave or Assigned to Officer.</div>
        @error('note') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.getElementById('phone');
            if (!input) {
                return;
            }

            input.addEventListener('input', () => {
                input.value = String(input.value || '').replace(/\D+/g, '');
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const nameInput = document.getElementById('full_name');
            const feedback = document.getElementById('driverNameInvalidFeedback');
            if (!nameInput) {
                return;
            }

            const isValidName = (value) => {
                const trimmed = String(value || '').trim();
                if (!trimmed) {
                    return false;
                }
                return /^\p{L}+(?:[\s'-]\p{L}+)*$/u.test(trimmed);
            };

            const setInvalid = (message) => {
                nameInput.classList.add('is-invalid');
                nameInput.setCustomValidity(message || 'Invalid name.');
                if (feedback) {
                    feedback.textContent = message || feedback.textContent;
                }
            };

            const setValid = () => {
                nameInput.classList.remove('is-invalid');
                nameInput.setCustomValidity('');
            };

            const validateNow = () => {
                const value = String(nameInput.value || '');
                if (!value.trim()) {
                    setInvalid('Full name is required.');
                    return;
                }
                if (isValidName(value)) {
                    setValid();
                } else {
                    setInvalid('Only letters, spaces, apostrophes, and hyphens are allowed.');
                }
            };

            nameInput.addEventListener('blur', validateNow);
            nameInput.addEventListener('input', validateNow);
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const emailInput = document.getElementById('email');
            const feedback = document.getElementById('driverEmailInvalidFeedback');
            if (!emailInput) {
                return;
            }

            const checkUrl = @json(route('drivers.check-email'));
            const ignoreId = @json($driver?->id);

            const setInvalid = (message) => {
                emailInput.classList.add('is-invalid');
                emailInput.setCustomValidity(message || 'Invalid email.');
                if (feedback) {
                    feedback.textContent = message || feedback.textContent;
                }
            };

            const setValid = () => {
                emailInput.classList.remove('is-invalid');
                emailInput.setCustomValidity('');
            };

            let timer = null;
            let lastChecked = '';

            const runCheck = async () => {
                const raw = String(emailInput.value || '').trim();
                if (!raw) {
                    setValid();
                    return;
                }

                // If browser says it's invalid format, don't show "already exists".
                if (!emailInput.checkValidity()) {
                    setInvalid('Enter a valid email address.');
                    return;
                }

                const normalized = raw.toLowerCase();
                if (normalized === lastChecked) {
                    return;
                }
                lastChecked = normalized;

                const url = new URL(checkUrl, window.location.origin);
                url.searchParams.set('email', normalized);
                if (ignoreId) {
                    url.searchParams.set('ignore', String(ignoreId));
                }

                try {
                    const response = await fetch(url.toString(), { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
                    if (!response.ok) {
                        // Don't block form if the check endpoint fails.
                        setValid();
                        return;
                    }
                    const data = await response.json();
                    if (data && data.available === false) {
                        setInvalid('This email address is already registered for another driver.');
                    } else {
                        setValid();
                    }
                } catch (error) {
                    setValid();
                }
            };

            const scheduleCheck = () => {
                if (timer) {
                    clearTimeout(timer);
                }
                timer = setTimeout(runCheck, 350);
            };

            emailInput.addEventListener('blur', runCheck);
            emailInput.addEventListener('input', scheduleCheck);
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const statusSelect = document.getElementById('status');
            const noteWrap = document.getElementById('driverStatusNoteWrap');
            const noteInput = document.getElementById('note');

            if (!statusSelect || !noteWrap || !noteInput) {
                return;
            }

            const needsNote = (status) => status === 'inactive' || status === 'suspended';

            const syncNoteVisibility = () => {
                const status = statusSelect.value;
                const required = needsNote(status);
                noteWrap.style.display = required ? '' : 'none';
                noteInput.required = required;
                if (!required) {
                    noteInput.value = '';
                }
            };

            statusSelect.addEventListener('change', syncNoteVisibility);
            syncNoteVisibility();
        });
    </script>
@endpush
