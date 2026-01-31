<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'Tele-Fleet'))</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

        <style>
            :root {
                --primary: #056CA3;
                --primary-dark: #065E8C;
                --primary-light: rgba(5, 108, 163, 0.1);
                --primary-lighter: rgba(5, 108, 163, 0.05);
                --secondary: #64748b;
                --success: #06d6a0;
                --danger: #ef476f;
                --warning: #ffd166;
                --dark: #1e293b;
                --light: #f8fafc;
                --surface: #ffffff;
                --gradient-primary: linear-gradient(135deg, #056CA3 0%, #065E8C 100%);
                --gradient-light: linear-gradient(135deg, #056CA3 0%, #0A8BC4 100%);
                --gradient-bg: linear-gradient(135deg, #f1f9ff 0%, #e6f2ff 100%);
                --shadow-lg: 0 25px 50px -12px rgba(5, 108, 163, 0.1);
                --shadow-md: 0 10px 25px rgba(5, 108, 163, 0.08);
                --radius-xl: 32px;
                --radius-lg: 24px;
                --radius-md: 16px;
                --radius-sm: 8px;
                --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: "Manrope", -apple-system, BlinkMacSystemFont, sans-serif;
                background: var(--gradient-bg);
                color: var(--dark);
                min-height: 100vh;
                overflow-x: hidden;
            }

            /* Animated Background */
            .auth-background {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: -1;
                overflow: hidden;
            }

            .bg-shape {
                position: absolute;
                border-radius: 50%;
                background: linear-gradient(135deg, rgba(5, 108, 163, 0.08) 0%, rgba(6, 94, 140, 0.04) 100%);
                filter: blur(40px);
                animation: float 20s infinite ease-in-out;
            }

            .bg-shape-1 {
                width: 600px;
                height: 600px;
                top: -200px;
                right: -200px;
                animation-delay: 0s;
            }

            .bg-shape-2 {
                width: 500px;
                height: 500px;
                bottom: -150px;
                left: -150px;
                animation-delay: -5s;
            }

            .bg-shape-3 {
                width: 400px;
                height: 400px;
                top: 50%;
                left: 10%;
                animation-delay: -10s;
            }

            @keyframes float {
                0%, 100% {
                    transform: translate(0, 0) scale(1);
                }
                33% {
                    transform: translate(30px, -50px) scale(1.1);
                }
                66% {
                    transform: translate(-20px, 20px) scale(0.9);
                }
            }

            /* Main Layout */
            .auth-layout {
                min-height: 100vh;
                display: flex;
                align-items: center;
                padding: 2rem;
                position: relative;
            }

            .auth-container {
                width: 100%;
                max-width: 1200px;
                margin: 0 auto;
            }

            /* Left Panel - Brand/Illustration */
            .auth-hero {
                background: var(--gradient-primary);
                border-radius: var(--radius-xl);
                padding: 3rem;
                height: 100%;
                min-height: 420px;
                position: relative;
                overflow: hidden;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                box-shadow: var(--shadow-lg);
            }

            .auth-hero::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.08' fill-rule='evenodd'/%3E%3C/svg%3E");
                opacity: 0.5;
            }

            .brand-header {
                position: relative;
                z-index: 1;
            }

            .brand-logo {
                display: inline-flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 3rem;
                background: rgba(255, 255, 255, 0.15);
                backdrop-filter: blur(10px);
                padding: 1rem 1.5rem;
                border-radius: var(--radius-md);
                width: fit-content;
                transition: var(--transition);
            }

            .brand-logo:hover {
                background: rgba(255, 255, 255, 0.2);
                transform: translateY(-2px);
            }

            .brand-logo i {
                font-size: 1.75rem;
                color: white; /* Made icon white */
            }

            .brand-logo h1 {
                font-size: 1.75rem;
                font-weight: 800;
                margin: 0;
                letter-spacing: -0.5px;
                color: white;
            }

            .hero-content {
                position: relative;
                z-index: 1;
                max-width: 500px;
            }

            .hero-title {
                font-size: 2.5rem;
                font-weight: 800;
                line-height: 1.2;
                margin-bottom: 1.5rem;
                color: white;
            }

            .hero-description {
                font-size: 1.125rem;
                line-height: 1.6;
                opacity: 0.9;
                color: white;
                margin-bottom: 2.5rem;
            }

            .features-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
                margin-bottom: 3rem;
            }

            .feature-item {
                display: flex;
                align-items: center;
                gap: 12px;
                color: white;
                opacity: 0.9;
                font-size: 0.9375rem;
            }

            .feature-icon {
                width: 32px;
                height: 32px;
                background: rgba(255, 255, 255, 0.15);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }

            .feature-icon i {
                color: white;
                font-size: 1rem;
            }

            .hero-footer {
                position: relative;
                z-index: 1;
                color: white;
                opacity: 0.8;
                font-size: 0.875rem;
            }

            /* Right Panel - Form */
            .auth-form-section {
                background: var(--surface);
                border-radius: var(--radius-xl);
                padding: 4rem;
                height: 100%;
                min-height: 460px;
                display: flex;
                flex-direction: column;
                justify-content: center;
                box-shadow: var(--shadow-lg);
                position: relative;
                overflow: hidden;
            }

            .auth-form-section::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: var(--gradient-primary);
            }

            .form-container {
                width: 100%;
                max-width: 400px;
                margin: 0 auto;
            }

            .form-header {
                margin-bottom: 3rem;
                text-align: center;
            }

            .form-title {
                font-size: 2rem;
                font-weight: 700;
                color: var(--dark);
                margin-bottom: 0.5rem;
            }

            .form-subtitle {
                color: var(--secondary);
                font-size: 1rem;
                margin-bottom: 0;
            }

            /* Form Styles */
            .form-group {
                margin-bottom: 1.5rem;
            }

            .form-label {
                display: block;
                margin-bottom: 0.5rem;
                font-weight: 600;
                color: var(--dark);
                font-size: 0.9375rem;
            }

            .input-group {
                position: relative;
            }

            .form-control {
                width: 100%;
                padding: 1rem 1rem 1rem 3rem;
                border: 2px solid #e2e8f0;
                border-radius: var(--radius-md);
                font-size: 1rem;
                font-family: "Manrope", sans-serif;
                transition: var(--transition);
                background: white;
                color: var(--dark);
            }

            .form-control:focus {
                outline: none;
                border-color: var(--primary);
                box-shadow: 0 0 0 3px var(--primary-light);
            }

            .input-icon {
                position: absolute;
                left: 1rem;
                top: 50%;
                transform: translateY(-50%);
                color: var(--secondary);
                font-size: 1.25rem;
                pointer-events: none;
                transition: var(--transition);
            }

            .form-control:focus + .input-icon {
                color: var(--primary);
            }

            .error-message {
                color: var(--danger);
                font-size: 0.875rem;
                margin-top: 0.5rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            /* Checkbox */
            .checkbox-wrapper {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                cursor: pointer;
                margin-bottom: 1.5rem;
            }

            .custom-checkbox {
                width: 20px;
                height: 20px;
                border: 2px solid #e2e8f0;
                border-radius: 6px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: var(--transition);
                flex-shrink: 0;
            }

            .checkbox-wrapper input[type="checkbox"] {
                display: none;
            }

            .checkbox-wrapper input[type="checkbox"]:checked + .custom-checkbox {
                background: var(--primary);
                border-color: var(--primary);
            }

            .checkbox-wrapper input[type="checkbox"]:checked + .custom-checkbox::after {
                content: "✓";
                color: white;
                font-size: 0.875rem;
            }

            .checkbox-wrapper span {
                font-size: 0.9375rem;
                color: var(--dark);
                user-select: none;
            }

            /* Button */
            .auth-btn {
                width: 100%;
                padding: 1rem;
                background: var(--gradient-primary);
                color: white;
                border: none;
                border-radius: var(--radius-md);
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: var(--transition);
                font-family: "Manrope", sans-serif;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                margin-bottom: 1.5rem;
            }

            .auth-btn:hover {
                transform: translateY(-2px);
                box-shadow: var(--shadow-md);
                background: var(--gradient-light);
            }

            .auth-btn:active {
                transform: translateY(0);
            }

            /* Alert */
            .alert {
                padding: 1rem 1.5rem;
                border-radius: var(--radius-md);
                margin-bottom: 2rem;
                border: none;
                display: flex;
                align-items: flex-start;
                gap: 12px;
                font-size: 0.9375rem;
            }

            .alert-success {
                background: rgba(6, 214, 160, 0.1);
                color: #059669;
                border-left: 4px solid #06d6a0;
            }

            .alert-danger {
                background: rgba(239, 71, 111, 0.1);
                color: #dc2626;
                border-left: 4px solid #ef476f;
            }

            .alert-warning {
                background: rgba(255, 209, 102, 0.1);
                color: #d97706;
                border-left: 4px solid #ffd166;
            }

            .alert i {
                font-size: 1.25rem;
                flex-shrink: 0;
            }

            /* Links */
            .auth-link {
                color: var(--primary);
                text-decoration: none;
                font-weight: 600;
                font-size: 0.9375rem;
                transition: var(--transition);
            }

            .auth-link:hover {
                color: var(--primary-dark);
                text-decoration: underline;
            }

            .link-group {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 2rem;
            }

            /* Footer */
            .auth-footer {
                margin-top: 3rem;
                text-align: center;
                padding-top: 2rem;
                border-top: 1px solid #e2e8f0;
            }

            .auth-footer p {
                color: var(--secondary);
                font-size: 0.9375rem;
            }

            /* Back Link */
            .back-link {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                color: var(--secondary);
                text-decoration: none;
                font-weight: 500;
                margin-bottom: 2rem;
                transition: var(--transition);
            }

            .back-link:hover {
                color: var(--primary);
                gap: 12px;
            }

            /* Loading State */
            .auth-btn.loading {
                position: relative;
                color: transparent;
            }

            .auth-btn.loading::after {
                content: '';
                position: absolute;
                width: 20px;
                height: 20px;
                border: 2px solid rgba(255, 255, 255, 0.3);
                border-top-color: white;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                to { transform: rotate(360deg); }
            }

            /* Responsive Design */
            @media (max-width: 1199.98px) {
                .auth-hero,
                .auth-form-section {
                    padding: 3rem;
                }
                
            }

            @media (max-width: 991.98px) {
                .auth-layout {
                    padding: 1rem;
                }

                .auth-hero {
                    min-height: 400px;
                    margin-bottom: 2rem;
                    border-radius: var(--radius-lg);
                }

                .auth-form-section {
                    min-height: auto;
                    border-radius: var(--radius-lg);
                }

                .hero-title {
                    font-size: 2rem;
                }

                .form-title {
                    font-size: 1.75rem;
                }
                
            }

            @media (max-width: 767.98px) {
                .auth-hero,
                .auth-form-section {
                    padding: 2rem;
                }

                .features-grid {
                    grid-template-columns: 1fr;
                }

                .brand-logo {
                    padding: 0.75rem 1.25rem;
                }

                .brand-logo h1 {
                    font-size: 1.5rem;
                }

                .hero-title {
                    font-size: 1.75rem;
                }

                .form-title {
                    font-size: 1.5rem;
                }

                .link-group {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 1rem;
                }
                
            }

            @media (max-width: 575.98px) {
                .auth-hero,
                .auth-form-section {
                    padding: 1.5rem;
                }

                .auth-layout {
                    padding: 0.5rem;
                }

                .bg-shape {
                    display: none;
                }
                
            }
        </style>
    </head>
    <body>
        <!-- Animated Background -->
        <div class="auth-background">
            <div class="bg-shape bg-shape-1"></div>
            <div class="bg-shape bg-shape-2"></div>
            <div class="bg-shape bg-shape-3"></div>
        </div>

        <!-- Main Layout -->
        <div class="auth-layout">
            <div class="auth-container">
                <div class="row g-4">
                    <!-- Left Panel - Brand/Illustration -->
                    <div class="col-lg-6 col-xl-6">
                        <div class="auth-hero">
                            <div class="brand-header">
                                <a href="{{ url('/') }}" class="brand-logo text-decoration-none">
                                    <i class="bi bi-truck"></i>
                                    <h1>Tele-Fleet</h1>
                                </a>
                            </div>

                            <div class="hero-content">
                                <h2 class="hero-title">
                                    @yield('hero-title', 'Enterprise-Grade Fleet Management')
                                </h2>
                                <p class="hero-description">
                                    @yield('hero-description', 'Streamline Your Corporate Fleet Operations with comprehensive vehicle management, trip coordination, and real-time tracking.')
                                </p>

                                <div class="features-grid">
                                    <div class="feature-item">
                                        <div class="feature-icon">
                                            <i class="bi bi-shield-check"></i>
                                        </div>
                                        <span>Enterprise Security</span>
                                    </div>
                                    <div class="feature-item">
                                        <div class="feature-icon">
                                            <i class="bi bi-speedometer2"></i>
                                        </div>
                                        <span>Real-time Tracking</span>
                                    </div>
                                    <div class="feature-item">
                                        <div class="feature-icon">
                                            <i class="bi bi-graph-up"></i>
                                        </div>
                                        <span>Advanced Analytics</span>
                                    </div>
                                    <div class="feature-item">
                                        <div class="feature-icon">
                                            <i class="bi bi-bell"></i>
                                        </div>
                                        <span>Smart Alerts</span>
                                    </div>
                                </div>
                            </div>

                            <div class="hero-footer">
                                © {{ date('Y') }} Tele-Fleet. All rights reserved.
                            </div>
                        </div>
                    </div>

                    <!-- Right Panel - Form -->
                    <div class="col-lg-6 col-xl-6">
                        <div class="auth-form-section">
                            @if(isset($backUrl))
                                <a href="{{ $backUrl }}" class="back-link">
                                    <i class="bi bi-arrow-left"></i>
                                    Back
                                </a>
                            @endif

                            <div class="form-container">
                                <div class="form-header">
                                    <h1 class="form-title">
                                        @yield('form-title', 'Welcome Back')
                                    </h1>
                                    <p class="form-subtitle">
                                        @yield('form-subtitle', 'Access the fleet operations dashboard')
                                    </p>
                                </div>

                                @if(session('status'))
                                    <div class="alert alert-success">
                                        <i class="bi bi-check-circle-fill"></i>
                                        {{ session('status') }}
                                    </div>
                                @endif

                                @if(session('error'))
                                    <div class="alert alert-danger">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                        {{ session('error') }}
                                    </div>
                                @endif

                                {{ $slot }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $supportEmail = config('app.support_email') ?? config('mail.from.address') ?? 'support@tele-fleet.test';
        @endphp
        <div class="modal fade" id="supportContactModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title">Contact Support</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">Need help signing in? Contact the system administrator:</p>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-envelope-fill text-primary"></i>
                            <a href="mailto:{{ $supportEmail }}" class="fw-semibold text-decoration-none">{{ $supportEmail }}</a>
                        </div>
                        <p class="text-muted small mt-3 mb-0">Please include your full name, branch, and a brief description of the issue.</p>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Form submission loading animation
            document.addEventListener('submit', function(e) {
                if (e.target.matches('form')) {
                    const submitBtn = e.target.querySelector('.auth-btn');
                    if (submitBtn) {
                        submitBtn.classList.add('loading');
                        submitBtn.disabled = true;
                    }
                }
            });

            // Form input validation styling
            document.addEventListener('DOMContentLoaded', function() {
                const inputs = document.querySelectorAll('.form-control');
                inputs.forEach(input => {
                    // Check initial value
                    if (input.value.trim() !== '') {
                        input.classList.add('has-value');
                    }

                    // Add focus effect
                    input.addEventListener('focus', function() {
                        const icon = this.parentElement.querySelector('.input-icon');
                        if (icon) icon.style.color = '#056CA3';
                    });

                    input.addEventListener('blur', function() {
                        const icon = this.parentElement.querySelector('.input-icon');
                        if (icon) icon.style.color = '#64748b';
                        
                        if (this.value.trim() !== '') {
                            this.classList.add('has-value');
                        } else {
                            this.classList.remove('has-value');
                        }
                    });
                });

                // Add animation to brand logo on hover
                const brandLogo = document.querySelector('.brand-logo');
                if (brandLogo) {
                    brandLogo.addEventListener('mouseenter', function() {
                        const icon = this.querySelector('i');
                        if (icon) {
                            icon.style.transform = 'rotate(-10deg) scale(1.1)';
                        }
                    });

                    brandLogo.addEventListener('mouseleave', function() {
                        const icon = this.querySelector('i');
                        if (icon) {
                            icon.style.transform = 'rotate(0) scale(1)';
                        }
                    });
                }
            });

            // Password visibility toggle (for password reset pages)
            function togglePasswordVisibility(inputId) {
                const input = document.getElementById(inputId);
                const icon = document.querySelector(`[data-toggle="${inputId}"]`);
                
                if (input.type === 'password') {
                    input.type = 'text';
                    if (icon) icon.classList.replace('bi-eye', 'bi-eye-slash');
                } else {
                    input.type = 'password';
                    if (icon) icon.classList.replace('bi-eye-slash', 'bi-eye');
                }
            }
        </script>
        @stack('scripts')
    </body>
</html>
