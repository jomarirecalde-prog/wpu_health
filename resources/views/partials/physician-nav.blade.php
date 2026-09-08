<nav class="portal-nav" aria-label="Physician portal">
    <div class="portal-nav__brand"><i class="fas fa-stethoscope"></i> Physician Portal</div>
    <a href="{{ route('physician.dashboard') }}" class="portal-nav__link {{ request()->routeIs('physician.dashboard') ? 'is-active' : '' }}"><i class="fas fa-home"></i> Dashboard</a>
    <a href="{{ route('physician.calendar') }}" class="portal-nav__link {{ request()->routeIs('physician.calendar') ? 'is-active' : '' }}"><i class="fas fa-calendar"></i> My Calendar</a>
    <a href="{{ route('physician.appointments') }}" class="portal-nav__link {{ request()->routeIs('physician.appointments*') ? 'is-active' : '' }}"><i class="fas fa-list"></i> My Appointments</a>
    <a href="{{ route('physician.schedule') }}" class="portal-nav__link {{ request()->routeIs('physician.schedule*') ? 'is-active' : '' }}"><i class="fas fa-clock"></i> My Schedule</a>
    <a href="{{ route('physician.profile') }}" class="portal-nav__link {{ request()->routeIs('physician.profile') ? 'is-active' : '' }}"><i class="fas fa-user"></i> Profile</a>
    <form action="{{ route('logout') }}" method="post" class="portal-nav__link" style="margin-top:auto;border:none;background:none;padding:0">@csrf
        <button type="submit" style="all:unset;cursor:pointer;display:flex;align-items:center;gap:0.5rem;width:100%"><i class="fas fa-sign-out-alt"></i> Logout</button>
    </form>
</nav>
