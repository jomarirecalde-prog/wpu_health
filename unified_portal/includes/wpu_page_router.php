<?php
/**
 * Page view router — maps admin.php switch cases to modular include files.
 */

if (! function_exists('wpu_admin_page_map')) {
    /**
     * @return array<string, string>
     */
    function wpu_admin_page_map(): array
    {
        return [
            'dashboard' => 'dashboard.php',
            'certificates' => 'certificates_referrals.php',
            'referrals' => 'certificates_referrals.php',
            'certificates_referrals' => 'certificates_referrals.php',
            'settings' => 'settings.php',
            'admin_management' => 'admin_management.php',
            'user_logs' => 'user_logs.php',
            'dental_records' => 'health_dental_records.php',
            'health_records' => 'health_dental_records.php',
            'health_dental_records' => 'health_dental_records.php',
            'view_record' => 'view_record.php',
            'reports' => 'reports.php',
            'backup' => 'backup.php',
            'consent_refusal_module' => 'consent_refusal_module.php',
            'secure_consent' => 'secure_consent.php',
            'view_consent' => 'view_consent.php',
            'consent_refusal' => 'consent_refusal.php',
            'view_refusal' => 'view_refusal.php',
            'referrals_hub' => 'referrals_hub.php',
            'dispensary' => 'dispensary.php',
        ];
    }
}

if (! function_exists('wpu_admin_page_path')) {
    function wpu_admin_page_path(string $page): ?string
    {
        $map = wpu_admin_page_map();
        if (! isset($map[$page])) {
            return null;
        }

        $path = __DIR__.'/../admin/pages/'.$map[$page];

        return is_readable($path) ? $path : null;
    }
}

if (! function_exists('wpu_render_admin_page')) {
    /**
     * Include a modular admin page with the caller's variables in scope.
     * Pass get_defined_vars() from admin.php so templates see $cert_total, $conn, etc.
     *
     * @param  array<string, mixed>  $vars
     */
    function wpu_render_admin_page(string $page, array $vars = []): bool
    {
        $path = wpu_admin_page_path($page);
        if ($path === null) {
            echo '<div class="alert alert-error">Page module not found.</div>';

            return false;
        }

        // Includes inside this function do not inherit admin.php locals unless extracted.
        unset($vars['this'], $vars['GLOBALS'], $vars['path'], $vars['vars'], $vars['page']);
        if ($vars !== []) {
            extract($vars, EXTR_SKIP);
        }

        include $path;

        return true;
    }
}

if (! function_exists('wpu_admin_page_exists')) {
    function wpu_admin_page_exists(string $page): bool
    {
        return wpu_admin_page_path($page) !== null;
    }
}
