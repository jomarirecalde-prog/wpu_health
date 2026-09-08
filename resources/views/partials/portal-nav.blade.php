<nav class="portal-nav" aria-label="Patient portal">
    <div class="portal-nav__brand"><i class="fas fa-heartbeat"></i> Patient Portal</div>
    <a href="{{ route('portal.dashboard') }}" class="portal-nav__link {{ request()->routeIs('portal.dashboard') ? 'is-active' : '' }}"><i class="fas fa-home"></i> Dashboard</a>
    <a href="{{ route('portal.book') }}" class="portal-nav__link {{ request()->routeIs('portal.book*') ? 'is-active' : '' }}"><i class="fas fa-calendar-plus"></i> Book Consultation</a>
    <a href="{{ route('portal.appointments') }}" class="portal-nav__link {{ request()->routeIs('portal.appointments*') ? 'is-active' : '' }}"><i class="fas fa-list"></i> My Appointments</a>
    <a href="{{ route('portal.calendar') }}" class="portal-nav__link {{ request()->routeIs('portal.calendar') ? 'is-active' : '' }}"><i class="fas fa-calendar"></i> My Calendar</a>
    <a href="{{ route('portal.physicians') }}" class="portal-nav__link {{ request()->routeIs('portal.physicians') ? 'is-active' : '' }}"><i class="fas fa-user-md"></i> Physicians</a>
    <a href="{{ route('portal.notifications') }}" class="portal-nav__link {{ request()->routeIs('portal.notifications') ? 'is-active' : '' }}"><i class="fas fa-bell"></i> Notifications</a>
    <a href="{{ route('portal.profile') }}" class="portal-nav__link {{ request()->routeIs('portal.profile') ? 'is-active' : '' }}"><i class="fas fa-user"></i> Profile</a>
    <form action="{{ route('logout') }}" method="post" class="portal-nav__link" style="margin-top:auto;border:none;background:none;padding:0">@csrf
        <button type="submit" style="all:unset;cursor:pointer;display:flex;align-items:center;gap:0.5rem;width:100%"><i class="fas fa-sign-out-alt"></i> Logout</button>
    </form>
</nav>
