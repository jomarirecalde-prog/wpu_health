<?php
/**
 * Role-aware sidebar navigation for the unified admin workspace.
 *
 * Expects: $page, $current_user, $his_assets, $clinical_role (optional)
 */
$clinical_role = $clinical_role ?? wpu_healthhub_admin_role($pdo);
$portal_title = wpu_healthhub_portal_title($clinical_role);
$records_tab_default = function_exists('wpu_healthhub_default_records_tab')
    ? wpu_healthhub_default_records_tab($clinical_role)
    : ($clinical_role === 'dentist' ? 'dental' : 'health');
$records_label = match ($clinical_role) {
    'dentist' => 'Dental Records',
    'physician' => 'Medical & Dental Records',
    'staff' => 'Patient Records',
    default => 'Medical Records',
};
$records_href = $clinical_role === 'physician'
    ? '?page=health_dental_records'
    : '?page=health_dental_records&records_tab='.rawurlencode($records_tab_default);
$wpu_calendar_base = (defined('WPU_LARAVEL_BRIDGE') && WPU_LARAVEL_BRIDGE && function_exists('url'))
    ? url('/admin/calendar')
    : '/admin/calendar';
$is_admin_role = in_array($clinical_role, ['super_admin', 'admin'], true);
$is_provider_dentist = function_exists('auth')
    && auth()->guard('physician')->check()
    && ! auth()->guard('admin')->check()
    && $clinical_role === 'dentist';
$provider_portal_base = (defined('WPU_LARAVEL_BRIDGE') && WPU_LARAVEL_BRIDGE && function_exists('url'))
    ? url('/physician')
    : '/physician';
