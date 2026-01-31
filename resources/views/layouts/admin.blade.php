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
                display: flex;
                flex-direction: column;
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
                overflow-y: auto;
                flex: 1;
                padding-bottom: 5rem;
            }

            .sidebar-footer {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                padding: 1rem 1.5rem;
                border-top: 1px solid rgba(5, 108, 163, 0.1);
                background: #f8fbfe;
                font-size: 0.85rem;
                color: #64748b;
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
                display: flex;
                flex-direction: column;
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

            @media (max-width: 768px) {
                .topbar {
                    flex-wrap: wrap;
                    gap: 0.75rem;
                    padding: 0.75rem 1rem;
                }

                .topbar .breadcrumb {
                    font-size: 0.85rem;
                }

                .topbar .d-flex.align-items-center.gap-3 {
                    flex-wrap: wrap;
                    justify-content: flex-end;
                    width: 100%;
                    gap: 0.5rem !important;
                }

                .content-wrapper {
                    padding: 1rem;
                }

                .table {
                    display: block;
                    overflow-x: auto;
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
                max-width: 1400px;
                margin: 0 auto;
                width: 100%;
                flex: 1;
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

            .notification-list {
                max-height: 360px;
                overflow-y: auto;
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

            /* Chat Widget */
            .chat-widget-button {
                position: fixed;
                bottom: 24px;
                right: 24px;
                z-index: 1045;
                border-radius: 999px;
                padding: 0.75rem 1.25rem;
                box-shadow: 0 12px 30px rgba(5, 108, 163, 0.25);
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .chat-widget-badge {
                min-width: 18px;
                height: 18px;
                border-radius: 50%;
                background: #ef476f;
                color: #fff;
                font-size: 0.65rem;
                display: none;
                align-items: center;
                justify-content: center;
                font-weight: 600;
            }

            .chat-offcanvas {
                width: min(520px, 100%);
            }

            .chat-widget-body {
                display: flex;
                flex-direction: column;
                height: calc(100vh - 90px);
                gap: 1rem;
            }

            .chat-widget-section {
                border: 1px solid rgba(5, 108, 163, 0.1);
                border-radius: 14px;
                padding: 0.75rem;
                background: #fff;
                box-shadow: var(--card-shadow);
            }

            .chat-widget-list {
                max-height: 180px;
                overflow-y: auto;
            }

            .chat-widget-item {
                padding: 0.6rem 0.75rem;
                border-radius: 10px;
                cursor: pointer;
                transition: var(--transition);
            }

            .chat-widget-item:hover,
            .chat-widget-item.active {
                background: var(--primary-lighter);
                border: 1px solid rgba(5, 108, 163, 0.15);
            }

            .chat-status-pill {
                font-size: 0.7rem;
                font-weight: 600;
                padding: 0.15rem 0.5rem;
                border-radius: 999px;
                background: rgba(148, 163, 184, 0.2);
                color: #64748b;
            }

            .chat-status-pill.closed {
                background: rgba(239, 68, 68, 0.12);
                color: #dc2626;
            }

            .chat-history-delete {
                border: none;
                background: transparent;
                color: #dc2626;
                display: inline-flex;
                align-items: center;
                gap: 0.3rem;
                font-size: 0.75rem;
                padding: 0;
            }

            .chat-widget-thread {
                flex: 1;
                display: flex;
                flex-direction: column;
                border-radius: 14px;
                border: 1px solid rgba(5, 108, 163, 0.1);
                overflow: hidden;
                background: #fff;
            }

            .chat-widget-thread-header {
                padding: 0.75rem 1rem;
                border-bottom: 1px solid rgba(5, 108, 163, 0.1);
                background: rgba(5, 108, 163, 0.03);
            }

            .chat-widget-messages {
                flex: 1;
                overflow-y: auto;
                padding: 1rem;
                background: #f6f9fd;
            }

            .chat-widget-message {
                max-width: 75%;
                padding: 0.6rem 0.8rem;
                border-radius: 14px;
                margin-bottom: 0.75rem;
                box-shadow: 0 4px 12px rgba(5, 108, 163, 0.08);
            }

            .chat-widget-message.self {
                background: linear-gradient(135deg, #056CA3 0%, #065E8C 100%);
                color: #fff;
                margin-left: auto;
            }

            .chat-widget-message.other {
                background: #fff;
                border: 1px solid rgba(5, 108, 163, 0.1);
            }

            .chat-widget-meta {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                margin-top: 0.35rem;
                font-size: 0.75rem;
                opacity: 0.75;
            }

            .chat-widget-status {
                font-size: 0.7rem;
                font-weight: 600;
                padding: 0.15rem 0.4rem;
                border-radius: 999px;
                background: rgba(5, 108, 163, 0.08);
                color: #056CA3;
            }

            .chat-widget-status.sending {
                background: rgba(59, 130, 246, 0.7);
                color: #fff;
            }

            .chat-widget-status.failed {
                background: rgba(239, 68, 68, 0.12);
                color: #dc2626;
            }

            .chat-widget-retry {
                background: transparent;
                border: none;
                color: #dc2626;
                font-weight: 600;
                padding: 0;
            }

            .chat-widget-input {
                display: flex;
                gap: 0.5rem;
                padding: 0.75rem;
                border-top: 1px solid rgba(5, 108, 163, 0.1);
                background: #fff;
            }

            .chat-widget-placeholder {
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100%;
                color: #64748b;
                text-align: center;
                padding: 1rem;
            }

            @media (max-width: 575px) {
                .chat-widget-button {
                    bottom: 16px;
                    right: 16px;
                    padding: 0.6rem 1rem;
                }

                .chat-offcanvas {
                    width: 100%;
                }

                .chat-widget-list {
                    max-height: 160px;
                }
            }
        </style>
        @stack('styles')
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
                                <a class="nav-link @if (request()->routeIs('trips.*') && ! request()->routeIs('trips.my')) active @endif" href="{{ route('trips.index') }}">
                                    <i class="bi bi-map nav-icon"></i>
                                    <span>Trips</span>
                                </a>
                            </li>
                        @endif

                        @if (in_array(auth()->user()?->role, [\App\Models\User::ROLE_BRANCH_ADMIN, \App\Models\User::ROLE_BRANCH_HEAD], true))
                            <li class="nav-item">
                                <a class="nav-link @if (request()->routeIs('trips.my')) active @endif" href="{{ route('trips.my') }}">
                                    <i class="bi bi-clipboard-check nav-icon"></i>
                                    <span>My Requests</span>
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

                        @if (in_array(auth()->user()?->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER, \App\Models\User::ROLE_BRANCH_ADMIN, \App\Models\User::ROLE_BRANCH_HEAD], true))
                            <li class="nav-item">
                                <a class="nav-link @if (request()->routeIs('incidents.*')) active @endif" href="{{ route('incidents.index') }}">
                                    <i class="bi bi-exclamation-triangle nav-icon"></i>
                                    <span>Incidents</span>
                                </a>
                            </li>
                        @endif

                        @if (in_array(auth()->user()?->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true))
                            <li class="nav-item">
                                <a class="nav-link @if (request()->routeIs('maintenances.*')) active @endif" href="{{ route('maintenances.index') }}">
                                    <i class="bi bi-tools nav-icon"></i>
                                    <span>Maintenance</span>
                                </a>
                            </li>
                        @endif

                        @if (in_array(auth()->user()?->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER, \App\Models\User::ROLE_BRANCH_ADMIN, \App\Models\User::ROLE_BRANCH_HEAD], true))
                            <li class="nav-item">
                                <a class="nav-link @if (request()->routeIs('reports.my-requests*')) active @endif" href="{{ route('reports.my-requests') }}">
                                    <i class="bi bi-bar-chart nav-icon"></i>
                                    <span>My Reports</span>
                                </a>
                            </li>
                        @endif

                        @if (in_array(auth()->user()?->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true))
                            <li class="nav-item">
                                <a class="nav-link @if (request()->routeIs('reports.fleet')) active @endif" href="{{ route('reports.fleet') }}">
                                    <i class="bi bi-graph-up nav-icon"></i>
                                    <span>Fleet Reports</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link @if (request()->routeIs('reports.custom*')) active @endif" href="{{ route('reports.custom') }}">
                                    <i class="bi bi-sliders nav-icon"></i>
                                    <span>Custom Reports</span>
                                </a>
                            </li>
                        @endif

                        @if (auth()->user()?->role === \App\Models\User::ROLE_BRANCH_HEAD)
                            <li class="nav-item">
                                <a class="nav-link @if (request()->routeIs('reports.branch*')) active @endif" href="{{ route('reports.branch') }}">
                                    <i class="bi bi-clipboard-data nav-icon"></i>
                                    <span>Branch Report</span>
                                </a>
                            </li>
                        @endif

                        @if (in_array(auth()->user()?->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER, \App\Models\User::ROLE_BRANCH_ADMIN, \App\Models\User::ROLE_BRANCH_HEAD], true))
                            <li class="nav-item">
                                <button class="nav-link w-100 text-start" type="button" data-bs-toggle="offcanvas" data-bs-target="#chatWidget" aria-controls="chatWidget">
                                    <i class="bi bi-chat-dots nav-icon"></i>
                                    <span>Chat</span>
                                </button>
                            </li>
                        @endif

                        @if (auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                            <li class="nav-item">
                                <a class="nav-link @if (request()->routeIs('admin.chats.*')) active @endif" href="{{ route('admin.chats.index') }}">
                                    <i class="bi bi-chat-square-dots nav-icon"></i>
                                    <span>Chat Management</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link @if (request()->routeIs('admin.maintenance-settings.*')) active @endif" href="{{ route('admin.maintenance-settings.edit') }}">
                                    <i class="bi bi-sliders nav-icon"></i>
                                    <span>Maintenance Settings</span>
                                </a>
                            </li>
                            <li class="nav-item mt-3"></li>
                            <li class="nav-item">
                                <a class="nav-link @if (request()->routeIs('admin.user-manual')) active @endif" href="{{ route('admin.user-manual') }}">
                                    <i class="bi bi-journal-richtext nav-icon"></i>
                                    <span>User Manual</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link @if (request()->routeIs('admin.health')) active @endif" href="{{ route('admin.health') }}">
                                    <i class="bi bi-activity nav-icon"></i>
                                    <span>System Health</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link @if (request()->routeIs('system.backups*')) active @endif" href="{{ route('system.backups') }}">
                                    <i class="bi bi-database-check nav-icon"></i>
                                    <span>Database Backups</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link @if (request()->routeIs('system.logs*')) active @endif" href="{{ route('system.logs') }}">
                                    <i class="bi bi-file-text nav-icon"></i>
                                    <span>System Logs</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </nav>
                <div class="sidebar-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Tele-Fleet</span>
                        <span class="fw-semibold text-primary">v1.0.0</span>
                    </div>
                </div>
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
                        <div class="d-none d-md-flex flex-column text-end pe-3 border-end" style="border-color: rgba(5, 108, 163, 0.15);">
                            <span class="text-muted small">Branch</span>
                            <span class="fw-semibold text-primary">{{ auth()->user()?->branch?->name ?? 'Head Office' }}</span>
                        </div>
                        @php
                            $excludedNotificationTypes = [\App\Notifications\ChatMessageNotification::class];
                            $unreadCount = auth()->user()?->unreadNotifications()
                                ->whereNotIn('type', $excludedNotificationTypes)
                                ->count() ?? 0;
                            $latestNotifications = auth()->user()?->unreadNotifications()
                                ->whereNotIn('type', $excludedNotificationTypes)
                                ->latest()
                                ->get() ?? collect();
                        @endphp
                        <div class="dropdown position-relative">
                            <button class="btn btn-light position-relative" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius: 10px; border: 1px solid rgba(5, 108, 163, 0.2);">
                                <i class="bi bi-bell" style="color: #056CA3;"></i>
                                <span class="notification-badge" style="{{ $unreadCount > 0 ? '' : 'display:none;' }}">{{ $unreadCount }}</span>
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
                                <div class="notification-list">
                                    @forelse ($latestNotifications as $notification)
                                        <div class="px-3 py-2 border-bottom">
                                        @php
                                            $notificationData = is_array($notification->data ?? null) ? $notification->data : [];
                                            $notificationType = class_basename($notification->type ?? '');
                                            $chatTypes = ['ChatRequestNotification', 'ChatClosedNotification', 'ChatMessageNotification'];
                                            $isChat = in_array($notificationType, $chatTypes, true);
                                            $isSystemHealth = $notificationType === 'SystemHealthAlert';
                                            $tripLabel = $notificationData['request_number']
                                                ?? (! empty($notificationData['trip_request_id'])
                                                    ? ('Trip #'.$notificationData['trip_request_id'])
                                                    : null);
                                            $tripTitle = $tripLabel ? "{$tripLabel} Update" : 'Trip Update';
                                            $title = match ($notificationType) {
                                                'ChatRequestNotification' => 'Chat Request',
                                                'ChatClosedNotification' => 'Chat Closed',
                                                'ChatMessageNotification' => 'Chat Message',
                                                'SystemHealthAlert' => $notificationData['title'] ?? 'System Health',
                                                default => $tripTitle,
                                            };

                                            $message = match ($notificationType) {
                                                'ChatRequestNotification' => 'New chat request received.',
                                                'ChatClosedNotification' => 'Chat has been closed.',
                                                'ChatMessageNotification' => 'New chat message received.',
                                                'SystemHealthAlert' => $notificationData['message'] ?? 'System health alert.',
                                                'TripRequestCreated' => 'New trip request submitted.',
                                                'TripRequestApproved' => 'Trip request approved.',
                                                'TripRequestAssigned' => 'Trip assigned to driver/vehicle.',
                                                'TripRequestRejected' => 'Trip request rejected.',
                                                'TripRequestCancelled' => 'Trip request cancelled.',
                                                'TripAssignmentPending' => 'Trip awaiting assignment.',
                                                'TripAssignmentConflict' => 'Trip assignment needs attention.',
                                                'TripCompletionReminderNotification' => 'Trip completion reminder sent.',
                                                'OverdueTripNotification' => 'Trip marked overdue.',
                                                default => ! empty($notificationData['status'])
                                                    ? ('Status: ' . ucfirst($notificationData['status']))
                                                    : ($notificationData['purpose'] ?? 'Trip update received.'),
                                            };

                                            if (! $isChat && $tripLabel) {
                                                $message = "{$tripLabel} • {$message}";
                                            }

                                            $viewUrl = ! empty($notificationData['trip_request_id'])
                                                ? route('trips.show', $notificationData['trip_request_id'])
                                                : ($isSystemHealth
                                                    ? route('admin.health')
                                                    : ($isChat && ! empty($notificationData['conversation_id'])
                                                        ? null
                                                        : null));
                                        @endphp
                                            <div class="d-flex justify-content-between">
                                                <div class="fw-semibold small">
                                                    {{ $title }}
                                                    <span class="badge bg-primary ms-1">New</span>
                                                </div>
                                                <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                            </div>
                                            <div class="text-muted small">
                                                {{ $message }}
                                            </div>
                                            <div class="d-flex gap-2 mt-2">
                                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="btn btn-outline-primary btn-sm" type="submit">Mark read</button>
                                                </form>
                                                @if ($viewUrl)
                                                    <a class="btn btn-light btn-sm" href="{{ $viewUrl }}">View</a>
                                                @elseif ($isChat && ! empty($notification->data['conversation_id']))
                                                    <button class="btn btn-light btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#chatWidget">Open chat</button>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="px-3 py-4 text-center text-muted">No unread notifications.</div>
                                    @endforelse
                                </div>
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
                                <small class="text-muted">{{ ucwords(str_replace('_', ' ', auth()->user()?->role ?? '')) }}</small>
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

        <button class="btn btn-primary chat-widget-button" type="button" data-bs-toggle="offcanvas" data-bs-target="#chatWidget" aria-controls="chatWidget">
            <i class="bi bi-chat-dots"></i>
            <span class="d-none d-sm-inline">Chat</span>
            <span class="chat-widget-badge" id="chatWidgetBadge"></span>
        </button>

        <div class="offcanvas offcanvas-end chat-offcanvas" tabindex="-1" id="chatWidget" aria-labelledby="chatWidgetLabel">
            <div class="offcanvas-header border-bottom">
                <div>
                    <h5 class="mb-0" id="chatWidgetLabel">Chat Support</h5>
                    <small class="text-muted">Realtime assistance</small>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
            </div>
            <div class="offcanvas-body">
                <div class="chat-widget-body">
                    @if (in_array(auth()->user()?->role, [\App\Models\User::ROLE_BRANCH_ADMIN, \App\Models\User::ROLE_BRANCH_HEAD], true))
                        <div class="chat-widget-section" id="chatSupportSection">
                            <div class="fw-semibold mb-2">Start support chat</div>
                            <div id="chatSupportBot">
                                <div class="small text-muted mb-2">What do you need help with?</div>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-primary" type="button" data-issue="{{ \App\Models\ChatConversation::ISSUE_ADMIN }}">
                                        Administrative (Trips/Reports)
                                    </button>
                                    <button class="btn btn-outline-primary" type="button" data-issue="{{ \App\Models\ChatConversation::ISSUE_TECH }}">
                                        Technical Support
                                    </button>
                                </div>
                                <button class="btn btn-primary w-100 mt-3" type="button" id="chatSupportStart" disabled>Start chat</button>
                                <input type="hidden" id="chatSupportIssue" value="">
                                <div class="small text-muted mt-2">We connect you to the right team after review.</div>
                            </div>
                            <div id="chatSupportFeedback" class="mt-2"></div>
                        </div>
                    @endif

                    <div class="chat-widget-section" id="chatWidgetConversationsSection">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold">Conversations</span>
                            <div class="d-flex align-items-center gap-2">
                                <div class="btn-group btn-group-sm" role="group" aria-label="Chat view">
                                    <button class="btn btn-outline-secondary active" type="button" id="chatWidgetActiveTab">Active</button>
                                    <button class="btn btn-outline-secondary" type="button" id="chatWidgetHistoryTab">History</button>
                                </div>
                            </div>
                        </div>
                        <div id="chatWidgetPending" class="mb-3"></div>
                        <div class="chat-widget-list" id="chatWidgetList">
                            <div class="text-muted small">No conversations loaded yet.</div>
                        </div>
                    </div>

                    <div class="chat-widget-thread" id="chatWidgetThread">
                        <div class="chat-widget-thread-header d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="fw-semibold" id="chatWidgetThreadTitle">Select a conversation</div>
                                <div class="text-muted small" id="chatWidgetThreadStatus">Waiting to load</div>
                            </div>
                            @if (in_array(auth()->user()?->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true))
                                <button class="btn btn-outline-danger btn-sm" type="button" id="chatWidgetClose" disabled>Close</button>
                            @endif
                        </div>
                        <div class="chat-widget-messages" id="chatWidgetMessages">
                            <div class="chat-widget-placeholder">Choose a conversation to start messaging.</div>
                        </div>
                        <div class="chat-widget-input" id="chatWidgetInput">
                            <input class="form-control" type="text" id="chatWidgetMessageInput" placeholder="Type your message..." disabled>
                            <button class="btn btn-primary" type="button" id="chatWidgetSend" disabled>Send</button>
                        </div>
                        <div class="p-3 border-top bg-light d-none" id="chatWidgetAcceptActions">
                            <div class="small text-muted mb-2">This chat is pending. Accept to start messaging.</div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-success w-100" type="button" id="chatWidgetAccept">Accept</button>
                                <button class="btn btn-outline-danger w-100" type="button" id="chatWidgetDecline">Decline</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
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

            const hidePageProgress = () => {
                const progress = document.getElementById('pageProgress');
                if (progress) {
                    progress.classList.remove('active');
                    progress.setAttribute('aria-hidden', 'true');
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
                if (link.hasAttribute('data-download')) {
                    showPageProgress();
                    const cleanup = () => hidePageProgress();
                    setTimeout(cleanup, 1200);
                    window.addEventListener('focus', cleanup, { once: true });
                    document.addEventListener('visibilitychange', () => {
                        if (!document.hidden) {
                            cleanup();
                        }
                    }, { once: true });
                    return;
                }

                showPageProgress();
            });

            window.addEventListener('beforeunload', () => {
                showPageProgress();
            });

            window.addEventListener('pageshow', () => {
                hidePageProgress();
            });

            const refreshNotificationCount = () => {
                fetch('{{ route("notifications.count") }}', { cache: 'no-store' })
                    .then(response => response.json())
                    .then(data => {
                        let badge = document.querySelector('.notification-badge');
                        if (!badge) {
                            return;
                        }
                        if (data.count > 0) {
                            badge.textContent = data.count;
                            badge.style.display = 'flex';
                        } else {
                            badge.style.display = 'none';
                        }
                    })
                    .catch(() => {});
            };

            // Auto-refresh notifications every 15 seconds
            refreshNotificationCount();
            setInterval(refreshNotificationCount, 15000);

            const chatWidget = document.getElementById('chatWidget');
            if (chatWidget) {
                const chatWidgetList = document.getElementById('chatWidgetList');
                const chatWidgetPending = document.getElementById('chatWidgetPending');
                const chatWidgetMessages = document.getElementById('chatWidgetMessages');
                const chatWidgetThreadTitle = document.getElementById('chatWidgetThreadTitle');
                const chatWidgetThreadStatus = document.getElementById('chatWidgetThreadStatus');
                const chatWidgetInput = document.getElementById('chatWidgetMessageInput');
                const chatWidgetSend = document.getElementById('chatWidgetSend');
                const chatWidgetAcceptActions = document.getElementById('chatWidgetAcceptActions');
                const chatWidgetAccept = document.getElementById('chatWidgetAccept');
                const chatWidgetDecline = document.getElementById('chatWidgetDecline');
                const chatWidgetClose = document.getElementById('chatWidgetClose');
                const chatWidgetBadge = document.getElementById('chatWidgetBadge');
                const chatWidgetConversationsSection = document.getElementById('chatWidgetConversationsSection');
                const chatSupportBot = document.getElementById('chatSupportBot');
                const chatSupportSection = document.getElementById('chatSupportSection');
                const chatSupportStart = document.getElementById('chatSupportStart');
                const chatSupportIssue = document.getElementById('chatSupportIssue');
                const chatSupportFeedback = document.getElementById('chatSupportFeedback');
                const chatWidgetActiveTab = document.getElementById('chatWidgetActiveTab');
                const chatWidgetHistoryTab = document.getElementById('chatWidgetHistoryTab');

                const messageUrlTemplate = "{{ route('chat.messages.store', ['conversation' => '__ID__']) }}";
                const acceptUrlTemplate = "{{ route('chat.accept', ['conversation' => '__ID__']) }}";
                const declineUrlTemplate = "{{ route('chat.decline', ['conversation' => '__ID__']) }}";
                const closeUrlTemplate = "{{ route('chat.close', ['conversation' => '__ID__']) }}";
                const historyDeleteUrlTemplate = "{{ route('chat.history.delete', ['conversation' => '__ID__']) }}";
                const widgetConversationsUrl = "{{ route('chat.widget.conversations') }}";
                const widgetConversationUrlTemplate = "{{ route('chat.widget.conversation', ['conversation' => '__ID__']) }}";
                const supportUrl = "{{ route('chat.support') }}";
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                let activeConversationId = null;
                let chatPoller = null;
                let currentView = 'active';
                let activeMessages = [];
                const currentUserId = {{ auth()->id() }};
                const currentUserName = "{{ auth()->user()?->name }}";
                const isRequesterRole = {{ in_array(auth()->user()?->role, [\App\Models\User::ROLE_BRANCH_ADMIN, \App\Models\User::ROLE_BRANCH_HEAD], true) ? 'true' : 'false' }};

                const initEcho = () => {
                    if (window.ChatEcho && typeof window.ChatEcho.private === 'function') {
                        return window.ChatEcho;
                    }
                    if (window.Echo && typeof window.Echo.private === 'function') {
                        window.ChatEcho = window.Echo;
                        return window.ChatEcho;
                    }
                    const EchoConstructor = window.Echo;
                    if (typeof EchoConstructor !== 'function') {
                        return null;
                    }
                    window.Pusher = Pusher;
                    window.ChatEcho = new EchoConstructor({
                        broadcaster: 'pusher',
                        cluster: 'mt1',
                        key: "{{ config('broadcasting.connections.reverb.key') }}",
                        wsHost: "{{ config('broadcasting.connections.reverb.options.host') }}",
                        wsPort: {{ config('broadcasting.connections.reverb.options.port') }},
                        wssPort: {{ config('broadcasting.connections.reverb.options.port') }},
                        forceTLS: "{{ config('broadcasting.connections.reverb.options.scheme') }}" === 'https',
                        enabledTransports: ['ws', 'wss'],
                        disableStats: true,
                        authEndpoint: '/broadcasting/auth',
                        auth: {
                            headers: {
                                'X-CSRF-TOKEN': csrfToken ?? '',
                            }
                        },
                    });
                    return window.ChatEcho;
                };

                const listenUserChannel = () => {
                    const echo = initEcho();
                    if (!echo || typeof echo.private !== 'function') {
                        return;
                    }
                    echo.private("chat.user.{{ auth()->id() }}")
                        .listen(".chat.request", () => loadChatWidgetData())
                        .listen(".chat.accepted", (event) => {
                            loadChatWidgetData();
                            if (event?.conversation_id && isRequesterRole) {
                                loadConversation(event.conversation_id, { subscribe: true, reset: true });
                            }
                        })
                        .listen(".chat.message", (event) => {
                            if (!event?.conversation_id || !event?.message) {
                                return;
                            }
                            if (activeConversationId && Number(event.conversation_id) === Number(activeConversationId)) {
                                const messageData = {
                                    user_id: event.message.user_id,
                                    user_name: event.message.user_id === currentUserId ? currentUserName : (chatWidgetThreadTitle?.textContent ?? 'User'),
                                    message: event.message.message,
                                    created_at: event.message.created_at,
                                };
                                activeMessages.push(messageData);
                                renderMessages(activeMessages, currentUserId);
                            }
                            loadChatWidgetData();
                        });
                };

                const setInputEnabled = (enabled) => {
                    if (chatWidgetInput && chatWidgetSend) {
                        chatWidgetInput.disabled = !enabled;
                        chatWidgetSend.disabled = !enabled;
                    }
                };

                const toggleAcceptActions = (show) => {
                    if (!chatWidgetAcceptActions) {
                        return;
                    }
                    chatWidgetAcceptActions.classList.toggle('d-none', !show);
                };

                const setCloseEnabled = (enabled) => {
                    if (!chatWidgetClose) {
                        return;
                    }
                    chatWidgetClose.disabled = !enabled;
                };

                const escapeHtml = (value) => {
                    return String(value ?? '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                };

                const renderMessages = (messages, userId) => {
                    if (!chatWidgetMessages) {
                        return;
                    }
                    chatWidgetMessages.innerHTML = '';
                    if (!messages.length) {
                        chatWidgetMessages.innerHTML = '<div class="chat-widget-placeholder">No messages yet.</div>';
                        return;
                    }
                    messages.forEach((message) => {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'chat-widget-message ' + (message.user_id === userId ? 'self' : 'other');
                        const safeName = escapeHtml(message.user_name ?? 'User');
                        const safeMessage = escapeHtml(message.message ?? '');
                        const safeTime = escapeHtml(message.created_at ?? '');
                        const derivedStatus = message.user_id === userId && !message.status ? 'sent' : message.status;
                        const status = derivedStatus ? escapeHtml(derivedStatus) : '';
                        const isFailed = status === 'failed';
                        wrapper.innerHTML = `
                            <div class="small fw-semibold mb-1">${safeName}</div>
                            <div>${safeMessage}</div>
                            <div class="chat-widget-meta">
                                <span>${safeTime}</span>
                                ${status ? `<span class="chat-widget-status ${status}">${status}</span>` : ''}
                                ${isFailed ? `<button class="chat-widget-retry" type="button" data-retry="${message.client_id ?? ''}">Retry</button>` : ''}
                            </div>
                        `;
                        chatWidgetMessages.appendChild(wrapper);
                    });
                    chatWidgetMessages.scrollTop = chatWidgetMessages.scrollHeight;
                };

                const renderConversationList = (data) => {
                    if (!chatWidgetList || !chatWidgetPending) {
                        return;
                    }

                    const pending = data.pending || [];
                    const activeConversations = data.conversations || [];
                    const historyConversations = data.history || [];
                    const conversations = currentView === 'history' ? historyConversations : activeConversations;
                    const totalConversations = activeConversations.length + historyConversations.length + pending.length;

                    if (chatWidgetBadge) {
                        const totalUnread = Number(data.unread_total ?? 0);
                        const totalBadge = totalUnread + pending.length;
                        if (totalBadge > 0) {
                            chatWidgetBadge.textContent = totalBadge;
                            chatWidgetBadge.style.display = 'flex';
                        } else {
                            chatWidgetBadge.style.display = 'none';
                        }
                    }

                    chatWidgetPending.innerHTML = '';
                    if (chatWidgetConversationsSection) {
                        const hideConversations = isRequesterRole
                            && Boolean(activeConversationId)
                            && totalConversations === 1
                            && pending.length === 0;
                        chatWidgetConversationsSection.classList.toggle('d-none', hideConversations);
                    }

                    if (chatSupportSection) {
                        const hideSupport = isRequesterRole && activeConversations.length > 0;
                        chatSupportSection.classList.toggle('d-none', hideSupport);
                    }
                    if (currentView === 'active' && pending.length > 0) {
                        const pendingBlock = document.createElement('div');
                        pendingBlock.className = 'mb-2';
                        pendingBlock.innerHTML = '<div class="fw-semibold mb-2 text-warning">Pending requests</div>';
                        pending.forEach((item) => {
                            const row = document.createElement('div');
                            row.className = 'chat-widget-item border mb-2';
                            row.dataset.conversationId = item.id;
                            const safePendingName = escapeHtml(item.other_user ?? 'Support');
                            const safePendingIssue = escapeHtml(item.issue_type ?? 'Support request');
                            row.innerHTML = `
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold">${safePendingName}</div>
                                        <div class="small text-muted">${safePendingIssue}</div>
                                    </div>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                </div>
                            `;
                            pendingBlock.appendChild(row);
                        });
                        chatWidgetPending.appendChild(pendingBlock);
                    }

                    chatWidgetList.innerHTML = '';
                    if (conversations.length === 0) {
                        const label = currentView === 'history' ? 'No closed chats in the last 30 days.' : 'No active conversations yet.';
                        chatWidgetList.innerHTML = `<div class="text-muted small">${label}</div>`;
                        return;
                    }
                    conversations.forEach((item) => {
                        const row = document.createElement('div');
                        row.className = 'chat-widget-item border';
                        if (activeConversationId === item.id) {
                            row.classList.add('active');
                        }
                        row.dataset.conversationId = item.id;
                        const safeName = escapeHtml(item.other_user ?? 'Support');
                        const safeMessage = escapeHtml(item.last_message ?? 'No messages yet');
                        const safeStatus = escapeHtml(item.status ?? '');
                        const unreadCount = Number(item.unread_count ?? 0);
                        const statusLabel = currentView === 'history' ? 'closed' : safeStatus;
                        const statusClass = statusLabel === 'closed' ? 'chat-status-pill closed' : 'chat-status-pill';
                        row.innerHTML = `
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="fw-semibold">${safeName}</div>
                                    <div class="small text-muted text-truncate">${safeMessage}</div>
                                </div>
                                <div class="d-flex flex-column align-items-end gap-1">
                                    <span class="${statusClass}">${statusLabel}</span>
                                    ${currentView === 'active' && unreadCount > 0 ? `<span class="badge bg-primary">${unreadCount}</span>` : ''}
                                    ${currentView === 'history' ? `<button class="chat-history-delete" type="button" data-history-delete="${item.id}"><i class="bi bi-trash"></i>Remove</button>` : ''}
                                </div>
                            </div>
                        `;
                        chatWidgetList.appendChild(row);
                    });
                };

                const loadConversation = async (conversationId, options = { subscribe: true, reset: true }) => {
                    const previousConversationId = activeConversationId;
                    activeConversationId = conversationId;
                    if (options.reset) {
                        setInputEnabled(false);
                        toggleAcceptActions(false);
                        if (chatWidgetThreadTitle) {
                            chatWidgetThreadTitle.textContent = 'Loading...';
                        }
                        if (chatWidgetThreadStatus) {
                            chatWidgetThreadStatus.textContent = '';
                        }
                    }
                    try {
                        const response = await fetch(widgetConversationUrlTemplate.replace('__ID__', conversationId), {
                            headers: { 'Accept': 'application/json' },
                        });
                        if (!response.ok) {
                            const errorText = await response.text();
                            throw new Error(`Failed to load conversation (${response.status}). ${errorText}`);
                        }
                        const data = await response.json();
                        if (chatWidgetThreadTitle) {
                            chatWidgetThreadTitle.textContent = data.conversation.other_user ?? 'Support';
                        }
                        if (chatWidgetThreadStatus) {
                            chatWidgetThreadStatus.textContent = `Status: ${data.conversation.status}`;
                        }
                        activeMessages = data.messages ?? [];
                        renderMessages(activeMessages, currentUserId);
                        toggleAcceptActions(Boolean(data.conversation.can_accept));
                        setInputEnabled(Boolean(data.conversation.can_reply));
                        setCloseEnabled(data.conversation.status !== 'closed');

                        if (options.subscribe) {
                            const echo = initEcho();
                            if (echo && previousConversationId) {
                                echo.leave(`chat.conversation.${previousConversationId}`);
                            }
                            if (echo) {
                                echo.private(`chat.conversation.${conversationId}`)
                                    .listen(".chat.message", (event) => {
                                        if (!event.message) {
                                            return;
                                        }
                                        const messageData = {
                                            user_id: event.message.user_id,
                                            user_name: event.message.user_id === currentUserId ? currentUserName : (data.conversation.other_user ?? 'User'),
                                            message: event.message.message,
                                            created_at: event.message.created_at,
                                        };
                                        activeMessages.push(messageData);
                                        renderMessages(activeMessages, currentUserId);
                                        loadChatWidgetData();
                                    })
                                    .error((error) => {
                                        console.warn('Chat subscription error', error);
                                    });
                            }
                        }
                    } catch (error) {
                        console.error(error);
                        if (chatWidgetMessages) {
                            const message = error?.message ? escapeHtml(error.message) : 'Unable to load messages.';
                            chatWidgetMessages.innerHTML = `<div class="chat-widget-placeholder">${message}</div>`;
                        }
                    } finally {
                        loadChatWidgetData();
                    }
                };

                const loadChatWidgetData = async () => {
                    try {
                        const response = await fetch(widgetConversationsUrl, {
                            headers: { 'Accept': 'application/json' },
                        });
                        if (!response.ok) {
                            throw new Error('Failed');
                        }
                        const data = await response.json();
                        renderConversationList(data);
                    } catch (error) {
                        if (chatWidgetList) {
                            chatWidgetList.innerHTML = '<div class="text-muted small">Unable to load conversations.</div>';
                        }
                    }
                };

                const sendChatMessage = async () => {
                    if (!activeConversationId || !chatWidgetInput) {
                        return;
                    }
                    const message = chatWidgetInput.value.trim();
                    if (!message) {
                        return;
                    }
                    if (chatWidgetSend?.dataset?.sending === 'true') {
                        return;
                    }
                    if (chatWidgetSend) {
                        chatWidgetSend.dataset.sending = 'true';
                        chatWidgetSend.disabled = true;
                    }
                    const clientId = `tmp_${Date.now()}_${Math.random().toString(16).slice(2)}`;
                    chatWidgetInput.value = '';
                    const optimisticMessage = {
                        user_id: currentUserId,
                        user_name: currentUserName,
                        message,
                        created_at: new Date().toLocaleString(),
                        status: 'sending',
                        client_id: clientId,
                    };
                    activeMessages.push(optimisticMessage);
                    renderMessages(activeMessages, currentUserId);
                    try {
                        const controller = new AbortController();
                        const timeoutId = setTimeout(() => controller.abort(), 10000);
                        const response = await fetch(messageUrlTemplate.replace('__ID__', activeConversationId), {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-CSRF-TOKEN': csrfToken ?? '',
                            },
                            body: new URLSearchParams({ message }),
                            signal: controller.signal,
                        });
                        clearTimeout(timeoutId);
                        if (!response.ok) {
                            throw new Error('Failed to send.');
                        }
                        const data = await response.json();
                        if (data.message) {
                            activeMessages = activeMessages.filter((item) => item.client_id !== clientId);
                            activeMessages.push(data.message);
                            renderMessages(activeMessages, currentUserId);
                            loadChatWidgetData();
                        }
                    } catch (error) {
                        activeMessages = activeMessages.map((item) => {
                            if (item.client_id === clientId) {
                                return { ...item, status: 'failed' };
                            }
                            return item;
                        });
                        renderMessages(activeMessages, currentUserId);
                    } finally {
                        if (chatWidgetSend) {
                            chatWidgetSend.dataset.sending = 'false';
                            chatWidgetSend.disabled = false;
                        }
                        chatWidgetInput.focus();
                    }
                };

                const retryFailedMessage = (clientId) => {
                    const failedMessage = activeMessages.find((item) => item.client_id === clientId);
                    if (!failedMessage) {
                        return;
                    }
                    chatWidgetInput.value = failedMessage.message ?? '';
                    activeMessages = activeMessages.filter((item) => item.client_id !== clientId);
                    renderMessages(activeMessages, currentUserId);
                    sendChatMessage();
                };

                const runSupportRequest = async () => {
                    if (!chatSupportStart || !chatSupportIssue) {
                        return;
                    }
                    const issueType = chatSupportIssue.value;
                    if (!issueType) {
                        return;
                    }
                    if (chatSupportStart.dataset.sending === 'true') {
                        return;
                    }
                    chatSupportStart.dataset.sending = 'true';
                    chatSupportStart.disabled = true;
                    if (chatSupportFeedback) {
                        chatSupportFeedback.innerHTML = '<div class="alert alert-info">Sending support request...</div>';
                    }
                    try {
                        const response = await fetch(supportUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken ?? '',
                            },
                            body: new URLSearchParams({ issue_type: issueType }),
                        });
                        if (!response.ok) {
                            throw new Error('Support request failed.');
                        }
                        const data = await response.json();
                        if (chatSupportFeedback) {
                            chatSupportFeedback.innerHTML = '<div class="alert alert-success">Support request sent. We will connect you shortly.</div>';
                        }
                        if (data.conversation_id) {
                            await loadChatWidgetData();
                            loadConversation(data.conversation_id);
                        }
                    } catch (error) {
                        if (chatSupportFeedback) {
                            chatSupportFeedback.innerHTML = '<div class="alert alert-danger">Unable to send support request.</div>';
                        }
                        if (chatSupportStart) {
                            chatSupportStart.disabled = false;
                        }
                    } finally {
                        if (chatSupportStart) {
                            chatSupportStart.dataset.sending = 'false';
                        }
                    }
                };

                const handleAcceptDecline = async (action) => {
                    if (!activeConversationId) {
                        return;
                    }
                    const urlTemplate = action === 'accept' ? acceptUrlTemplate : declineUrlTemplate;
                    const acceptLabel = chatWidgetAccept?.textContent;
                    const declineLabel = chatWidgetDecline?.textContent;
                    try {
                        if (action === 'accept' && chatWidgetAccept) {
                            chatWidgetAccept.disabled = true;
                            chatWidgetAccept.textContent = 'Accepting...';
                        }
                        if (action === 'decline' && chatWidgetDecline) {
                            chatWidgetDecline.disabled = true;
                            chatWidgetDecline.textContent = 'Declining...';
                        }
                        const response = await fetch(urlTemplate.replace('__ID__', activeConversationId), {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken ?? '',
                            },
                        });
                        if (!response.ok) {
                            throw new Error('Failed');
                        }
                        await loadChatWidgetData();
                        await loadConversation(activeConversationId);
                    } catch (error) {
                        if (chatWidgetMessages) {
                            chatWidgetMessages.innerHTML = '<div class="chat-widget-placeholder">Unable to update chat status.</div>';
                        }
                    } finally {
                        if (chatWidgetAccept) {
                            chatWidgetAccept.disabled = false;
                            if (acceptLabel) {
                                chatWidgetAccept.textContent = acceptLabel;
                            }
                        }
                        if (chatWidgetDecline) {
                            chatWidgetDecline.disabled = false;
                            if (declineLabel) {
                                chatWidgetDecline.textContent = declineLabel;
                            }
                        }
                    }
                };

                const handleCloseChat = async () => {
                    if (!activeConversationId || !chatWidgetClose) {
                        return;
                    }
                    if (chatWidgetClose.dataset.sending === 'true') {
                        return;
                    }
                    const originalLabel = chatWidgetClose.textContent;
                    chatWidgetClose.dataset.sending = 'true';
                    chatWidgetClose.disabled = true;
                    chatWidgetClose.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Closing...';
                    try {
                        const response = await fetch(closeUrlTemplate.replace('__ID__', activeConversationId), {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken ?? '',
                            },
                        });
                        if (!response.ok) {
                            throw new Error('Failed');
                        }
                        await loadChatWidgetData();
                        await loadConversation(activeConversationId, { subscribe: false, reset: true });
                    } catch (error) {
                        if (chatWidgetMessages) {
                            chatWidgetMessages.innerHTML = '<div class="chat-widget-placeholder">Unable to close chat.</div>';
                        }
                    } finally {
                        chatWidgetClose.dataset.sending = 'false';
                        chatWidgetClose.disabled = false;
                        chatWidgetClose.innerHTML = originalLabel;
                    }
                };

                const handleHistoryDelete = async (conversationId) => {
                    if (!conversationId) {
                        return;
                    }
                    try {
                        const response = await fetch(historyDeleteUrlTemplate.replace('__ID__', conversationId), {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken ?? '',
                            },
                        });
                        if (!response.ok) {
                            throw new Error('Failed');
                        }
                        if (activeConversationId && Number(activeConversationId) === Number(conversationId)) {
                            activeConversationId = null;
                            if (chatWidgetMessages) {
                                chatWidgetMessages.innerHTML = '<div class="chat-widget-placeholder">Conversation removed.</div>';
                            }
                            setInputEnabled(false);
                            toggleAcceptActions(false);
                            setCloseEnabled(false);
                        }
                        await loadChatWidgetData();
                    } catch (error) {
                        if (chatWidgetMessages) {
                            chatWidgetMessages.innerHTML = '<div class="chat-widget-placeholder">Unable to remove chat history.</div>';
                        }
                    }
                };

                chatWidget.addEventListener('show.bs.offcanvas', () => {
                    listenUserChannel();
                    loadChatWidgetData();
                    if (!chatPoller) {
                        chatPoller = setInterval(() => {
                            loadChatWidgetData();
                            if (activeConversationId) {
                                loadConversation(activeConversationId, { subscribe: false, reset: false });
                            }
                        }, 3000);
                    }
                });

                chatWidget.addEventListener('hidden.bs.offcanvas', () => {
                    if (chatPoller) {
                        clearInterval(chatPoller);
                        chatPoller = null;
                    }
                });

                document.addEventListener('click', (event) => {
                    const item = event.target.closest('.chat-widget-item');
                    if (item && item.dataset.conversationId) {
                        loadConversation(item.dataset.conversationId, { subscribe: true, reset: true });
                    }
                    const retryButton = event.target.closest('.chat-widget-retry');
                    if (retryButton && retryButton.dataset.retry) {
                        retryFailedMessage(retryButton.dataset.retry);
                    }
                    const deleteButton = event.target.closest('[data-history-delete]');
                    if (deleteButton && deleteButton.dataset.historyDelete) {
                        handleHistoryDelete(deleteButton.dataset.historyDelete);
                    }
                });

                if (chatWidgetSend) {
                    chatWidgetSend.addEventListener('click', sendChatMessage);
                }

                if (chatWidgetInput) {
                    chatWidgetInput.addEventListener('keydown', (event) => {
                        if (event.key === 'Enter' && !event.shiftKey) {
                            event.preventDefault();
                            sendChatMessage();
                        }
                    });
                }

                if (chatWidgetAccept) {
                    chatWidgetAccept.addEventListener('click', () => handleAcceptDecline('accept'));
                }

                if (chatWidgetDecline) {
                    chatWidgetDecline.addEventListener('click', () => handleAcceptDecline('decline'));
                }

                if (chatWidgetClose) {
                    chatWidgetClose.addEventListener('click', handleCloseChat);
                }

                if (chatWidgetActiveTab && chatWidgetHistoryTab) {
                    chatWidgetActiveTab.addEventListener('click', () => {
                        currentView = 'active';
                        chatWidgetActiveTab.classList.add('active');
                        chatWidgetHistoryTab.classList.remove('active');
                        loadChatWidgetData();
                    });
                    chatWidgetHistoryTab.addEventListener('click', () => {
                        currentView = 'history';
                        chatWidgetHistoryTab.classList.add('active');
                        chatWidgetActiveTab.classList.remove('active');
                        loadChatWidgetData();
                    });
                }

                if (chatSupportBot) {
                    chatSupportBot.querySelectorAll('[data-issue]').forEach((button) => {
                        button.addEventListener('click', () => {
                            const issue = button.getAttribute('data-issue');
                            if (chatSupportIssue) {
                                chatSupportIssue.value = issue ?? '';
                            }
                            chatSupportBot.querySelectorAll('[data-issue]').forEach((btn) => {
                                btn.classList.remove('btn-primary');
                                btn.classList.add('btn-outline-primary');
                            });
                            button.classList.remove('btn-outline-primary');
                            button.classList.add('btn-primary');
                            if (chatSupportStart) {
                                chatSupportStart.disabled = false;
                            }
                        });
                    });
                }

                if (chatSupportStart) {
                    chatSupportStart.addEventListener('click', () => {
                        runSupportRequest();
                    });
                }
            }
        </script>
        @stack('scripts')
    </body>
</html>
