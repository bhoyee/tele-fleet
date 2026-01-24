<x-guest-layout>
    <div class="text-center mb-5">
        <div class="avatar-circle mb-3">
            <i class="bi bi-person-circle"></i>
        </div>
        <h1 class="h2 fw-bold text-dark mb-2">Welcome Back</h1>
        <p class="text-muted mb-4">Sign in to access your fleet management dashboard</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <div>{{ session('status') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" id="loginForm">
        @csrf

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
                       value="{{ old('email') }}" 
                       required 
                       autofocus 
                       autocomplete="username"
                       placeholder="Enter your email">
            </div>
            @error('email') 
                <div class="d-flex align-items-center text-danger small mt-2">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    <span>{{ $message }}</span>
                </div>
            @enderror
        </div>

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
                <button class="btn btn-outline-secondary border-start-0" type="button" id="togglePassword" style="border-color: #dee2e6;">
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
            <div class="form-check">
                <input id="remember_me" class="form-check-input" type="checkbox" name="remember" style="width: 18px; height: 18px;">
                <label class="form-check-label ms-2 text-dark" for="remember_me">
                    Remember me
                </label>
            </div>
        </div>

        <button class="btn w-100 py-3 mb-4 fw-semibold" type="submit" id="submitBtn" style="background: #056CA3; color: white;">
            <span class="btn-text">Sign In</span>
            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
        </button>

        <div class="text-center pt-4 border-top">
            <p class="text-muted mb-0">Don't have an account? 
                <a href="#" class="fw-semibold text-decoration-none" style="color: #056CA3;">Contact Administrator</a>
            </p>
        </div>
    </form>

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
            border-left: 4px solid #056CA3;
            border-radius: 8px;
            background: rgba(5, 108, 163, 0.1);
        }
        
        .alert-success {
            border-left: 4px solid #28a745;
            background: rgba(40, 167, 69, 0.1);
        }
        
        .form-label {
            font-size: 0.95rem;
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
        
        /* Custom focus styles */
        .form-check-input:checked {
            background-color: #056CA3;
            border-color: #056CA3;
        }
        
        .form-check-input:focus {
            border-color: #056CA3;
            box-shadow: 0 0 0 0.25rem rgba(5, 108, 163, 0.25);
        }
        
        /* Link hover effects */
        a:hover {
            color: #065E8C !important;
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
            const loginForm = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');
            
            if (loginForm && submitBtn) {
                loginForm.addEventListener('submit', function(e) {
                    submitBtn.classList.add('loading');
                    submitBtn.disabled = true;
                    
                    // Re-enable button after 5 seconds in case of error
                    setTimeout(() => {
                        submitBtn.classList.remove('loading');
                        submitBtn.disabled = false;
                    }, 5000);
                });
            }
            
            // Add floating label effect
            const inputs = document.querySelectorAll('input[type="email"], input[type="password"]');
            inputs.forEach(input => {
                // Check if input has value on page load
                if (input.value) {
                    input.classList.add('has-value');
                }
                
                // Add focus effect for icons
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
                    
                    // Add/remove has-value class
                    if (this.value) {
                        this.classList.add('has-value');
                    } else {
                        this.classList.remove('has-value');
                    }
                });
                
                // Add/remove class on input events
                input.addEventListener('input', function() {
                    if (this.value) {
                        this.classList.add('has-value');
                    } else {
                        this.classList.remove('has-value');
                    }
                });
            });
            
            // Add hover effect to forgot password link
            const forgotPasswordLink = document.querySelector('a[href*="password.request"]');
            if (forgotPasswordLink) {
                forgotPasswordLink.addEventListener('mouseenter', function() {
                    this.style.textDecoration = 'underline';
                });
                
                forgotPasswordLink.addEventListener('mouseleave', function() {
                    this.style.textDecoration = 'none';
                });
            }
        });
    </script>
</x-guest-layout>