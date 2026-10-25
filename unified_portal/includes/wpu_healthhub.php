<?php

declare(strict_types=1);

/**
 * WPU HealthHub — shared branding and RBAC for legacy unified_portal.
 */

if (defined('WPU_HEALTHHUB_LOADED')) {
    return;
}
define('WPU_HEALTHHUB_LOADED', true);

function wpu_healthhub_name(): string
{
    if (function_exists('config')) {
        return (string) config('healthhub.name', 'WPU HealthHub');
    }

    return 'WPU HealthHub';
}

function wpu_healthhub_tagline(): string
{
    if (function_exists('config')) {
        return (string) config('healthhub.tagline', 'Your unified gateway to campus wellness.');
    }

    return 'Your unified gateway to campus wellness.';
}

function wpu_healthhub_transactions(): array
{
    if (function_exists('config')) {
        return config('healthhub.transactions', []);
    }

    return [];
}

function wpu_healthhub_medical_case_types(): array
{
    if (function_exists('config')) {
        return config('healthhub.medical_case_types', []);
    }

    return [];
}

/**
 * Resolve clinical role for the logged-in admin (Laravel bridge or legacy session).
 */
function wpu_healthhub_admin_role(PDO $pdo): string
{
    if (function_exists('auth') && auth()->guard('physician')->check()) {
        $physician = auth()->guard('physician')->user();
        if ($physician && method_exists($physician, 'clinicalPortalRole')) {
            return $physician->clinicalPortalRole();
        }
    }

    if (function_exists('auth') && auth()->guard('admin')->check()) {
        $admin = auth()->guard('admin')->user();
        if ($admin && method_exists($admin, 'isSuperAdmin') && $admin->isSuperAdmin()) {
            return 'super_admin';
        }
        $role = strtolower(trim((string) ($admin->role ?? 'admin')));

        return $role !== '' ? $role : 'admin';
    }

    $username = $_SESSION['admin_username'] ?? '';
    if ($username === '') {
        return 'guest';
    }

    try {
        $stmt = $pdo->prepare('SELECT role FROM admins WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $role = strtolower(trim((string) $stmt->fetchColumn()));
        if ($role === 'super_admin') {
            return 'super_admin';
        }

        return $role !== '' ? $role : 'admin';
    } catch (PDOException) {
        return 'admin';
    }
}

function wpu_healthhub_can_view_medical_soda(string $role): bool
{
    return in_array($role, ['super_admin', 'admin', 'physician'], true);
}

function wpu_healthhub_can_view_clinical_diagnosis(string $role): bool
{
    return in_array($role, ['super_admin', 'admin', 'physician', 'nurse'], true);
}

function wpu_healthhub_can_access_dental(string $role): bool
{
    return in_array($role, ['super_admin', 'admin', 'physician', 'dentist'], true);
}

function wpu_healthhub_can_access_medical(string $role): bool
{
    return in_array($role, ['super_admin', 'admin', 'physician', 'nurse', 'staff'], true);
}

function wpu_healthhub_can_manage_dispensary(string $role): bool
{
    return in_array(strtolower(trim($role)), ['super_admin', 'admin', 'physician', 'nurse', 'staff'], true);
}

/**
 * @param  array<string, mixed>  $record
 * @return array<string, mixed>
 */
function wpu_healthhub_filter_medical_record(array $record, string $role): array
{
    if (wpu_healthhub_can_view_medical_soda($role)) {
        return $record;
    }

    foreach (['subjective', 'objectives', 'assessment'] as $field) {
        if (array_key_exists($field, $record)) {
            $record[$field] = null;
        }
    }

    if (! wpu_healthhub_can_view_clinical_diagnosis($role)) {
        foreach (['diagnosis', 'treatment', 'diagnostics', 'plan', 'visit_quick_snapshot'] as $field) {
            if (array_key_exists($field, $record)) {
                $record[$field] = null;
            }
        }
    }

    if ($role === 'staff') {
        foreach (['subjective', 'objectives', 'diagnostics', 'assessment', 'plan', 'diagnosis', 'treatment'] as $field) {
            if (array_key_exists($field, $record)) {
                $record[$field] = null;
            }
        }
    }

    return $record;
}

function wpu_schema_has_column(PDO $pdo, string $table, string $column): bool
{
    static $cache = [];
    $key = $table.'.'.$column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);
        $cache[$key] = ((int) $stmt->fetchColumn()) > 0;
    } catch (PDOException) {
        $cache[$key] = false;
    }

    return $cache[$key];
}

