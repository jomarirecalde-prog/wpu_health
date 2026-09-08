@php
    $physician = auth('physician')->user();
    $userName = $physician ? $physician->displayName() : 'Physician';
@endphp
<div class="sidebar no-print" id="sidebar" role="navigation" aria-label="Physician navigation">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="{{ $wpuAssets }}/images/logo.png" alt="" width="40" height="40" onerror="this.style.display='none'">
        </div>
        <div class="sidebar-brand">
            <strong>WPU Health Services</strong>
            <p>Physician Portal</p>
        </div>
    </div>
    <nav class="sidebar-menu sidebar-nav" aria-label="Physician modules">
        <div class="sidebar-section-label">Overview</div>
        <a href="{{ route('physician.dashboard') }}" class="nav-link {{ request()->routeIs('physician.dashboard') ? 'active' : '' }}">
            <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
            <span class="nav-link-text">Dashboard</span>
        </a>

        <div class="sidebar-section-label">Schedule</div>
        <a href="{{ route('physician.calendar') }}" class="nav-link {{ request()->routeIs('physician.calendar') ? 'active' : '' }}">
            <i class="fas fa-calendar-check" aria-hidden="true"></i>
            <span class="nav-link-text">My Calendar</span>
        </a>
        <a href="{{ route('physician.appointments') }}" class="nav-link {{ request()->routeIs('physician.appointments*') ? 'active' : '' }}">
            <i class="fas fa-list" aria-hidden="true"></i>
            <span class="nav-link-text">My Appointments</span>
        </a>
        <a href="{{ route('physician.schedule') }}" class="nav-link {{ request()->routeIs('physician.schedule*') ? 'active' : '' }}">
            <i class="fas fa-clock" aria-hidden="true"></i>
            <span class="nav-link-text">My Schedule</span>
        </a>

        <div class="sidebar-section-label">Account</div>
        <a href="{{ route('physician.profile') }}" class="nav-link {{ request()->routeIs('physician.profile') ? 'active' : '' }}">
            <i class="fas fa-user" aria-hidden="true"></i>
            <span class="nav-link-text">Profile</span>
        </a>
    </nav>
    <div class="user-info">
        <div class="user-avatar" aria-hidden="true">{{ strtoupper(substr($userName, 0, 2)) }}</div>
        <div class="user-details">
            <div class="user-name">{{ $userName }}</div>
            <div class="user-actions">
                <form action="{{ route('logout') }}" method="post" style="display:inline">@csrf
                    <button type="submit" class="user-action-btn logout" style="background:none;border:none;cursor:pointer">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
