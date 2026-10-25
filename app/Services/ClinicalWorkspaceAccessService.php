<?php

namespace App\Services;

class ClinicalWorkspaceAccessService
{
    /**
     * @return list<string>
     */
    public function clinicalRoles(): array
    {
        return config('healthhub.clinical_roles', []);
    }

    public function normalizePage(string $page): string
    {
        $page = strtolower(trim($page));

        return match ($page) {
            'certificates', 'referrals' => 'certificates_referrals',
            'dental_records', 'health_records' => 'health_dental_records',
            default => $page,
        };
    }

    public function canAccessPage(string $role, string $page): bool
    {
        $role = strtolower(trim($role));
        $page = $this->normalizePage($page);
        $map = config('healthhub.workspace.pages', []);

        if (! isset($map[$page])) {
            return false;
        }

        return in_array($role, $map[$page], true);
    }

    public function canAccessRecordsTab(string $role, string $tab): bool
    {
        $role = strtolower(trim($role));
        $tab = strtolower(trim($tab));
        $map = config('healthhub.workspace.records_tabs', []);

        if (! isset($map[$tab])) {
            return false;
        }

        return in_array($role, $map[$tab], true);
    }

    public function defaultRecordsTabForRole(string $role): string
    {
        $role = strtolower(trim($role));
        $landing = $this->landingForRole($role);
        if (($landing['page'] ?? '') === 'health_dental_records') {
            $tab = strtolower((string) ($landing['query']['records_tab'] ?? ''));

            return in_array($tab, ['health', 'dental'], true) ? $tab : 'health';
        }

        return match ($role) {
            'dentist' => 'dental',
            'physician', 'nurse', 'staff' => 'health',
            default => 'dental',
        };
    }

    /**
     * Pick the first records tab this role may view (for redirects / fallbacks).
     */
    public function primaryRecordsTabForRole(string $role): string
    {
        foreach (['health', 'dental'] as $tab) {
            if ($this->canAccessRecordsTab($role, $tab)) {
                return $tab;
            }
        }

        return $this->defaultRecordsTabForRole($role);
    }

    public function canAccessCalendarRoute(string $role, ?string $routeName): bool
    {
        if ($routeName === null || ! str_starts_with($routeName, 'admin.calendar.')) {
            return false;
        }

        $suffix = substr($routeName, strlen('admin.calendar.'));
        $map = config('healthhub.workspace.calendar_routes', []);

        if (! isset($map[$suffix])) {
            return false;
        }

        return in_array(strtolower(trim($role)), $map[$suffix], true);
    }

    /**
     * @return array{page: string, query: array<string, string>}
     */
    public function landingForRole(string $role): array
    {
        $role = strtolower(trim($role));
        $landings = config('healthhub.workspace.landing', []);
        $landing = $landings[$role] ?? ['page' => 'dashboard', 'query' => []];

        return [
            'page' => (string) ($landing['page'] ?? 'dashboard'),
            'query' => is_array($landing['query'] ?? null) ? $landing['query'] : [],
        ];
    }

    public function workspaceUrlForRole(string $role, string $routeName = 'admin.workspace'): string
    {
        $landing = $this->landingForRole($role);
        $url = route($routeName, ['path' => 'admin/admin.php']);
        $query = array_merge(['page' => $landing['page']], $landing['query']);

        return $url.'?'.http_build_query($query);
    }

    public function sidebarPartialForRole(string $role): string
    {
        return match (strtolower(trim($role))) {
            'physician' => 'partials.his-sidebar-physician-workspace',
            'dentist' => 'partials.his-sidebar-dentist',
            'nurse' => 'partials.his-sidebar-nurse',
            'staff' => 'partials.his-sidebar-staff',
            default => 'partials.his-sidebar',
        };
    }

    public function portalTitleForRole(string $role): string
    {
        return match (strtolower(trim($role))) {
            'physician' => 'Physician Portal',
            'dentist' => 'Dentist Portal',
            'nurse' => 'Nurse Portal',
            'staff' => 'Staff Portal',
            default => 'Clinical Workspace',
        };
    }
}
