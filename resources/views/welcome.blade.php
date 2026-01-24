<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Tele-Fleet: Enterprise-grade fleet management system for corporate vehicle operations. Streamline trips, manage drivers, track maintenance, and optimize fleet performance.">

    <title>{{ config('app.name', 'Tele-Fleet') }} | Corporate Fleet Management</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-50: #eff6ff;
            --primary-100: #dbeafe;
            --primary-500: #0c4a6e;
            --primary-600: #075985;
            --primary-700: #0369a1;
            --primary-800: #0284c7;
            --primary-900: #0ea5e9;
            
            --accent-500: #ea580c;
            --accent-600: #c2410c;
            
            --neutral-50: #f8fafc;
            --neutral-100: #f1f5f9;
            --neutral-200: #e2e8f0;
            --neutral-800: #1e293b;
            --neutral-900: #0f172a;
            
            --success: #059669;
            --warning: #d97706;
            --error: #dc2626;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--neutral-900);
            background: linear-gradient(135deg, #ffffff 0%, var(--neutral-50) 100%);
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
        }

        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 0;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-500), var(--primary-800));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-text {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-500), var(--primary-800));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Hero Section */
        .hero-section {
            position: relative;
            padding: 6rem 0 4rem;
            background: linear-gradient(135deg, var(--neutral-50) 0%, #ffffff 100%);
            overflow: hidden;
        }

        .hero-background {
            position: absolute;
            top: 0;
            right: 0;
            width: 50%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=1200&q=80');
            background-size: cover;
            background-position: center;
            clip-path: polygon(20% 0, 100% 0, 100% 100%, 0 100%);
            opacity: 0.1;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(12, 74, 110, 0.1);
            color: var(--primary-500);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }

        .hero-title {
            font-size: 3.5rem;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--neutral-900) 0%, var(--primary-600) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--neutral-800);
            line-height: 1.6;
            margin-bottom: 2rem;
            max-width: 600px;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 3rem;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-600), var(--primary-800));
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(2, 132, 199, 0.2);
        }

        .btn-outline-custom {
            background: transparent;
            color: var(--primary-600);
            border: 2px solid var(--primary-200);
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-custom:hover {
            background: var(--primary-50);
            border-color: var(--primary-300);
            transform: translateY(-2px);
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 4rem;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--neutral-200);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary-100), var(--primary-200));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-500), var(--primary-800));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }

        /* Features Section */
        .section-title {
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }

        .section-subtitle {
            color: var(--neutral-800);
            text-align: center;
            font-size: 1.125rem;
            max-width: 600px;
            margin: 0 auto 4rem;
        }

        .features-section {
            padding: 6rem 0;
            background: white;
        }

        .feature-card {
            background: white;
            border-radius: 16px;
            padding: 2.5rem 2rem;
            height: 100%;
            border: 1px solid var(--neutral-200);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-500), var(--primary-800));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .feature-card:hover::before {
            transform: scaleX(1);
        }

        .feature-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--primary-500), var(--primary-800));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        /* Process Section */
        .process-section {
            padding: 6rem 0;
            background: linear-gradient(135deg, var(--neutral-50) 0%, #ffffff 100%);
        }

        .process-step {
            text-align: center;
            position: relative;
            padding: 0 1rem;
        }

        .process-step-number {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-500), var(--primary-800));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 auto 1.5rem;
            position: relative;
            z-index: 2;
        }

        .process-line {
            position: absolute;
            top: 30px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, var(--primary-200), var(--primary-400), var(--primary-200));
            z-index: 1;
        }

        /* Dashboard Preview */
        .dashboard-section {
            padding: 6rem 0;
            background: white;
        }

        .dashboard-preview {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--neutral-200);
            position: relative;
            overflow: hidden;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .dashboard-metrics {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .metric-item {
            background: var(--neutral-50);
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, var(--neutral-900) 0%, var(--neutral-800) 100%);
            color: white;
            padding: 4rem 0 2rem;
        }

        .footer-brand {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 0.5rem;
        }

        .footer-links a {
            color: var(--neutral-200);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .cta-buttons {
                flex-direction: column;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .dashboard-metrics {
                grid-template-columns: 1fr;
            }
            
            .process-line {
                display: none;
            }
        }

        /* Animations */
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .animate-on-scroll.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top" id="navbar">
        <div class="container">
            <div class="brand-logo">
                <div class="brand-icon">
                    <i class="fas fa-truck-moving text-white"></i>
                </div>
                <span class="brand-text">Tele-Fleet</span>
            </div>
            
            <div class="ms-auto">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary-custom">
                        <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary-custom">
                        <i class="fas fa-sign-in-alt me-2"></i>Employee Login
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-background"></div>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-badge animate-on-scroll">
                        <i class="fas fa-shield-check"></i>
                        Enterprise-Grade Fleet Management
                    </div>
                    
                    <h1 class="hero-title animate-on-scroll">
                        Streamline Your Corporate<br>
                        <span style="color: var(--primary-500);">Fleet Operations</span>
                    </h1>
                    
                    <p class="hero-subtitle animate-on-scroll">
                        Tele-Fleet provides comprehensive vehicle management, trip coordination, and real-time tracking for corporate fleets across multiple branches.
                    </p>
                    
                    <div class="cta-buttons animate-on-scroll">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-primary-custom">
                                <i class="fas fa-rocket me-2"></i>Launch Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary-custom">
                                <i class="fas fa-lock me-2"></i>Secure Employee Login
                            </a>
                        @endauth
                        <a href="#features" class="btn btn-outline-custom">
                            <i class="fas fa-play-circle me-2"></i>Watch Overview
                        </a>
                    </div>
                    
                    <!-- Stats -->
                    <div class="stats-container animate-on-scroll">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-car text-primary-600"></i>
                            </div>
                            <div class="stat-number">150+</div>
                            <p class="text-muted">Managed Vehicles</p>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users text-primary-600"></i>
                            </div>
                            <div class="stat-number">85+</div>
                            <p class="text-muted">Professional Drivers</p>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-map-marker-alt text-primary-600"></i>
                            </div>
                            <div class="stat-number">15+</div>
                            <p class="text-muted">Branch Locations</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <!-- Dashboard Preview Card -->
                    <div class="dashboard-preview animate-on-scroll" style="animation-delay: 0.2s;">
                        <div class="dashboard-header">
                            <div>
                                <h5 class="mb-1">Live Fleet Dashboard</h5>
                                <p class="text-muted small">Real-time operations monitoring</p>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-circle me-1"></i>
                                <small>All Systems Operational</small>
                            </div>
                        </div>
                        
                        <div class="dashboard-metrics">
                            <div class="metric-item">
                                <div class="text-muted small">Active Trips</div>
                                <div class="h4 mb-0">24</div>
                            </div>
                            <div class="metric-item">
                                <div class="text-muted small">Available Vehicles</div>
                                <div class="h4 mb-0">47</div>
                            </div>
                            <div class="metric-item">
                                <div class="text-muted small">Pending Requests</div>
                                <div class="h4 mb-0">12</div>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" style="width: 95%"></div>
                            </div>
                            <small class="text-muted">Vehicle utilization rate: 95%</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <h2 class="section-title animate-on-scroll">Powerful Fleet Management Features</h2>
            <p class="section-subtitle animate-on-scroll">
                Comprehensive tools designed for corporate fleet efficiency and compliance
            </p>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card animate-on-scroll">
                        <div class="feature-icon">
                            <i class="fas fa-car text-white fs-4"></i>
                        </div>
                        <h4 class="mb-3">Vehicle Management</h4>
                        <p class="text-muted">
                            Complete lifecycle tracking with maintenance scheduling, insurance monitoring, and fuel consumption analytics.
                        </p>
                        <ul class="list-unstyled mt-3">
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Maintenance scheduling</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Document tracking</li>
                            <li><i class="fas fa-check-circle text-success me-2"></i> Fuel efficiency analytics</li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card animate-on-scroll" style="animation-delay: 0.1s;">
                        <div class="feature-icon">
                            <i class="fas fa-route text-white fs-4"></i>
                        </div>
                        <h4 class="mb-3">Trip Management</h4>
                        <p class="text-muted">
                            Streamlined trip requests, approvals, and assignments with automated notifications and real-time tracking.
                        </p>
                        <ul class="list-unstyled mt-3">
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Automated workflow</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Real-time tracking</li>
                            <li><i class="fas fa-check-circle text-success me-2"></i> Digital logbook system</li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card animate-on-scroll" style="animation-delay: 0.2s;">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line text-white fs-4"></i>
                        </div>
                        <h4 class="mb-3">Analytics & Reporting</h4>
                        <p class="text-muted">
                            Comprehensive dashboards and customizable reports for data-driven decision making and cost optimization.
                        </p>
                        <ul class="list-unstyled mt-3">
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Real-time dashboards</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Cost analysis</li>
                            <li><i class="fas fa-check-circle text-success me-2"></i> Custom report builder</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Process Section -->
    <section class="process-section">
        <div class="container">
            <h2 class="section-title animate-on-scroll">Streamlined Operational Workflow</h2>
            <p class="section-subtitle animate-on-scroll">
                Efficient trip management from request to completion
            </p>
            
            <div class="row position-relative">
                <div class="process-line d-none d-lg-block"></div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="process-step animate-on-scroll">
                        <div class="process-step-number">1</div>
                        <h5 class="mb-3">Request Submission</h5>
                        <p class="text-muted">Branch admins submit trip requests with full details</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="process-step animate-on-scroll" style="animation-delay: 0.1s;">
                        <div class="process-step-number">2</div>
                        <h5 class="mb-3">Approval Workflow</h5>
                        <p class="text-muted">Fleet managers review and approve requests</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="process-step animate-on-scroll" style="animation-delay: 0.2s;">
                        <div class="process-step-number">3</div>
                        <h5 class="mb-3">Vehicle Assignment</h5>
                        <p class="text-muted">Optimal vehicle and driver assignment</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="process-step animate-on-scroll" style="animation-delay: 0.3s;">
                        <div class="process-step-number">4</div>
                        <h5 class="mb-3">Trip Completion</h5>
                        <p class="text-muted">Logbook entry and automated reporting</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-6 bg-primary-50">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="mb-4 animate-on-scroll">Ready to Optimize Your Fleet Operations?</h2>
                    <p class="lead mb-5 animate-on-scroll" style="animation-delay: 0.1s;">
                        Join leading companies that trust Tele-Fleet for their corporate transportation management.
                    </p>
                    <div class="animate-on-scroll" style="animation-delay: 0.2s;">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-primary-custom btn-lg">
                                <i class="fas fa-tachometer-alt me-2"></i>Access Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary-custom btn-lg">
                                <i class="fas fa-lock me-2"></i>Secure Employee Login
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="brand-logo mb-4">
                        <div class="brand-icon">
                            <i class="fas fa-truck-moving text-white"></i>
                        </div>
                        <span class="brand-text text-white">Tele-Fleet</span>
                    </div>
                    <p class="text-neutral-200 mb-0">
                        Enterprise-grade fleet management system for corporate transportation efficiency.
                    </p>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="mb-3">Company</h5>
                    <ul class="footer-links">
                        <li><a href="#features">Features</a></li>
                        <li><a href="#process">Process</a></li>
                        <li><a href="#dashboard">Dashboard</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="mb-3">Resources</h5>
                    <ul class="footer-links">
                        <li><a href="#">Documentation</a></li>
                        <li><a href="#">Support</a></li>
                        <li><a href="#">API Reference</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-4 mb-4">
                    <h5 class="mb-3">Contact</h5>
                    <ul class="footer-links">
                        <li><i class="fas fa-envelope me-2"></i> fleet@company.com</li>
                        <li><i class="fas fa-phone me-2"></i> +234 800 123 4567</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> Corporate Headquarters</li>
                    </ul>
                </div>
            </div>
            
            <hr class="my-4 border-neutral-700">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-neutral-300">&copy; {{ now()->year }} Tele-Fleet. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0 text-neutral-300">Enterprise Fleet Management System</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Scroll animations
        function animateOnScroll() {
            const elements = document.querySelectorAll('.animate-on-scroll');
            
            elements.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const windowHeight = window.innerHeight;
                
                if (elementTop < windowHeight - 100) {
                    element.classList.add('visible');
                }
            });
        }

        // Initial call
        animateOnScroll();

        // Listen for scroll events
        window.addEventListener('scroll', animateOnScroll);

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>