function wpu_healthhub_require_module_access(PDO $pdo, string $module, bool $json = true): void
{
    $role = wpu_healthhub_admin_role($pdo);
    $module = strtolower($module);

    $allowed = match ($module) {
        'dental' => wpu_healthhub_can_access_dental($role),
        'health', 'medical' => wpu_healthhub_can_access_medical($role),
        default => in_array($role, ['super_admin', 'admin', 'physician'], true),
    };

    if ($allowed) {
        return;
    }

    if ($json) {
        http_response_code(403);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'You do not have permission to access this module.']);
        exit;
    }

    http_response_code(403);
    echo '<div class="alert alert-error">You do not have permission to access this module.</div>';
    exit;
}

function wpu_healthhub_normalize_workspace_page(string $page): string
{
    $page = strtolower(trim($page));

    return match ($page) {
        'certificates', 'referrals' => 'certificates_referrals',
        'dental_records', 'health_records' => 'health_dental_records',
        default => $page,
    };
}

function wpu_healthhub_workspace_config(string $key, array $fallback = []): array
{
    if (function_exists('config')) {
        $value = config('healthhub.workspace.'.$key, $fallback);

        return is_array($value) ? $value : $fallback;
    }

    return $fallback;
}

function wpu_healthhub_can_access_workspace_page(string $role, string $page): bool
{
    $role = strtolower(trim($role));
    $page = wpu_healthhub_normalize_workspace_page($page);
    $map = wpu_healthhub_workspace_config('pages');

    if ($map === []) {
        return in_array($role, ['super_admin', 'admin', 'physician', 'dentist', 'nurse', 'staff'], true);
    }

    if (! isset($map[$page])) {
        return false;
    }

    return in_array($role, $map[$page], true);
}

function wpu_healthhub_can_access_records_tab(string $role, string $tab): bool
{
    $role = strtolower(trim($role));
    $tab = strtolower(trim($tab));
    $map = wpu_healthhub_workspace_config('records_tabs');

    if ($map === [] || ! isset($map[$tab])) {
        return $tab === 'dental'
            ? wpu_healthhub_can_access_dental($role)
            : wpu_healthhub_can_access_medical($role);
    }

    return in_array($role, $map[$tab], true);
}

function wpu_healthhub_enforce_workspace_page(PDO $pdo, string $page): void
{
    if ($page === 'login') {
        return;
    }

    $role = wpu_healthhub_admin_role($pdo);
    $normalized = wpu_healthhub_normalize_workspace_page($page);

    if (! wpu_healthhub_can_access_workspace_page($role, $normalized)) {
        http_response_code(403);
        echo '<div class="alert alert-error">You do not have permission to access this module.</div>';
        exit;
    }

    if ($normalized === 'health_dental_records' && isset($_GET['records_tab'])) {
        $tab = strtolower((string) $_GET['records_tab']);
        if ($tab !== '' && ! wpu_healthhub_can_access_records_tab($role, $tab)) {
            http_response_code(403);
            echo '<div class="alert alert-error">You do not have permission to access this records module.</div>';
            exit;
        }
    }
}

function wpu_healthhub_default_records_tab(string $role): string
{
    if (function_exists('app')) {
        try {
            $service = app(\App\Services\ClinicalWorkspaceAccessService::class);

            return $service->defaultRecordsTabForRole($role);
        } catch (Throwable) {
            // fall through
        }
    }

    $role = strtolower(trim($role));

    return match ($role) {
        'dentist' => 'dental',
        'physician', 'nurse', 'staff' => 'health',
        default => 'dental',
    };
}

function wpu_healthhub_portal_title(string $role): string
{
    return match (strtolower(trim($role))) {
        'physician' => 'Physician Portal',
        'dentist' => 'Dentist Portal',
        'nurse' => 'Nurse Portal',
        'staff' => 'Staff Portal',
        default => wpu_healthhub_name(),
    };
}
