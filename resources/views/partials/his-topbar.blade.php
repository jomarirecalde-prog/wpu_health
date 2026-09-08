@php $adminUser = auth('admin')->user()->username ?? 'Admin'; @endphp
<header class="admin-topbar no-print" role="banner">
    <button type="button" class="mobile-menu-btn" onclick="toggleSidebar()" aria-label="Open or close navigation menu" aria-expanded="false" aria-controls="sidebar">
        <i class="fas fa-bars" aria-hidden="true"></i>
    </button>
    <span class="admin-topbar-title">WPU Health Services</span>
    <div class="topbar-search">
        <i class="fas fa-search search-ico" aria-hidden="true"></i>
        <label for="his-global-search" class="visually-hidden">Global search</label>
        <input type="search" id="his-global-search" placeholder="Search modules, actions…" autocomplete="off">
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
                <span class="av">{{ strtoupper(substr($adminUser, 0, 2)) }}</span>
                <span class="name">{{ $adminUser }}</span>
                <i class="fas fa-chevron-down" style="font-size:10px;opacity:.6;" aria-hidden="true"></i>
            </button>
            <div class="topbar-dropdown" data-his-dropdown-menu role="menu">
                <a href="{{ route('admin.workspace', ['path' => 'admin/admin.php']) }}?page=settings" role="menuitem"><i class="fas fa-cog"></i> Settings</a>
                <a href="{{ route('admin.calendar.index') }}" role="menuitem"><i class="fas fa-calendar-check"></i> Calendar</a>
                <div class="sep" role="separator"></div>
                <form action="{{ route('admin.logout') }}" method="post" style="margin:0">@csrf
                    <button type="submit" style="width:100%;text-align:left;background:none;border:none;cursor:pointer;padding:10px 16px;font:inherit;color:inherit" role="menuitem">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
