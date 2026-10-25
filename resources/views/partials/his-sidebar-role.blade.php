@php
    $roleNav = $roleNav ?? ($clinicalRole ?? 'staff');
    $adminUser = auth('admin')->user()->username ?? 'User';
    $workspaceUrl = $workspaceUrl ?? route('admin.workspace', ['path' => 'admin/admin.php']);
    $calendarBase = url('/admin/calendar');
    $portalTitles = [
        'physician' => 'Physician Portal',
        'dentist' => 'Dentist Portal',
        'nurse' => 'Nurse Portal',
        'staff' => 'Staff Portal',
    ];
    $portalTitle = $portalTitles[$roleNav] ?? 'Clinical Workspace';
    $recordsTabDefault = $roleNav === 'dentist' ? 'dental' : 'health';
    $recordsLabel = match ($roleNav) {
        'dentist' => 'Dental Records',
        'physician' => 'Medical & Dental Records',
        'staff' => 'Patient Records',
        default => 'Medical Records',
    };
    $recordsHref = $roleNav === 'physician'
        ? $workspaceUrl.'?page=health_dental_records'
        : $workspaceUrl.'?page=health_dental_records&amp;records_tab='.$recordsTabDefault;
@endphp
<div class="sidebar no-print" id="sidebar" role="navigation" aria-label="{{ $portalTitle }}">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="{{ $wpuAssets }}/images/logo.png" alt="" width="40" height="40" onerror="this.style.display='none'">
        </div>
        <div class="sidebar-brand">
            <strong>{{ $healthHubName ?? config('healthhub.name') }}</strong>
            <p>{{ $portalTitle }}</p>
        </div>
    </div>
    <nav class="sidebar-menu sidebar-nav" aria-label="Authorized modules">
        <div class="sidebar-section-label">Overview</div>
        <a href="{{ $workspaceUrl }}?page=dashboard" class="nav-link">
            <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
            <span class="nav-link-text">Dashboard</span>
        </a>

        <div class="sidebar-section-label">Clinical</div>
        @if (in_array($roleNav, ['physician'], true))
            <a href="{{ $workspaceUrl }}?page=certificates_referrals&amp;tab=certificates" class="nav-link">
                <i class="fas fa-file-medical" aria-hidden="true"></i>
                <span class="nav-link-text">Certificates &amp; Referrals</span>
            </a>
        @endif
        <a href="{{ $recordsHref }}" class="nav-link">
            <i class="fas fa-{{ $roleNav === 'dentist' ? 'tooth' : 'notes-medical' }}" aria-hidden="true"></i>
            <span class="nav-link-text">{{ $recordsLabel }}</span>
        </a>
        @if (in_array($roleNav, ['physician', 'dentist', 'nurse'], true))
            <a href="{{ $workspaceUrl }}?page=consent_refusal_module" class="nav-link">
                <i class="fas fa-file-contract" aria-hidden="true"></i>
                <span class="nav-link-text">Consent &amp; Refusal</span>
            </a>
        @endif
        @if ($roleNav === 'physician')
            <a href="{{ $workspaceUrl }}?page=referrals_hub" class="nav-link">
                <i class="fas fa-share-square" aria-hidden="true"></i>
                <span class="nav-link-text">Referrals</span>
            </a>
        @endif
        @if (in_array($roleNav, ['physician', 'nurse', 'staff'], true))
            <a href="{{ $workspaceUrl }}?page=dispensary" class="nav-link">
                <i class="fas fa-pills" aria-hidden="true"></i>
                <span class="nav-link-text">Dispensary</span>
            </a>
        @endif
        @if (in_array($roleNav, ['physician', 'nurse'], true))
            <a href="{{ $workspaceUrl }}?page=reports" class="nav-link">
                <i class="fas fa-chart-bar" aria-hidden="true"></i>
                <span class="nav-link-text">Reports</span>
            </a>
        @endif

        @if (in_array($roleNav, ['physician', 'dentist', 'nurse', 'staff'], true))
            <div class="sidebar-section-label">Schedule</div>
            <a href="{{ $calendarBase }}" class="nav-link">
                <i class="fas fa-calendar-check" aria-hidden="true"></i>
                <span class="nav-link-text">Calendar</span>
            </a>
            <a href="{{ $calendarBase }}/appointments" class="nav-link">
                <i class="fas fa-list" aria-hidden="true"></i>
                <span class="nav-link-text">Appointments</span>
            </a>
        @endif
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
