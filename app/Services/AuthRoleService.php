<?php

namespace App\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

class AuthRoleService
{
    public const ROLE_PATIENT = 'patient';

    public const ROLE_PHYSICIAN = 'physician';

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
            if ($admin instanceof \App\Models\Admin && $admin->isSuperAdmin()) {
                return self::ROLE_SUPER_ADMIN;
            }

            return self::ROLE_ADMIN;
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
        return match ($this->currentRole()) {
            self::ROLE_PATIENT => route('portal.dashboard'),
            self::ROLE_PHYSICIAN => route('physician.dashboard'),
            self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN => route('admin.dashboard'),
            default => url('/'),
        };
    }

    public function dashboardLabel(): string
    {
        return match ($this->currentRole()) {
            self::ROLE_PATIENT => __('Go to My Dashboard'),
            self::ROLE_PHYSICIAN => __('Go to Physician Dashboard'),
            self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN => __('Go to Admin Dashboard'),
            default => __('Go to Dashboard'),
        };
    }

    public function guardForRole(string $role): ?string
    {
        if ($role === self::ROLE_SUPER_ADMIN) {
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
}
