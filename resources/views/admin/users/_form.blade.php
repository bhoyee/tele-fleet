@php
    $user = $user ?? null;
@endphp
@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="name">Name</label>
        <input class="form-control" id="name" name="name" value="{{ old('name', $user?->name ?? '') }}" required>
        <div class="invalid-feedback" id="nameInvalidFeedback">Name must contain only letters and spaces (e.g. "Ade Boye").</div>
        @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" id="email" name="email" type="email" value="{{ old('email', $user?->email ?? '') }}" required>
        @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="phone">Phone</label>
        <input class="form-control" id="phone" name="phone" type="tel" inputmode="numeric" pattern="[0-9]*" placeholder="08065428869"
               value="{{ old('phone', $user?->phone ?? '') }}" required>
        @error('phone') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="role">Role</label>
        <select class="form-select" id="role" name="role" required>
            <option value="">Select role</option>
            @foreach ($roles as $roleValue => $roleLabel)
                <option value="{{ $roleValue }}" @selected(old('role', $user?->role ?? '') === $roleValue)>{{ $roleLabel }}</option>
            @endforeach
        </select>
        @error('role') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="branch_id">Branch</label>
        <select class="form-select tele-select2" id="branch_id" name="branch_id" data-placeholder="Search branch...">
            <option value="">Select branch</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) old('branch_id', $user?->branch_id ?? '') === (string) $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
        @error('branch_id') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="status">Status</label>
        <select class="form-select" id="status" name="status" required>
            @foreach ($statuses as $statusValue => $statusLabel)
                <option value="{{ $statusValue }}" @selected(old('status', $user?->status ?? '') === $statusValue)>{{ $statusLabel }}</option>
            @endforeach
        </select>
        @error('status') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" id="password" name="password" type="password">
        <div class="form-text">Leave blank to auto-generate a temporary password.</div>
        @error('password') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="password_confirmation">Confirm Password</label>
        <input class="form-control" id="password_confirmation" name="password_confirmation" type="password">
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
            const roleSelect = document.getElementById('role');
            const branchSelect = document.getElementById('branch_id');
            if (!roleSelect || !branchSelect) {
                return;
            }

            const rolesRequiringBranch = new Set(['branch_admin', 'branch_head']);

            const syncBranchRequired = () => {
                const role = String(roleSelect.value || '').trim();
                const required = rolesRequiringBranch.has(role);
                branchSelect.required = required;
                if (!required) {
                    branchSelect.classList.remove('is-invalid');
                    branchSelect.setCustomValidity('');
                }
            };

            roleSelect.addEventListener('change', syncBranchRequired);
            branchSelect.addEventListener('change', () => {
                branchSelect.classList.remove('is-invalid');
                branchSelect.setCustomValidity('');
            });
            branchSelect.addEventListener('invalid', () => {
                branchSelect.classList.add('is-invalid');
                branchSelect.setCustomValidity('Branch is required for Branch Admin and Branch Head users.');
            });

            syncBranchRequired();
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const nameInput = document.getElementById('name');
            const feedback = document.getElementById('nameInvalidFeedback');
            if (!nameInput) {
                return;
            }

            // Allow: letters (any language), spaces, apostrophes, hyphens.
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
                const value = nameInput.value;
                if (value.trim() === '') {
                    // Let required validation handle empty on submit; keep it clean while typing.
                    setValid();
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
@endpush
