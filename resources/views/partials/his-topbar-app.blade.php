@php
    if (auth('portal')->check()) {
        $user = auth('portal')->user();
        $displayName = $user->name;
        $profileUrl = route('portal.profile');
        $notificationsUrl = route('portal.notifications');
        $bookUrl = route('portal.book');
        $showBookLink = true;
    } elseif (auth('physician')->check()) {
        $user = auth('physician')->user();
        $displayName = $user->displayName();
        $profileUrl = route('physician.profile');
        $notificationsUrl = null;
        $bookUrl = null;
        $showBookLink = false;
    } else {
        $displayName = 'User';
        $profileUrl = url('/');
        $notificationsUrl = null;
        $bookUrl = null;
        $showBookLink = false;
    }
    $initials = auth('portal')->check() ? auth('portal')->user()->initials() : strtoupper(substr($displayName, 0, 2));
@endphp
<header class="admin-topbar no-print" role="banner">
    <button type="button" class="mobile-menu-btn" onclick="toggleSidebar()" aria-label="Open or close navigation menu" aria-expanded="false" aria-controls="sidebar">
        <i class="fas fa-bars" aria-hidden="true"></i>
    </button>
    <span class="admin-topbar-title">WPU Health Services</span>
    <div class="topbar-search">
        <i class="fas fa-search search-ico" aria-hidden="true"></i>
        <label for="his-global-search" class="visually-hidden">Search</label>
        <input type="search" id="his-global-search" placeholder="Search appointments, physicians…" autocomplete="off">
    </div>
    <div class="topbar-actions">
        <div class="topbar-meta" aria-live="polite">
            <span class="date">{{ now()->format('D, M j, Y') }}</span>
            <span class="time" data-his-clock>{{ now()->format('H:i:s') }}</span>
        </div>
        <button type="button" class="icon-btn" onclick="typeof hisToggleTheme==='function'&&hisToggleTheme()" title="Toggle theme" aria-label="Toggle theme">
            <i class="fas fa-moon" data-his-theme-icon aria-hidden="true"></i>
        </button>
        <div class="topbar-profile" data-his-dropdown>
            <button type="button" class="topbar-profile-btn" data-his-dropdown-btn aria-haspopup="true">
                @if(auth('portal')->check())
                    <x-portal-user-avatar size="topbar" class="av" />
                @else
                    <span class="av">{{ $initials }}</span>
                @endif
                <span class="name">{{ $displayName }}</span>
                <i class="fas fa-chevron-down" style="font-size:10px;opacity:.6;" aria-hidden="true"></i>
            </button>
            <div class="topbar-dropdown" data-his-dropdown-menu role="menu">
                <a href="{{ $profileUrl }}" role="menuitem"><i class="fas fa-user"></i> Profile</a>
                @if($notificationsUrl)
                    <a href="{{ $notificationsUrl }}" role="menuitem"><i class="fas fa-bell"></i> Notifications</a>
                @endif
                @if($showBookLink && $bookUrl)
                    <a href="{{ $bookUrl }}" role="menuitem"><i class="fas fa-calendar-plus"></i> Book Consultation</a>
                @endif
                <div class="sep" role="separator"></div>
                <form action="{{ route('logout') }}" method="post" style="margin:0">@csrf
                    <button type="submit" style="width:100%;text-align:left;background:none;border:none;cursor:pointer;padding:10px 16px;font:inherit;color:inherit" role="menuitem">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
