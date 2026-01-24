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

        <style>
            :root {
                --primary: #3b82f6;
                --secondary: #6b7280;
                --success: #10b981;
                --danger: #ef4444;
                --warning: #f59e0b;
                --info: #3b82f6;
                --dark: #1f2937;
                --light: #f9fafb;
            }

            body {
                font-family: "Manrope", system-ui, -apple-system, sans-serif;
                background: #f4f6fb;
            }

            .app-shell {
                min-height: 100vh;
                display: flex;
                flex-direction: column;
            }

            .navbar-brand {
                font-weight: 700;
                letter-spacing: 0.4px;
            }

            .app-content {
                flex: 1;
                padding: 2rem 0;
            }
        </style>
    </head>
    <body>
        <div class="app-shell">
            <nav class="navbar navbar-expand-lg bg-white border-bottom">
                <div class="container-fluid px-4">
                    <a class="navbar-brand text-primary" href="{{ route('dashboard') }}">Tele-Fleet</a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#appNavbar">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="appNavbar">
                        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                            <li class="nav-item">
                                <a class="nav-link @if (request()->routeIs('dashboard')) active @endif" href="{{ route('dashboard') }}">Dashboard</a>
                            </li>
                            @if (auth()->user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                                <li class="nav-item">
                                    <a class="nav-link @if (request()->routeIs('admin.users.*')) active @endif" href="{{ route('admin.users.index') }}">Users</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link @if (request()->routeIs('branches.*')) active @endif" href="{{ route('branches.index') }}">Branches</a>
                                </li>
                            @endif
                            @if (in_array(auth()->user()?->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true))
                                <li class="nav-item">
                                    <a class="nav-link @if (request()->routeIs('vehicles.*')) active @endif" href="{{ route('vehicles.index') }}">Vehicles</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link @if (request()->routeIs('drivers.*')) active @endif" href="{{ route('drivers.index') }}">Drivers</a>
                                </li>
                            @endif
                        </ul>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                {{ auth()->user()?->name }}
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button class="dropdown-item" type="submit">Log out</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <main class="app-content">
                <div class="container-fluid px-4">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    {{ $slot }}
                </div>
            </main>

            <footer class="border-top py-3 bg-white">
                <div class="container-fluid px-4 text-muted small">
                    &copy; {{ now()->year }} Tele-Fleet. All rights reserved.
                </div>
            </footer>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
