<nav class="navbar navbar-expand-lg bg-white border-bottom">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="{{ route('dashboard') }}">Tele-Fleet</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link @if (request()->routeIs('dashboard')) active @endif" href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                @if (Auth::user()?->role === \App\Models\User::ROLE_SUPER_ADMIN)
                    <li class="nav-item">
                        <a class="nav-link @if (request()->routeIs('admin.users.*')) active @endif" href="{{ route('admin.users.index') }}">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if (request()->routeIs('branches.*')) active @endif" href="{{ route('branches.index') }}">Branches</a>
                    </li>
                @endif
                @if (in_array(Auth::user()?->role, [\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_FLEET_MANAGER], true))
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
                    {{ Auth::user()->name }}
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
