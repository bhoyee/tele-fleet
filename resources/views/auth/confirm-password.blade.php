<x-guest-layout>
    <div class="text-center mb-5">
        <div class="avatar-circle mb-3">
            <i class="bi bi-shield-check"></i>
        </div>
        <h1 class="h2 fw-bold text-dark mb-2">Confirm Your Identity</h1>
        <p class="text-muted mb-4">Please confirm your password to continue</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" id="confirmForm">
        @csrf

        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label fw-semibold text-dark" for="password">
                    <i class="bi bi-lock me-1" style="color: #056CA3;"></i> Password
                </label>
                @if (Route::has('password.request'))
                    <a class="text-decoration-none fw-medium small" href="{{ route('password.request') }}" style="color: #056CA3;">
                        Forgot password?
                    </a>
                @endif
            </div>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0">
                    <i class="bi bi-lock" style="color: #056CA3;"></i>
                </span>
                <input id="password" class="form-control ps-3 py-3 border-start-0" 
                       type="password" 
                       name="password" 
                       required 
                       autocomplete="current-password"
                       placeholder="Enter your password">
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

        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
            <a href="{{ url()->previous() }}" class="text-decoration-none" style="color: #64748b;">
                <i class="bi bi-arrow-left me-1"></i> Go Back
            </a>
            <button class="btn fw-semibold px-4 py-2" type="submit" id="submitBtn" 
                    style="background: #056CA3; color: white; border-radius: 8px;">
                <span class="btn-text">Confirm Password</span>
                <span class="spinner-border spinner-border-sm d-none" role="status"></span>
            </button>
        </div>
    </form>

    <div class="mt-4 pt-3 border-top">
        <div class="alert alert-info d-flex align-items-start mb-0" style="background: rgba(5, 108, 163, 0.05); border-left: 4px solid #056CA3; border-radius: 8px;">
            <i class="bi bi-info-circle me-2" style="color: #056CA3; font-size: 1.1rem;"></i>
            <div>
                <p class="mb-1" style="color: #056CA3; font-weight: 500;">Security Notice</p>
                <p class="text-muted small mb-0">We're asking for your password to verify your identity before proceeding with this sensitive action.</p>
            </div>
        </div>
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
        
        .alert-info {
            border: none;
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
            text-decoration: underline;
        }
        
        a[href*="password.request"]:hover {
            color: #065E8C !important;
        }
        
        a[href*="previous"]:hover {
            color: #056CA3 !important;
        }
        
        /* Security badge */
        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(5, 108, 163, 0.1);
            color: #056CA3;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password visibility toggle
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? 
                        '<i class="bi bi-eye" style="color: #056CA3;"></i>' : 
                        '<i class="bi bi-eye-slash" style="color: #056CA3;"></i>';
                });
            }
            
            // Form submission loading state
            const confirmForm = document.getElementById('confirmForm');
            const submitBtn = document.getElementById('submitBtn');
            
            if (confirmForm && submitBtn) {
                confirmForm.addEventListener('submit', function(e) {
                    submitBtn.classList.add('loading');
                    submitBtn.disabled = true;
                    
                    // Change button text temporarily
                    const btnText = submitBtn.querySelector('.btn-text');
                    btnText.textContent = 'Verifying...';
                    
                    // Re-enable button after 5 seconds in case of error
                    setTimeout(() => {
                        submitBtn.classList.remove('loading');
                        submitBtn.disabled = false;
                        btnText.textContent = 'Confirm Password';
                    }, 5000);
                });
            }
            
            // Add focus effect for input
            if (passwordInput) {
                passwordInput.addEventListener('focus', function() {
                    const icon = this.parentElement.querySelector('.input-group-text i');
                    if (icon) {
                        icon.style.color = '#065E8C';
                    }
                });
                
                passwordInput.addEventListener('blur', function() {
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
                if (passwordInput.value) {
                    passwordInput.classList.add('has-value');
                }
            }
            
            // Add security context information
            const contextInfo = document.createElement('div');
            contextInfo.className = 'mb-4';
            contextInfo.innerHTML = `
                <div class="d-flex align-items-center mb-2">
                    <span class="security-badge me-2">
                        <i class="bi bi-shield-check"></i>
                        Security Check
                    </span>
                    <span class="text-muted small">Re-authentication required</span>
                </div>
                <div class="alert alert-light border" style="background: #f8f9fa; border-radius: 8px; padding: 0.75rem; font-size: 0.875rem;">
                    <div class="d-flex">
                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                        <div>
                            <p class="mb-1 fw-medium">Why am I seeing this?</p>
                            <p class="mb-0 text-muted">This additional security step helps protect your account from unauthorized changes.</p>
                        </div>
                    </div>
                </div>
            `;
            
            // Insert context info before the form
            const form = document.getElementById('confirmForm');
            if (form) {
                const firstFormGroup = form.querySelector('.mb-4');
                if (firstFormGroup) {
                    firstFormGroup.parentNode.insertBefore(contextInfo, firstFormGroup);
                }
            }
            
            // Add enter key submission
            passwordInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    confirmForm.dispatchEvent(new Event('submit'));
                }
            });
            
            // Auto-focus the password input
            passwordInput.focus();
        });
    </script>
</x-guest-layout>