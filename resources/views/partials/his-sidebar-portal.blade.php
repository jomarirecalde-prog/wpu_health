@php
    $portalUser = auth('portal')->user();
    $userName = $portalUser->name ?? 'Patient';
    $activeSection = $activeSection ?? '';
@endphp
<div class="sidebar no-print" id="sidebar" role="navigation" aria-label="Patient navigation">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="{{ $wpuAssets }}/images/logo.png" alt="" width="40" height="40" onerror="this.style.display='none'">
        </div>
        <div class="sidebar-brand">
            <strong>WPU Health Services</strong>
            <p>Consultation Portal</p>
        </div>
    </div>
    <nav class="sidebar-menu sidebar-nav" aria-label="Patient modules">
        <div class="sidebar-section-label">Overview</div>
        <a href="{{ route('portal.dashboard') }}" class="nav-link {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}">
            <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
            <span class="nav-link-text">Dashboard</span>
        </a>

        <div class="sidebar-section-label">Consultations</div>
        <a href="{{ route('portal.book') }}" class="nav-link {{ request()->routeIs('portal.book*') ? 'active' : '' }}">
            <i class="fas fa-calendar-plus" aria-hidden="true"></i>
            <span class="nav-link-text">Book Consultation</span>
        </a>
        <a href="{{ route('portal.calendar') }}" class="nav-link {{ request()->routeIs('portal.calendar') ? 'active' : '' }}">
            <i class="fas fa-calendar-check" aria-hidden="true"></i>
            <span class="nav-link-text">My Calendar</span>
        </a>
        <a href="{{ route('portal.appointments') }}" class="nav-link {{ request()->routeIs('portal.appointments*') ? 'active' : '' }}">
            <i class="fas fa-list" aria-hidden="true"></i>
            <span class="nav-link-text">Appointments</span>
        </a>
        <a href="{{ route('portal.physicians') }}" class="nav-link {{ request()->routeIs('portal.physicians') ? 'active' : '' }}">
            <i class="fas fa-user-md" aria-hidden="true"></i>
            <span class="nav-link-text">Physicians</span>
        </a>

        <div class="sidebar-section-label">Account</div>
        <a href="{{ route('portal.notifications') }}" class="nav-link {{ request()->routeIs('portal.notifications') ? 'active' : '' }}">
            <i class="fas fa-bell" aria-hidden="true"></i>
            <span class="nav-link-text">Notifications</span>
        </a>
        <a href="{{ route('portal.profile') }}" class="nav-link {{ request()->routeIs('portal.profile') ? 'active' : '' }}">
            <i class="fas fa-user" aria-hidden="true"></i>
            <span class="nav-link-text">Profile</span>
        </a>
    </nav>
    <div class="user-info">
        <x-portal-user-avatar :user="$portalUser" />
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