?>
<nav class="sidebar-menu sidebar-nav" aria-label="Authorized modules">
    <?php if ($is_provider_dentist): ?>
        <div class="sidebar-section-label">Overview</div>
        <a href="<?php echo htmlspecialchars($provider_portal_base, ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">
            <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
            <span class="nav-link-text">Dashboard</span>
        </a>
    <?php else: ?>
    <div class="sidebar-section-label">Overview</div>
    <a href="?page=dashboard" class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>">
        <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
        <span class="nav-link-text">Dashboard</span>
    </a>
    <?php endif; ?>

    <div class="sidebar-section-label">Clinical</div>
    <?php if ($is_admin_role || $clinical_role === 'physician'): ?>
        <a href="?page=certificates_referrals&tab=certificates" class="nav-link <?php echo ($page === 'certificates' || $page === 'referrals' || $page === 'certificates_referrals') ? 'active' : ''; ?>">
            <i class="fas fa-file-medical" aria-hidden="true"></i>
            <span class="nav-link-text">Certificates &amp; Referrals</span>
        </a>
    <?php endif; ?>
    <a href="<?php echo htmlspecialchars($records_href, ENT_QUOTES, 'UTF-8'); ?>" class="nav-link <?php echo ($page === 'dental_records' || $page === 'health_records' || $page === 'health_dental_records' || $page === 'view_record') ? 'active' : ''; ?>">
        <i class="fas fa-<?php echo $clinical_role === 'dentist' ? 'tooth' : 'notes-medical'; ?>" aria-hidden="true"></i>
        <span class="nav-link-text"><?php echo htmlspecialchars($records_label, ENT_QUOTES, 'UTF-8'); ?></span>
    </a>
    <?php if ($is_admin_role || in_array($clinical_role, ['physician', 'dentist', 'nurse'], true)): ?>
        <?php
        require_once __DIR__.'/../../includes/wpu_consent_refusal_module.php';
        $cr_mod_active = wpu_consent_refusal_module_is_page((string) $page);
        ?>
        <a href="?page=consent_refusal_module" class="nav-link <?php echo $cr_mod_active ? 'active' : ''; ?>">
            <i class="fas fa-file-contract" aria-hidden="true"></i>
            <span class="nav-link-text">Consent &amp; Refusal</span>
        </a>
    <?php endif; ?>
    <?php if ($is_admin_role || $clinical_role === 'physician'): ?>
        <a href="?page=referrals_hub" class="nav-link <?php echo $page === 'referrals_hub' ? 'active' : ''; ?>">
            <i class="fas fa-share-square" aria-hidden="true"></i>
            <span class="nav-link-text">Referrals</span>
        </a>
    <?php endif; ?>
    <?php if ($is_admin_role || in_array($clinical_role, ['physician', 'nurse', 'staff'], true)): ?>
        <a href="?page=dispensary" class="nav-link <?php echo $page === 'dispensary' ? 'active' : ''; ?>">
            <i class="fas fa-pills" aria-hidden="true"></i>
            <span class="nav-link-text">Dispensary</span>
        </a>
    <?php endif; ?>
    <?php if ($is_admin_role || in_array($clinical_role, ['physician', 'nurse'], true)): ?>
        <a href="?page=reports" class="nav-link <?php echo $page === 'reports' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar" aria-hidden="true"></i>
            <span class="nav-link-text">Reports</span>
        </a>
    <?php endif; ?>

    <?php if ($is_provider_dentist): ?>
        <div class="sidebar-section-label">Schedule</div>
        <a href="<?php echo htmlspecialchars($provider_portal_base.'/calendar', ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">
            <i class="fas fa-calendar-check" aria-hidden="true"></i>
            <span class="nav-link-text">My Calendar</span>
        </a>
        <a href="<?php echo htmlspecialchars($provider_portal_base.'/appointments', ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">
            <i class="fas fa-list" aria-hidden="true"></i>
            <span class="nav-link-text">My Appointments</span>
        </a>
        <a href="<?php echo htmlspecialchars($provider_portal_base.'/schedule', ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">
            <i class="fas fa-clock" aria-hidden="true"></i>
            <span class="nav-link-text">My Schedule</span>
        </a>
    <?php elseif (! $is_admin_role): ?>
        <div class="sidebar-section-label">Schedule</div>
        <a href="<?php echo htmlspecialchars($wpu_calendar_base, ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">
            <i class="fas fa-calendar-check" aria-hidden="true"></i>
            <span class="nav-link-text">Calendar</span>
        </a>
        <a href="<?php echo htmlspecialchars($wpu_calendar_base.'/appointments', ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">
            <i class="fas fa-list" aria-hidden="true"></i>
            <span class="nav-link-text">Appointments</span>
        </a>
    <?php else: ?>
        <div class="sidebar-section-label">Calendar</div>
        <a href="<?php echo htmlspecialchars($wpu_calendar_base, ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">
            <i class="fas fa-calendar-check" aria-hidden="true"></i>
            <span class="nav-link-text">Calendar</span>
        </a>
        <a href="<?php echo htmlspecialchars($wpu_calendar_base.'/appointments', ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">
            <i class="fas fa-list" aria-hidden="true"></i>
            <span class="nav-link-text">Appointments</span>
        </a>
        <a href="<?php echo htmlspecialchars($wpu_calendar_base.'/physicians', ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">
            <i class="fas fa-user-md" aria-hidden="true"></i>
            <span class="nav-link-text">Doctors Appointment</span>
        </a>
        <a href="<?php echo htmlspecialchars($wpu_calendar_base.'/reports', ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">
            <i class="fas fa-chart-line" aria-hidden="true"></i>
            <span class="nav-link-text">Appointment Reports</span>
        </a>
        <a href="<?php echo htmlspecialchars($wpu_calendar_base.'/portal-users', ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">
            <i class="fas fa-users" aria-hidden="true"></i>
            <span class="nav-link-text">Portal Users</span>
        </a>
        <a href="<?php echo htmlspecialchars($wpu_calendar_base.'/settings', ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">
            <i class="fas fa-sliders-h" aria-hidden="true"></i>
            <span class="nav-link-text">Appointment Settings</span>
        </a>
    <?php endif; ?>

    <?php if ($is_admin_role): ?>
        <div class="sidebar-section-label">Administration</div>
        <a href="?page=settings" class="nav-link <?php echo $page === 'settings' ? 'active' : ''; ?>">
            <i class="fas fa-cog" aria-hidden="true"></i>
            <span class="nav-link-text">Settings</span>
        </a>
        <a href="?page=admin_management" class="nav-link <?php echo $page === 'admin_management' ? 'active' : ''; ?>">
            <i class="fas fa-user-shield" aria-hidden="true"></i>
            <span class="nav-link-text">User Management</span>
        </a>
        <a href="?page=user_logs" class="nav-link <?php echo $page === 'user_logs' ? 'active' : ''; ?>">
            <i class="fas fa-history" aria-hidden="true"></i>
            <span class="nav-link-text">Activity Logs</span>
        </a>
        <a href="?page=backup" class="nav-link <?php echo $page === 'backup' ? 'active' : ''; ?>">
            <i class="fas fa-database" aria-hidden="true"></i>
            <span class="nav-link-text">Backup</span>
        </a>
    <?php endif; ?>
</nav>
