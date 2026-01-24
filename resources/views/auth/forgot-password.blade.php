<x-guest-layout>
    <div class="text-center mb-5">
        <div class="avatar-circle mb-3">
            <i class="bi bi-key"></i>
        </div>
        <h1 class="h2 fw-bold text-dark mb-2">Reset Password</h1>
        <p class="text-muted mb-4">Enter your email and we'll send you a reset link</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-4" role="alert" style="border-left: 4px solid #056CA3; background: rgba(5, 108, 163, 0.1);">
            <i class="bi bi-check-circle-fill me-2" style="color: #056CA3;"></i>
            <div class="flex-grow-1">{{ session('status') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" id="resetForm">
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
                       placeholder="Enter your registered email">
            </div>
            @error('email') 
                <div class="d-flex align-items-center text-danger small mt-2">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    <span>{{ $message }}</span>
                </div>
            @enderror
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
            <a href="{{ route('login') }}" class="text-decoration-none" style="color: #056CA3;">
                <i class="bi bi-arrow-left me-1"></i> Back to Login
            </a>
            <button class="btn fw-semibold px-4 py-2" type="submit" id="submitBtn" 
                    style="background: #056CA3; color: white; border-radius: 8px;">
                <span class="btn-text">Send Reset Link</span>
                <span class="spinner-border spinner-border-sm d-none" role="status"></span>
            </button>
        </div>
    </form>

    <div class="text-center pt-4 mt-4 border-top">
        <p class="text-muted small mb-2">Need help with your account?</p>
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
        
        /* Instruction box */
        .instruction-box {
            background: rgba(5, 108, 163, 0.05);
            border-left: 4px solid #056CA3;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 2rem;
        }
        
        .instruction-box p {
            margin: 0;
            font-size: 0.9rem;
            color: #495057;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form submission loading state
            const resetForm = document.getElementById('resetForm');
            const submitBtn = document.getElementById('submitBtn');
            
            if (resetForm && submitBtn) {
                resetForm.addEventListener('submit', function(e) {
                    submitBtn.classList.add('loading');
                    submitBtn.disabled = true;
                    
                    // Change button text temporarily
                    const btnText = submitBtn.querySelector('.btn-text');
                    btnText.textContent = 'Sending...';
                    
                    // Re-enable button after 5 seconds in case of error
                    setTimeout(() => {
                        submitBtn.classList.remove('loading');
                        submitBtn.disabled = false;
                        btnText.textContent = 'Send Reset Link';
                    }, 5000);
                });
            }
            
            // Add focus effect for input
            const emailInput = document.getElementById('email');
            if (emailInput) {
                emailInput.addEventListener('focus', function() {
                    const icon = this.parentElement.querySelector('.input-group-text i');
                    if (icon) {
                        icon.style.color = '#065E8C';
                    }
                });
                
                emailInput.addEventListener('blur', function() {
                    const icon = this.parentElement.querySelector('.input-group-text i');
                    if (icon) {
                        icon.style.color = '#056CA3';
                    }
                });
                
                // Check if input has value on page load
                if (emailInput.value) {
                    emailInput.classList.add('has-value');
                }
                
                // Add/remove class on input events
                emailInput.addEventListener('input', function() {
                    if (this.value) {
                        this.classList.add('has-value');
                    } else {
                        this.classList.remove('has-value');
                    }
                });
            }
            
            // Add instruction box for password reset process
            const instructionBox = document.createElement('div');
            instructionBox.className = 'instruction-box';
            instructionBox.innerHTML = `
                <p>
                    <i class="bi bi-info-circle me-1" style="color: #056CA3;"></i>
                    We'll send you a secure link to reset your password. Check your email inbox.
                </p>
            `;
            
            // Insert instruction box before the form
            const form = document.getElementById('resetForm');
            if (form && form.parentNode) {
                form.parentNode.insertBefore(instructionBox, form);
            }
        });
    </script>
</x-guest-layout>