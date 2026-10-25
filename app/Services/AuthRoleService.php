<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Physician;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

class AuthRoleService
{
    public const ROLE_PATIENT = 'patient';

    public const ROLE_PHYSICIAN = 'physician';

    public const ROLE_DENTIST = 'dentist';

    public const ROLE_NURSE = 'nurse';

    public const ROLE_STAFF = 'staff';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_SUPER_ADMIN = 'super_admin';

    /** @var array<string, string> */
    private const GUARD_ROLES = [
        'portal' => self::ROLE_PATIENT,
        'physician' => self::ROLE_PHYSICIAN,
        'admin' => self::ROLE_ADMIN,
    ];

    /** @var list<string> */
    private const GUARD_PRIORITY = ['admin', 'physician', 'portal'];

    public function __construct(
        private readonly HealthHubRbacService $rbac,
        private readonly ClinicalWorkspaceAccessService $workspace,
    ) {
    }

    public function isAuthenticated(): bool
    {
        return $this->currentGuard() !== null;
    }

    public function currentGuard(): ?string
    {
        foreach (self::GUARD_PRIORITY as $guard) {
            if (Auth::guard($guard)->check()) {
                return $guard;
            }
        }

        return null;
    }

    public function currentRole(): ?string
    {
        $guard = $this->currentGuard();

        if ($guard === null) {
            return null;
        }

        if ($guard === 'admin') {
            $admin = Auth::guard('admin')->user();

            return $admin instanceof Admin ? $this->rbac->resolveRole($admin) : self::ROLE_ADMIN;
        }

        if ($guard === 'physician') {
            $physician = Auth::guard('physician')->user();

            return $physician instanceof Physician
                ? $physician->clinicalPortalRole()
                : self::ROLE_PHYSICIAN;
        }

        return self::GUARD_ROLES[$guard] ?? null;
    }

    public function currentUser(): ?Authenticatable
    {
        $guard = $this->currentGuard();

        return $guard ? Auth::guard($guard)->user() : null;
    }

    public function dashboardUrl(): string
    {
        $role = $this->currentRole();
        $guard = $this->currentGuard();

        if ($role === self::ROLE_PATIENT) {
            return route('portal.dashboard');
        }

        if ($guard === 'physician') {
            if ($role === self::ROLE_DENTIST) {
                return $this->workspace->workspaceUrlForRole(self::ROLE_DENTIST, 'physician.workspace');
            }

            return route('physician.dashboard');
        }

        if ($guard === 'admin' && in_array($role, [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN], true)) {
            return route('admin.dashboard');
        }

        if ($guard === 'admin' && in_array($role, [
            self::ROLE_PHYSICIAN,
            self::ROLE_DENTIST,
            self::ROLE_NURSE,
            self::ROLE_STAFF,
        ], true)) {
            return $this->workspace->workspaceUrlForRole($role);
        }

        return url('/');
    }

    public function dashboardLabel(): string
    {
        return match ($this->currentRole()) {
            self::ROLE_PATIENT => __('Go to My Dashboard'),
            self::ROLE_PHYSICIAN => $this->currentGuard() === 'physician'
                ? __('Go to Physician Dashboard')
                : __('Go to Physician Workspace'),
            self::ROLE_DENTIST => __('Go to Dentist Dashboard'),
            self::ROLE_NURSE => __('Go to Nurse Dashboard'),
            self::ROLE_STAFF => __('Go to Staff Dashboard'),
            self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN => __('Go to Admin Dashboard'),
            default => __('Go to Dashboard'),
        };
    }

    public function guardForRole(string $role): ?string
    {
        if ($role === self::ROLE_SUPER_ADMIN) {
            return 'admin';
        }

        if (in_array($role, [
            self::ROLE_ADMIN,
            self::ROLE_PHYSICIAN,
            self::ROLE_DENTIST,
            self::ROLE_NURSE,
            self::ROLE_STAFF,
        ], true)) {
            return 'admin';
        }

        foreach (self::GUARD_ROLES as $guard => $guardRole) {
            if ($guardRole === $role) {
                return $guard;
            }
        }

        return null;
    }

    public function hasRole(string ...$roles): bool
    {
        $current = $this->currentRole();

        if ($current === null) {
            return false;
        }

        if ($current === self::ROLE_SUPER_ADMIN && in_array(self::ROLE_ADMIN, $roles, true)) {
            return true;
        }

        return in_array($current, $roles, true);
    }

    public function isClinicalStaffAdmin(): bool
    {
        if ($this->currentGuard() !== 'admin') {
            return false;
        }

        return in_array($this->currentRole(), [
            self::ROLE_PHYSICIAN,
            self::ROLE_DENTIST,
            self::ROLE_NURSE,
            self::ROLE_STAFF,
        ], true);
    }
}
