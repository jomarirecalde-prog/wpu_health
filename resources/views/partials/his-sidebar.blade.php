@php
    $adminUser = auth('admin')->user()->username ?? 'Admin';
    $workspaceUrl = route('admin.workspace', ['path' => 'admin/admin.php']);
    $calendarBase = url('/admin/calendar');
    $activeSection = $activeSection ?? 'calendar';
@endphp
<div class="sidebar no-print" id="sidebar" role="navigation" aria-label="Main navigation">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="{{ $wpuAssets }}/images/logo.png" alt="" width="40" height="40" onerror="this.style.display='none'">
        </div>
        <div class="sidebar-brand">
            <strong>WPU Medical</strong>
            <p>Enterprise HIS</p>
        </div>
    </div>
    <nav class="sidebar-menu sidebar-nav" aria-label="Admin modules">
        <div class="sidebar-section-label">Overview</div>
        <a href="{{ $workspaceUrl }}?page=dashboard" class="nav-link">
            <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
            <span class="nav-link-text">Dashboard</span>
        </a>

        <div class="sidebar-section-label">Clinical</div>
        <a href="{{ $workspaceUrl }}?page=certificates_referrals&amp;tab=certificates" class="nav-link">
            <i class="fas fa-file-medical" aria-hidden="true"></i>
            <span class="nav-link-text">Certificates &amp; Referrals</span>
        </a>
        <a href="{{ $workspaceUrl }}?page=health_dental_records&amp;records_tab=dental" class="nav-link">
            <i class="fas fa-notes-medical" aria-hidden="true"></i>
            <span class="nav-link-text">Health &amp; Dental</span>
        </a>
        <a href="{{ $workspaceUrl }}?page=reports" class="nav-link">
            <i class="fas fa-chart-bar" aria-hidden="true"></i>
            <span class="nav-link-text">Reports</span>
        </a>

        <div class="sidebar-section-label">Calendar</div>
        <a href="{{ $calendarBase }}" class="nav-link {{ $activeSection === 'calendar' ? 'active' : '' }}">
            <i class="fas fa-calendar-check" aria-hidden="true"></i>
            <span class="nav-link-text">Calendar</span>
        </a>
        <a href="{{ $calendarBase }}/appointments" class="nav-link {{ $activeSection === 'appointments' ? 'active' : '' }}">
            <i class="fas fa-list" aria-hidden="true"></i>
            <span class="nav-link-text">Appointments</span>
        </a>
        <a href="{{ $calendarBase }}/physicians" class="nav-link {{ $activeSection === 'physicians' ? 'active' : '' }}">
            <i class="fas fa-user-md" aria-hidden="true"></i>
            <span class="nav-link-text">Physicians</span>
        </a>
        <a href="{{ $calendarBase }}/reports" class="nav-link {{ $activeSection === 'reports' ? 'active' : '' }}">
            <i class="fas fa-chart-line" aria-hidden="true"></i>
            <span class="nav-link-text">Appointment Reports</span>
        </a>
        <a href="{{ $calendarBase }}/portal-users" class="nav-link {{ $activeSection === 'portal-users' ? 'active' : '' }}">
            <i class="fas fa-users" aria-hidden="true"></i>
            <span class="nav-link-text">Portal Users</span>
        </a>
        <a href="{{ $calendarBase }}/settings" class="nav-link {{ $activeSection === 'settings' ? 'active' : '' }}">
            <i class="fas fa-cog" aria-hidden="true"></i>
            <span class="nav-link-text">Appointment Settings</span>
        </a>

        <div class="sidebar-section-label">Administration</div>
        <a href="{{ $workspaceUrl }}?page=settings" class="nav-link">
            <i class="fas fa-cog" aria-hidden="true"></i>
            <span class="nav-link-text">Settings</span>
        </a>
        <a href="{{ $workspaceUrl }}?page=admin_management" class="nav-link">
            <i class="fas fa-user-shield" aria-hidden="true"></i>
            <span class="nav-link-text">User Management</span>
        </a>
        <a href="{{ $workspaceUrl }}?page=user_logs" class="nav-link">
            <i class="fas fa-history" aria-hidden="true"></i>
            <span class="nav-link-text">Activity Logs</span>
        </a>
    </nav>
    <div class="user-info">
        <div class="user-avatar" aria-hidden="true">{{ strtoupper(substr($adminUser, 0, 2)) }}</div>
        <div class="user-details">
            <div class="user-name">{{ $adminUser }}</div>
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
