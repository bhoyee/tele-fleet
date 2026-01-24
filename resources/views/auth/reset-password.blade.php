<x-guest-layout>
    <div class="text-center mb-5">
        <div class="avatar-circle mb-3">
            <i class="bi bi-shield-lock"></i>
        </div>
        <h1 class="h2 fw-bold text-dark mb-2">Create New Password</h1>
        <p class="text-muted mb-4">Choose a strong password to secure your account</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" id="passwordResetForm">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-4">
            <label class="form-label fw-semibold text-dark mb-2" for="email">
                <i class="bi bi-envelope me-1" style="color: #056CA3;"></i> Email Address
            </label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0">
                    <i class="bi bi-envelope" style="color: #056CA3;"></i>
                </span>
                <input id="email" class="form-control ps-3 py-3 border-start-0" 
                       type="email" 
                       name="email" 
                       value="{{ old('email', $request->email) }}" 
                       required 
                       autofocus 
                       autocomplete="username"
                       placeholder="Enter your email address">
            </div>
            @error('email') 
                <div class="d-flex align-items-center text-danger small mt-2">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    <span>{{ $message }}</span>
                </div>
            @enderror
        </div>

        <div class="mb-4">
            <label class="form-label fw-semibold text-dark mb-2" for="password">
                <i class="bi bi-lock me-1" style="color: #056CA3;"></i> New Password
            </label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0">
                    <i class="bi bi-lock" style="color: #056CA3;"></i>
                </span>
                <input id="password" class="form-control ps-3 py-3 border-start-0 password-input" 
                       type="password" 
                       name="password" 
                       required 
                       autocomplete="new-password"
                       placeholder="Enter new password">
                <button class="btn btn-outline-secondary border-start-0" type="button" id="togglePassword">
                    <i class="bi bi-eye" style="color: #056CA3;"></i>
                </button>
            </div>
            @error('password') 
                <div class="d-flex align-items-center text-danger small mt-2">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    <span>{{ $message }}</span>
                </div>
            @enderror
        </div>

        <div class="mb-4">
            <label class="form-label fw-semibold text-dark mb-2" for="password_confirmation">
                <i class="bi bi-lock-fill me-1" style="color: #056CA3;"></i> Confirm New Password
            </label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0">
                    <i class="bi bi-lock-fill" style="color: #056CA3;"></i>
                </span>
                <input id="password_confirmation" class="form-control ps-3 py-3 border-start-0 password-input" 
                       type="password" 
                       name="password_confirmation" 
                       required 
                       autocomplete="new-password"
                       placeholder="Confirm new password">
                <button class="btn btn-outline-secondary border-start-0" type="button" id="toggleConfirmPassword">
                    <i class="bi bi-eye" style="color: #056CA3;"></i>
                </button>
            </div>
        </div>

        <!-- Password Strength Indicator -->
        <div class="mb-4">
            <div class="password-strength-container">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Password Strength:</span>
                    <span class="text-muted small" id="passwordStrengthText">Weak</span>
                </div>
                <div class="progress" style="height: 6px;">
                    <div id="passwordStrengthBar" class="progress-bar" role="progressbar" style="width: 0%; background-color: #ff6b6b;"></div>
                </div>
                <div class="mt-2">
                    <p class="text-muted small mb-1">Requirements:</p>
                    <ul class="list-unstyled text-muted small mb-0" style="font-size: 0.8rem;">
                        <li id="lengthReq" class="text-danger"><i class="bi bi-x-circle me-1"></i> At least 8 characters</li>
                        <li id="uppercaseReq" class="text-danger"><i class="bi bi-x-circle me-1"></i> One uppercase letter</li>
                        <li id="lowercaseReq" class="text-danger"><i class="bi bi-x-circle me-1"></i> One lowercase letter</li>
                        <li id="numberReq" class="text-danger"><i class="bi bi-x-circle me-1"></i> One number</li>
                        <li id="specialReq" class="text-danger"><i class="bi bi-x-circle me-1"></i> One special character</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
            <a href="{{ route('login') }}" class="text-decoration-none" style="color: #056CA3;">
                <i class="bi bi-arrow-left me-1"></i> Back to Login
            </a>
            <button class="btn fw-semibold px-4 py-2" type="submit" id="submitBtn" 
                    style="background: #056CA3; color: white; border-radius: 8px;">
                <span class="btn-text">Reset Password</span>
                <span class="spinner-border spinner-border-sm d-none" role="status"></span>
            </button>
        </div>
    </form>

    <div class="text-center pt-4 mt-4 border-top">
        <p class="text-muted small mb-2">Having trouble resetting your password?</p>
        <a href="#" class="fw-semibold text-decoration-none" style="color: #056CA3;">Contact Support</a>
    </div>

    <style>
        .avatar-circle {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #056CA3 0%, #065E8C 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
        
        .avatar-circle i {
            font-size: 2.5rem;
            color: white;
        }
        
        .form-control:focus {
            border-color: #056CA3;
            box-shadow: 0 0 0 0.25rem rgba(5, 108, 163, 0.25);
        }
        
        .input-group-text {
            transition: all 0.3s ease;
        }
        
        .form-control:focus + .input-group-text {
            border-color: #056CA3;
        }
        
        #submitBtn {
            background: linear-gradient(135deg, #056CA3 0%, #065E8C 100%);
            border: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        #submitBtn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(5, 108, 163, 0.3);
            background: linear-gradient(135deg, #065E8C 0%, #056CA3 100%);
        }
        
        #submitBtn:active {
            transform: translateY(0);
        }
        
        .alert {
            border: none;
            border-radius: 8px;
        }
        
        .input-group .btn-outline-secondary {
            border-color: #dee2e6;
        }
        
        .input-group .btn-outline-secondary:hover {
            background-color: rgba(5, 108, 163, 0.05);
            border-color: #056CA3;
        }
        
        /* Loading animation */
        #submitBtn.loading .btn-text {
            visibility: hidden;
        }
        
        #submitBtn.loading .spinner-border {
            display: inline-block !important;
        }
        
        /* Link hover effects */
        a:hover {
            color: #065E8C !important;
            text-decoration: underline;
        }
        
        /* Password strength indicator */
        .password-strength-container {
            background: rgba(5, 108, 163, 0.05);
            border-radius: 8px;
            padding: 1rem;
            border-left: 4px solid #056CA3;
        }
        
        .progress {
            background-color: #e9ecef;
        }
        
        /* Requirement check styling */
        .list-unstyled li {
            display: flex;
            align-items: center;
            margin-bottom: 0.25rem;
        }
        
        .list-unstyled li.text-success {
            color: #28a745 !important;
        }
        
        .list-unstyled li.text-success i {
            color: #28a745 !important;
        }
        
        .list-unstyled li.text-danger {
            color: #dc3545 !important;
        }
        
        .list-unstyled li.text-danger i {
            color: #dc3545 !important;
        }
        
        /* Password match indicator */
        .password-match {
            margin-top: 0.5rem;
            font-size: 0.85rem;
        }
        
        .match-success {
            color: #28a745;
        }
        
        .match-error {
            color: #dc3545;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password visibility toggle
            const togglePassword = document.getElementById('togglePassword');
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('password_confirmation');
            
            function togglePasswordVisibility(input, button) {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                const icon = button.querySelector('i');
                icon.className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
                icon.style.color = '#056CA3';
            }
            
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    togglePasswordVisibility(passwordInput, this);
                });
            }
            
            if (toggleConfirmPassword && confirmPasswordInput) {
                toggleConfirmPassword.addEventListener('click', function() {
                    togglePasswordVisibility(confirmPasswordInput, this);
                });
            }
            
            // Form submission loading state
            const passwordResetForm = document.getElementById('passwordResetForm');
            const submitBtn = document.getElementById('submitBtn');
            
            if (passwordResetForm && submitBtn) {
                passwordResetForm.addEventListener('submit', function(e) {
                    submitBtn.classList.add('loading');
                    submitBtn.disabled = true;
                    
                    // Change button text temporarily
                    const btnText = submitBtn.querySelector('.btn-text');
                    btnText.textContent = 'Resetting...';
                    
                    // Re-enable button after 5 seconds in case of error
                    setTimeout(() => {
                        submitBtn.classList.remove('loading');
                        submitBtn.disabled = false;
                        btnText.textContent = 'Reset Password';
                    }, 5000);
                });
            }
            
            // Password strength checker
            const passwordStrengthBar = document.getElementById('passwordStrengthBar');
            const passwordStrengthText = document.getElementById('passwordStrengthText');
            
            function checkPasswordStrength(password) {
                let strength = 0;
                const requirements = {
                    length: password.length >= 8,
                    uppercase: /[A-Z]/.test(password),
                    lowercase: /[a-z]/.test(password),
                    number: /[0-9]/.test(password),
                    special: /[^A-Za-z0-9]/.test(password)
                };
                
                // Update requirement indicators
                document.getElementById('lengthReq').className = requirements.length ? 'text-success' : 'text-danger';
                document.getElementById('lengthReq').innerHTML = requirements.length ? 
                    '<i class="bi bi-check-circle me-1"></i> At least 8 characters' : 
                    '<i class="bi bi-x-circle me-1"></i> At least 8 characters';
                
                document.getElementById('uppercaseReq').className = requirements.uppercase ? 'text-success' : 'text-danger';
                document.getElementById('uppercaseReq').innerHTML = requirements.uppercase ? 
                    '<i class="bi bi-check-circle me-1"></i> One uppercase letter' : 
                    '<i class="bi bi-x-circle me-1"></i> One uppercase letter';
                
                document.getElementById('lowercaseReq').className = requirements.lowercase ? 'text-success' : 'text-danger';
                document.getElementById('lowercaseReq').innerHTML = requirements.lowercase ? 
                    '<i class="bi bi-check-circle me-1"></i> One lowercase letter' : 
                    '<i class="bi bi-x-circle me-1"></i> One lowercase letter';
                
                document.getElementById('numberReq').className = requirements.number ? 'text-success' : 'text-danger';
                document.getElementById('numberReq').innerHTML = requirements.number ? 
                    '<i class="bi bi-check-circle me-1"></i> One number' : 
                    '<i class="bi bi-x-circle me-1"></i> One number';
                
                document.getElementById('specialReq').className = requirements.special ? 'text-success' : 'text-danger';
                document.getElementById('specialReq').innerHTML = requirements.special ? 
                    '<i class="bi bi-check-circle me-1"></i> One special character' : 
                    '<i class="bi bi-x-circle me-1"></i> One special character';
                
                // Calculate strength
                Object.values(requirements).forEach(req => {
                    if (req) strength += 20;
                });
                
                // Update progress bar
                passwordStrengthBar.style.width = strength + '%';
                
                // Update text and color
                if (strength < 40) {
                    passwordStrengthBar.style.backgroundColor = '#ff6b6b';
                    passwordStrengthText.textContent = 'Weak';
                    passwordStrengthText.style.color = '#ff6b6b';
                } else if (strength < 80) {
                    passwordStrengthBar.style.backgroundColor = '#ffd166';
                    passwordStrengthText.textContent = 'Fair';
                    passwordStrengthText.style.color = '#ffd166';
                } else if (strength < 100) {
                    passwordStrengthBar.style.backgroundColor = '#06d6a0';
                    passwordStrengthText.textContent = 'Good';
                    passwordStrengthText.style.color = '#06d6a0';
                } else {
                    passwordStrengthBar.style.backgroundColor = '#056CA3';
                    passwordStrengthText.textContent = 'Strong';
                    passwordStrengthText.style.color = '#056CA3';
                }
                
                return strength;
            }
            
            // Password match checker
            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                // Remove any existing match indicator
                const existingIndicator = document.querySelector('.password-match');
                if (existingIndicator) {
                    existingIndicator.remove();
                }
                
                if (password && confirmPassword) {
                    const indicator = document.createElement('div');
                    indicator.className = 'password-match';
                    
                    if (password === confirmPassword) {
                        indicator.innerHTML = '<i class="bi bi-check-circle me-1"></i> Passwords match';
                        indicator.classList.add('match-success');
                    } else {
                        indicator.innerHTML = '<i class="bi bi-x-circle me-1"></i> Passwords do not match';
                        indicator.classList.add('match-error');
                    }
                    
                    confirmPasswordInput.parentNode.parentNode.appendChild(indicator);
                }
            }
            
            // Event listeners for password strength and match checking
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    checkPasswordStrength(this.value);
                    checkPasswordMatch();
                });
            }
            
            if (confirmPasswordInput) {
                confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            }
            
            // Add focus effect for inputs
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    const icon = this.parentElement.querySelector('.input-group-text i');
                    if (icon) {
                        icon.style.color = '#065E8C';
                    }
                });
                
                input.addEventListener('blur', function() {
                    const icon = this.parentElement.querySelector('.input-group-text i');
                    if (icon) {
                        icon.style.color = '#056CA3';
                    }
                    
                    // Check if input has value
                    if (this.value) {
                        this.classList.add('has-value');
                    } else {
                        this.classList.remove('has-value');
                    }
                });
                
                // Check initial value
                if (input.value) {
                    input.classList.add('has-value');
                }
            });
            
            // Check password strength on page load if there's already a value
            if (passwordInput && passwordInput.value) {
                checkPasswordStrength(passwordInput.value);
                checkPasswordMatch();
            }
        });
    </script>
</x-guest-layout>