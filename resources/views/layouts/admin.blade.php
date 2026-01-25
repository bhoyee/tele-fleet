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
        <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

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
                --info: #118ab2;
                --dark: #2b2d42;
                --light: #f8f9fa;
                --sidebar-bg: #ffffff;
                --sidebar-width: 260px;
                --card-shadow: 0 4px 12px rgba(5, 108, 163, 0.08);
                --shadow-lg: 0 10px 25px rgba(5, 108, 163, 0.1);
                --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            body {
                font-family: "Manrope", system-ui, -apple-system, sans-serif;
                background: #f5f9ff;
                color: #333;
                overflow-x: hidden;
            }

            .page-progress {
                position: fixed;
                top: 0;
                left: 0;
                height: 3px;
                width: 100%;
                z-index: 9999;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.2s ease;
            }

            .page-progress.active {
                opacity: 1;
            }

            .page-progress::before {
                content: '';
                position: absolute;
                left: -30%;
                top: 0;
                height: 100%;
                width: 30%;
                background: linear-gradient(90deg, rgba(5, 108, 163, 0) 0%, #056ca3 50%, rgba(5, 108, 163, 0) 100%);
                animation: progress-slide 1s ease-in-out infinite;
            }

            @keyframes progress-slide {
                0% { left: -30%; }
                100% { left: 100%; }
            }


            @keyframes spin {
                to { transform: rotate(360deg); }
            }

            /* Sidebar Styling */
            .sidebar {
                width: var(--sidebar-width);
                background: var(--sidebar-bg);
                border-right: 1px solid rgba(5, 108, 163, 0.1);
                position: fixed;
                height: 100vh;
                left: 0;
                top: 0;
                z-index: 1000;
                transition: var(--transition);
                padding: 0;
                box-shadow: 2px 0 12px rgba(5, 108, 163, 0.03);
                background: linear-gradient(to bottom, #ffffff, #f8fbfe);
            }

            .sidebar-brand {
                padding: 1.5rem 1.5rem 1rem;
                border-bottom: 1px solid rgba(5, 108, 163, 0.1);
                background: #056CA3;
            }

            .sidebar-brand h2 {
                font-weight: 700;
                color: white;
                margin: 0;
                font-size: 1.5rem;
                letter-spacing: -0.5px;
            }

            .sidebar-brand h2 i {
                color: white;
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
                color: #056CA3;
                background: rgba(5, 108, 163, 0.08);
                transform: translateX(4px);
            }

            .nav-link.active {
                color: #056CA3;
                background: rgba(5, 108, 163, 0.12);
                font-weight: 600;
            }

            .nav-link.active::before {
                content: '';
                position: absolute;
                left: 0;
                top: 0;
                height: 100%;
                width: 4px;
                background: #056CA3;
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
                border-bottom: 1px solid rgba(5, 108, 163, 0.1);
                padding: 1rem 2rem;
                position: sticky;
                top: 0;
                z-index: 100;
                backdrop-filter: blur(10px);
                background: rgba(255, 255, 255, 0.98);
                box-shadow: 0 2px 10px rgba(5, 108, 163, 0.05);
            }

            .user-dropdown {
                border: none;
                background: transparent;
                color: var(--dark);
                padding: 0.5rem 1rem;
                border-radius: 10px;
                transition: var(--transition);
                border: 1px solid rgba(5, 108, 163, 0.1);
            }

            .user-dropdown:hover {
                background: rgba(5, 108, 163, 0.05);
                border-color: #056CA3;
            }

            .user-avatar {
                width: 36px;
                height: 36px;
                background: linear-gradient(135deg, #056CA3 0%, #065E8C 100%);
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
                border: 1px solid rgba(5, 108, 163, 0.1);
            }

            .card:hover {
                transform: translateY(-4px);
                box-shadow: var(--shadow-lg);
            }

            .card-header {
                background: transparent;
                border-bottom: 1px solid rgba(5, 108, 163, 0.1);
                padding: 1.25rem 1.5rem;
                font-weight: 600;
                background: rgba(5, 108, 163, 0.02);
            }

            .card-body {
                padding: 1.5rem;
            }

            /* Stats Cards */
            .stat-card {
                border-left: 4px solid #056CA3;
                padding: 1.25rem;
                background: linear-gradient(to right, rgba(5, 108, 163, 0.02), transparent);
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
                background: #056CA3;
                border: none;
                border-radius: 10px;
                padding: 0.625rem 1.5rem;
                font-weight: 600;
                transition: var(--transition);
                background: linear-gradient(135deg, #056CA3 0%, #065E8C 100%);
            }

            .btn-primary:hover {
                background: linear-gradient(135deg, #065E8C 0%, #056CA3 100%);
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(5, 108, 163, 0.2);
            }

            .btn-loading {
                position: relative;
                pointer-events: none;
                opacity: 0.85;
            }

            .btn-loading .btn-label {
                visibility: hidden;
            }

            .btn-loading .btn-spinner {
                position: absolute;
                left: 50%;
                top: 50%;
                transform: translate(-50%, -50%);
            }

            /* Alert */
            .alert {
                border: none;
                border-radius: 12px;
                padding: 1rem 1.5rem;
                margin-bottom: 1.5rem;
                box-shadow: var(--card-shadow);
                border-left: 4px solid;
            }

            .alert-success {
                background: rgba(6, 214, 160, 0.1);
                border-left-color: #06d6a0;
                color: #059669;
            }

            /* Footer */
            .app-footer {
                background: white;
                border-top: 1px solid rgba(5, 108, 163, 0.1);
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
                width: 8px;
            }

            ::-webkit-scrollbar-track {
                background: #f1f5ff;
            }

            ::-webkit-scrollbar-thumb {
                background: rgba(5, 108, 163, 0.3);
                border-radius: 4px;
            }

            ::-webkit-scrollbar-thumb:hover {
                background: rgba(5, 108, 163, 0.5);
            }

            /* Active State Enhancement */
            .active {
                position: relative;
            }

            /* Content Padding */
            .content-wrapper {
                padding: 2rem;
                max-width: 1600px;
                margin: 0 auto;
                width: 100%;
            }

            /* Page Header */
            .page-header {
                margin-bottom: 2rem;
                background: white;
                padding: 1.5rem;
                border-radius: 16px;
                box-shadow: var(--card-shadow);
                border: 1px solid rgba(5, 108, 163, 0.1);
            }

            .page-header h1 {
                font-weight: 700;
                color: #056CA3;
                margin-bottom: 0.5rem;
                font-size: 1.75rem;
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
                border: 1px solid rgba(5, 108, 163, 0.1);
            }

            .table thead th {
                border-bottom: 2px solid rgba(5, 108, 163, 0.1);
                background: #f8fafc;
                padding: 1rem 1.5rem;
                font-weight: 600;
                color: var(--dark);
                background: rgba(5, 108, 163, 0.02);
            }

            .table tbody td {
                padding: 1rem 1.5rem;
                border-bottom: 1px solid rgba(5, 108, 163, 0.05);
            }

            .table tbody tr:hover {
                background: rgba(5, 108, 163, 0.02);
            }

            /* Notification Badge */
            .notification-badge {
                position: absolute;
                top: -5px;
                right: -5px;
                width: 18px;
                height: 18px;
                background: linear-gradient(135deg, #ef476f 0%, #dc2626 100%);
                color: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.7rem;
                font-weight: 600;
            }

            /* Quick Stats */
            .quick-stats {
                background: linear-gradient(135deg, #056CA3 0%, #065E8C 100%);
                color: white;
                border-radius: 16px;
                padding: 1.5rem;
                margin-bottom: 2rem;
                box-shadow: var(--shadow-lg);
            }

            .quick-stats .stat-item {
                text-align: center;
                padding: 1rem;
            }

            .quick-stats .stat-value {
                font-size: 2rem;
                font-weight: 700;
                margin-bottom: 0.5rem;
            }

            .quick-stats .stat-label {
                font-size: 0.875rem;
                opacity: 0.9;
            }

            /* Status Badges */
            .status-badge {
                padding: 0.25rem 0.75rem;
                border-radius: 20px;
                font-size: 0.75rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .status-badge.active {
                background: rgba(6, 214, 160, 0.1);
                color: #059669;
                border: 1px solid rgba(6, 214, 160, 0.2);
            }

            .status-badge.inactive {
                background: rgba(239, 71, 111, 0.1);
                color: #dc2626;
                border: 1px solid rgba(239, 71, 111, 0.2);
            }

            .status-badge.pending {
                background: rgba(255, 209, 102, 0.1);
                color: #d97706;
                border: 1px solid rgba(255, 209, 102, 0.2);
            }

            /* Action Buttons */
            .action-btn {
                width: 36px;
                height: 36px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: rgba(5, 108, 163, 0.1);
                color: #056CA3;
                border: 1px solid rgba(5, 108, 163, 0.2);
                transition: var(--transition);
            }

            .action-btn:hover {
                background: #056CA3;
                color: white;
                transform: translateY(-2px);
            }

            /* Search Bar */
            .search-bar {
                position: relative;
                max-width: 400px;
            }

            .search-bar input {
                padding-left: 2.5rem;
                border-radius: 10px;
                border: 1px solid rgba(5, 108, 163, 0.2);
                background: rgba(5, 108, 163, 0.02);
            }

            .search-bar input:focus {
                border-color: #056CA3;
                box-shadow: 0 0 0 3px rgba(5, 108, 163, 0.1);
            }

            .search-bar i {
                position: absolute;
                left: 1rem;
                top: 50%;
                transform: translateY(-50%);
                color: #64748b;
            }

            /* Role Badges */
            .role-badge {
                padding: 0.25rem 0.75rem;
                border-radius: 6px;
                font-size: 0.75rem;
                font-weight: 600;
                background: rgba(5, 108, 163, 0.1);
                color: #056CA3;
            }

            /* Mobile Sidebar Overlay */
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
            }

            .sidebar-overlay.active {
                display: block;
            }

            @media (max-width: 992px) {
                .sidebar {
                    z-index: 1000;
                }
                .sidebar-overlay.active {
                    display: block;
                }
            }
        </style>
    </head>
    <body>
        <div class="page-progress" id="pageProgress" aria-hidden="true"></div>
        <div class="app-shell d-flex">
            <!-- Sidebar Overlay for Mobile -->
            <div class="sidebar-overlay" id="sidebarOverlay"></div>

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
                        @if (in_array(auth()->user()?->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true))
                            <li class="nav-item">
                                <a class="nav-link @if (request()->routeIs('logbooks.*')) active @endif" href="{{ route('logbooks.index') }}">
                                    <i class="bi bi-journal-text nav-icon"></i>
                                    <span>Logbooks</span>
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
                    <div class="d-flex align-items-center">
                        <button class="btn btn-outline-secondary d-lg-none me-3" id="sidebarToggle">
                            <i class="bi bi-list"></i>
                        </button>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="#" class="text-decoration-none text-primary">Home</a></li>
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
                        @php
                            $unreadCount = auth()->user()?->unreadNotifications()->count() ?? 0;
                            $latestNotifications = auth()->user()?->notifications()->latest()->take(5)->get() ?? collect();
                        @endphp
                        <div class="dropdown position-relative">
                            <button class="btn btn-light position-relative" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius: 10px; border: 1px solid rgba(5, 108, 163, 0.2);">
                                <i class="bi bi-bell" style="color: #056CA3;"></i>
                                @if ($unreadCount > 0)
                                    <span class="notification-badge">{{ $unreadCount }}</span>
                                @endif
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 p-0" style="min-width: 320px; border: 1px solid rgba(5, 108, 163, 0.1);">
                                <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center bg-light">
                                    <span class="fw-semibold text-primary">Notifications</span>
                                    @if ($unreadCount > 0)
                                        <form method="POST" action="{{ route('notifications.read_all') }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-link btn-sm text-decoration-none text-primary" type="submit">Mark all read</button>
                                        </form>
                                    @endif
                                </div>
                                @forelse ($latestNotifications as $notification)
                                    <div class="px-3 py-2 border-bottom">
                                        <div class="d-flex justify-content-between">
                                            <div class="fw-semibold small">
                                                {{ $notification->data['request_number'] ?? 'Trip Update' }}
                                                @if (! $notification->read_at)
                                                    <span class="badge bg-primary ms-1">New</span>
                                                @endif
                                            </div>
                                            <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                        </div>
                                        <div class="text-muted small">
                                            {{ $notification->data['purpose'] ?? 'Trip status updated' }}
                                        </div>
                                        <div class="d-flex gap-2 mt-2">
                                            @if (! $notification->read_at)
                                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="btn btn-outline-primary btn-sm" type="submit">Mark read</button>
                                                </form>
                                            @endif
                                            @if (! empty($notification->data['trip_request_id']))
                                                <a class="btn btn-light btn-sm" href="{{ route('trips.show', $notification->data['trip_request_id']) }}">View</a>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-3 py-4 text-center text-muted">No notifications yet.</div>
                                @endforelse
                                <div class="px-3 py-2 text-center border-top">
                                    <a class="text-decoration-none fw-semibold text-primary" href="{{ route('notifications.index') }}">View all notifications</a>
                                </div>
                            </div>
                        </div>

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
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3" style="min-width: 200px; border: 1px solid rgba(5, 108, 163, 0.1);">
                                <li>
                                    <a class="dropdown-item d-flex align-items-center" href="{{ route('profile.edit') }}">
                                        <i class="bi bi-person me-2 text-primary"></i>
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
                        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <div>{{ session('success') }}</div>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div>{{ session('error') }}</div>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{ $slot }}
                </main>

                <!-- Footer -->
                <footer class="app-footer">
                    <div class="container-fluid">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                &copy; {{ now()->year }} <strong class="text-primary">Tele-Fleet</strong>. All rights reserved.
                            </div>
                            <div class="col-md-6 text-md-end">
                                <span class="text-muted">v1.0.0</span>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Mobile sidebar toggle
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const sidebar = document.querySelector('.sidebar');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    sidebarOverlay.classList.toggle('active');
                });
            }

            // Close sidebar when clicking overlay
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                });
            }

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                if (window.innerWidth < 992 && 
                    !sidebar.contains(event.target) && 
                    !sidebarToggle.contains(event.target) &&
                    sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                }
            });

            // Add smooth transitions for cards
            document.addEventListener('DOMContentLoaded', function() {
                const cards = document.querySelectorAll('.card');
                cards.forEach(card => {
                    card.style.transition = 'all 0.3s ease';
                });
                
                // Auto-hide success alerts after 5 seconds
                const successAlerts = document.querySelectorAll('.alert-success');
                successAlerts.forEach(alert => {
                    setTimeout(() => {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }, 5000);
                });

                if (window.jQuery && $('.datatable').length) {
                    $('.datatable').DataTable({
                        pageLength: 10,
                        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
                        order: [],
                        searching: true,
                        paging: true,
                        info: true,
                    });
                }

                const applyLoadingState = (button) => {
                    if (!button || button.classList.contains('btn-loading')) {
                        return;
                    }
                    const label = document.createElement('span');
                    label.className = 'btn-label';
                    label.textContent = button.textContent.trim();
                    const spinner = document.createElement('span');
                    spinner.className = 'spinner-border spinner-border-sm btn-spinner';
                    spinner.setAttribute('role', 'status');
                    spinner.setAttribute('aria-hidden', 'true');
                    button.textContent = '';
                    button.appendChild(label);
                    button.appendChild(spinner);
                    button.classList.add('btn-loading');
                    button.setAttribute('disabled', 'disabled');
                };

                document.querySelectorAll('form').forEach((form) => {
                    form.addEventListener('submit', () => {
                        const submitButton = form.querySelector('button[type="submit"]');
                        applyLoadingState(submitButton);
                    });
                });

                document.querySelectorAll('[data-loading]').forEach((button) => {
                    button.addEventListener('click', () => {
                        applyLoadingState(button);
                    });
                });
            });

            // Initialize tooltips
            document.addEventListener('DOMContentLoaded', function() {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            });

            const showPageProgress = () => {
                const progress = document.getElementById('pageProgress');
                if (progress) {
                    progress.classList.add('active');
                    progress.setAttribute('aria-hidden', 'false');
                }
            };

            document.addEventListener('click', (event) => {
                const link = event.target.closest('a');
                if (!link) {
                    return;
                }
                const href = link.getAttribute('href');
                if (!href || href.startsWith('#') || link.getAttribute('target') === '_blank') {
                    return;
                }
                showPageProgress();
            });

            window.addEventListener('beforeunload', () => {
                showPageProgress();
            });

            // Auto-refresh notifications every 30 seconds
            setInterval(() => {
                fetch('{{ route("notifications.count") }}')
                    .then(response => response.json())
                    .then(data => {
                        const badge = document.querySelector('.notification-badge');
                        if (badge) {
                            if (data.count > 0) {
                                badge.textContent = data.count;
                                badge.style.display = 'flex';
                            } else {
                                badge.style.display = 'none';
                            }
                        }
                    });
            }, 30000);
        </script>
        @stack('scripts')
    </body>
</html>
