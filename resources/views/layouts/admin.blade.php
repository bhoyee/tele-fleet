<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Tele-Fleet') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700&display=swap" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

        <style>
            :root {
                --primary: #4361ee;
                --primary-dark: #3a56d4;
                --secondary: #6c757d;
                --success: #06d6a0;
                --danger: #ef476f;
                --warning: #ffd166;
                --info: #118ab2;
                --dark: #2b2d42;
                --light: #f8f9fa;
                --sidebar-bg: #ffffff;
                --sidebar-width: 260px;
                --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
                --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            body {
                font-family: "Manrope", system-ui, -apple-system, sans-serif;
                background: #f5f7fb;
                color: #333;
                overflow-x: hidden;
            }

            /* Sidebar Styling */
            .sidebar {
                width: var(--sidebar-width);
                background: var(--sidebar-bg);
                border-right: 1px solid rgba(0, 0, 0, 0.08);
                position: fixed;
                height: 100vh;
                left: 0;
                top: 0;
                z-index: 1000;
                transition: var(--transition);
                padding: 0;
                box-shadow: 2px 0 12px rgba(0, 0, 0, 0.03);
            }

            .sidebar-brand {
                padding: 1.5rem 1.5rem 1rem;
                border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            }

            .sidebar-brand h2 {
                font-weight: 700;
                color: var(--primary);
                margin: 0;
                font-size: 1.5rem;
                letter-spacing: -0.5px;
            }

            .sidebar-nav {
                padding: 1.5rem 0.75rem;
            }

            .nav-item {
                margin-bottom: 0.5rem;
            }

            .nav-link {
                color: #64748b;
                padding: 0.75rem 1rem;
                border-radius: 10px;
                display: flex;
                align-items: center;
                transition: var(--transition);
                font-weight: 500;
                position: relative;
            }

            .nav-link:hover {
                color: var(--primary);
                background: rgba(67, 97, 238, 0.08);
                transform: translateX(4px);
            }

            .nav-link.active {
                color: var(--primary);
                background: rgba(67, 97, 238, 0.12);
                font-weight: 600;
            }

            .nav-link.active::before {
                content: '';
                position: absolute;
                left: 0;
                top: 0;
                height: 100%;
                width: 4px;
                background: var(--primary);
                border-radius: 0 4px 4px 0;
            }

            .nav-icon {
                margin-right: 0.75rem;
                font-size: 1.2rem;
                width: 24px;
                text-align: center;
            }

            /* Main Content */
            .main-content {
                margin-left: var(--sidebar-width);
                min-height: 100vh;
                transition: var(--transition);
                padding: 0;
            }

            /* Topbar */
            .topbar {
                background: #ffffff;
                border-bottom: 1px solid rgba(0, 0, 0, 0.08);
                padding: 1rem 2rem;
                position: sticky;
                top: 0;
                z-index: 100;
                backdrop-filter: blur(10px);
                background: rgba(255, 255, 255, 0.95);
            }

            .user-dropdown {
                border: none;
                background: transparent;
                color: var(--dark);
                padding: 0.5rem 1rem;
                border-radius: 10px;
                transition: var(--transition);
            }

            .user-dropdown:hover {
                background: rgba(0, 0, 0, 0.04);
            }

            .user-avatar {
                width: 36px;
                height: 36px;
                background: var(--primary);
                color: white;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 600;
                margin-right: 0.75rem;
            }

            /* Cards */
            .card {
                border: none;
                border-radius: 16px;
                box-shadow: var(--card-shadow);
                transition: var(--transition);
                background: white;
                overflow: hidden;
            }

            .card:hover {
                transform: translateY(-4px);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            }

            .card-header {
                background: transparent;
                border-bottom: 1px solid rgba(0, 0, 0, 0.08);
                padding: 1.25rem 1.5rem;
                font-weight: 600;
            }

            .card-body {
                padding: 1.5rem;
            }

            /* Stats Cards */
            .stat-card {
                border-left: 4px solid var(--primary);
                padding: 1.25rem;
            }

            .stat-card .stat-value {
                font-size: 2rem;
                font-weight: 700;
                color: var(--dark);
                line-height: 1;
            }

            .stat-card .stat-label {
                color: #64748b;
                font-size: 0.875rem;
                font-weight: 500;
                margin-bottom: 0.5rem;
            }

            .stat-card .stat-change {
                font-size: 0.875rem;
                font-weight: 600;
                display: flex;
                align-items: center;
            }

            .stat-card .stat-change.positive {
                color: var(--success);
            }

            .stat-card .stat-change.negative {
                color: var(--danger);
            }

            /* Buttons */
            .btn-primary {
                background: var(--primary);
                border: none;
                border-radius: 10px;
                padding: 0.625rem 1.5rem;
                font-weight: 600;
                transition: var(--transition);
            }

            .btn-primary:hover {
                background: var(--primary-dark);
                transform: translateY(-2px);
            }

            /* Alert */
            .alert {
                border: none;
                border-radius: 12px;
                padding: 1rem 1.5rem;
                margin-bottom: 1.5rem;
                box-shadow: var(--card-shadow);
            }

            /* Footer */
            .app-footer {
                background: white;
                border-top: 1px solid rgba(0, 0, 0, 0.08);
                padding: 1.5rem 2rem;
                margin-top: auto;
            }

            /* Mobile Responsiveness */
            @media (max-width: 992px) {
                .sidebar {
                    transform: translateX(-100%);
                }
                
                .sidebar.active {
                    transform: translateX(0);
                }
                
                .main-content {
                    margin-left: 0;
                }
                
                .topbar .navbar-toggler {
                    display: block;
                }
            }

            /* Custom Scrollbar */
            ::-webkit-scrollbar {
                width: 6px;
            }

            ::-webkit-scrollbar-track {
                background: #f1f1f1;
            }

            ::-webkit-scrollbar-thumb {
                background: #c1c1c1;
                border-radius: 3px;
            }

            ::-webkit-scrollbar-thumb:hover {
                background: #a8a8a8;
            }

            /* Active State Enhancement */
            .active {
                position: relative;
            }

            /* Content Padding */
            .content-wrapper {
                padding: 2rem;
                max-width: 1400px;
                margin: 0 auto;
                width: 100%;
            }

            /* Page Header */
            .page-header {
                margin-bottom: 2rem;
            }

            .page-header h1 {
                font-weight: 700;
                color: var(--dark);
                margin-bottom: 0.5rem;
            }

            .page-header .breadcrumb {
                background: transparent;
                padding: 0;
                color: #64748b;
            }

            /* Table Styling */
            .table {
                background: white;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: var(--card-shadow);
            }

            .table thead th {
                border-bottom: 2px solid rgba(0, 0, 0, 0.08);
                background: #f8fafc;
                padding: 1rem 1.5rem;
                font-weight: 600;
                color: var(--dark);
            }

            .table tbody td {
                padding: 1rem 1.5rem;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            }
        </style>
    </head>
    <body>
        <div class="app-shell d-flex">
            <!-- Sidebar -->
            <aside class="sidebar">
                <div class="sidebar-brand">
                    <h2>
                        <i class="bi bi-truck me-2"></i>
                        Tele-Fleet
                    </h2>
                </div>
                
                <nav class="sidebar-nav">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link @if (request()->routeIs('dashboard')) active @endif" href="{{ route('dashboard') }}">
                                <i class="bi bi-speedometer2 nav-icon"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        
                        @if (auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                            <li class="nav-item">
                                <a class="nav-link @if (request()->routeIs('admin.users.*')) active @endif" href="{{ route('admin.users.index') }}">
                                    <i class="bi bi-people nav-icon"></i>
                                    <span>Users</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link @if (request()->routeIs('branches.*')) active @endif" href="{{ route('branches.index') }}">
                                    <i class="bi bi-building nav-icon"></i>
                                    <span>Branches</span>
                                </a>
                            </li>
                        @endif
                        
                        @if (in_array(auth()->user()?->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true))
                            <li class="nav-item">
                                <a class="nav-link @if (request()->routeIs('vehicles.*')) active @endif" href="{{ route('vehicles.index') }}">
                                    <i class="bi bi-car-front nav-icon"></i>
                                    <span>Vehicles</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link @if (request()->routeIs('drivers.*')) active @endif" href="{{ route('drivers.index') }}">
                                    <i class="bi bi-person-badge nav-icon"></i>
                                    <span>Drivers</span>
                                </a>
                            </li>
                        @endif
                        
                        @if (in_array(auth()->user()?->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER, \App\Models\User::ROLE_BRANCH_ADMIN, \App\Models\User::ROLE_BRANCH_HEAD], true))
                            <li class="nav-item">
                                <a class="nav-link @if (request()->routeIs('trips.*')) active @endif" href="{{ route('trips.index') }}">
                                    <i class="bi bi-map nav-icon"></i>
                                    <span>Trips</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </nav>
            </aside>

            <!-- Main Content -->
            <div class="main-content flex-grow-1">
                <!-- Topbar -->
                <header class="topbar d-flex justify-content-between align-items-center">
                    <div>
                        <button class="btn btn-outline-secondary d-lg-none me-3" id="sidebarToggle">
                            <i class="bi bi-list"></i>
                        </button>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="#" class="text-decoration-none">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">
                                    @php
                                        $routeName = request()->route()->getName();
                                        echo ucwords(str_replace(['.', '-'], ' ', $routeName));
                                    @endphp
                                </li>
                            </ol>
                        </nav>
                    </div>
                    
                    <div class="d-flex align-items-center gap-3">
                        <a href="{{ route('notifications.index') }}" class="btn btn-light position-relative">
                            <i class="bi bi-bell"></i>
                            @php($unreadCount = auth()->user()?->unreadNotifications()->count() ?? 0)
                            @if ($unreadCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    {{ $unreadCount }}
                                </span>
                            @endif
                        </a>

                        <div class="dropdown">
                        <button class="btn user-dropdown d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                            <div class="user-avatar me-2">
                                {{ strtoupper(substr(auth()->user()?->name, 0, 1)) }}
                            </div>
                            <div class="d-flex flex-column text-start">
                                <span class="fw-semibold">{{ auth()->user()?->name }}</span>
                                <small class="text-muted">{{ auth()->user()?->role }}</small>
                            </div>
                            <i class="bi bi-chevron-down ms-2"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3" style="min-width: 200px;">
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="{{ route('profile.edit') }}">
                                    <i class="bi bi-person me-2"></i>
                                    Profile
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" id="logoutForm">
                                    @csrf
                                    <button class="dropdown-item d-flex align-items-center text-danger" type="submit">
                                        <i class="bi bi-box-arrow-right me-2"></i>
                                        Log out
                                    </button>
                                </form>
                            </li>
                        </ul>
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="content-wrapper">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{ $slot }}
                </main>

                <!-- Footer -->
                <footer class="app-footer">
                    <div class="container-fluid">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                &copy; {{ now()->year }} <strong>Tele-Fleet</strong>. All rights reserved.
                            </div>
                            <div class="col-md-6 text-md-end">
                                <span class="text-muted">v1.0.0</span>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Mobile sidebar toggle
            document.getElementById('sidebarToggle').addEventListener('click', function() {
                document.querySelector('.sidebar').classList.toggle('active');
            });

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                const sidebar = document.querySelector('.sidebar');
                const toggleBtn = document.getElementById('sidebarToggle');
                
                if (window.innerWidth < 992 && 
                    !sidebar.contains(event.target) && 
                    !toggleBtn.contains(event.target) &&
                    sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            });

            // Add smooth transitions for cards
            document.addEventListener('DOMContentLoaded', function() {
                const cards = document.querySelectorAll('.card');
                cards.forEach(card => {
                    card.style.transition = 'all 0.3s ease';
                });
            });
        </script>
    </body>
</html>
