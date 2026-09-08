<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Physician;
use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UnifiedAuthService
{
    public function __construct(private readonly AdminSecurityService $adminSecurity)
    {
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function attempt(string $identifier, string $password, bool $remember = false): array
    {
        $identifier = trim($identifier);

        if ($identifier === '' || $password === '') {
            return $this->failure();
        }

        if (str_contains($identifier, '@')) {
            $portalResult = $this->attemptPortal($identifier, $password, $remember);
            if ($portalResult['success']) {
                return $portalResult;
            }

            $physicianResult = $this->attemptPhysician($identifier, $password, $remember);
            if ($physicianResult['success']) {
                return $physicianResult;
            }
        }

        return $this->attemptAdmin($identifier, $password);
    }

    /**
     * @return array{success: bool, message: string}
     */
    private function attemptPortal(string $email, string $password, bool $remember): array
    {
        $user = PortalUser::query()->where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return $this->failure();
        }

        if (! $user->isActive()) {
            return [
                'success' => false,
                'message' => __('Your account is not active. Please contact the health services office.'),
            ];
        }

        Auth::guard('portal')->login($user, $remember);

        return ['success' => true, 'message' => ''];
    }

    /**
     * @return array{success: bool, message: string}
     */
    private function attemptPhysician(string $email, string $password, bool $remember): array
    {
        $physician = Physician::query()->where('email', $email)->first();

        if (! $physician || ! Hash::check($password, $physician->password)) {
            return $this->failure();
        }

        if (! $physician->isActive()) {
            return [
                'success' => false,
                'message' => __('Your account is not active. Please contact the health services office.'),
            ];
        }

        Auth::guard('physician')->login($physician, $remember);

        return ['success' => true, 'message' => ''];
    }

    /**
     * @return array{success: bool, message: string}
     */
    private function attemptAdmin(string $username, string $password): array
    {
        if ($this->adminSecurity->isLocked($username)) {
            return [
                'success' => false,
                'message' => __('Too many failed attempts. Try again later.'),
            ];
        }

        $admin = Admin::query()->where('username', $username)->first();

        if (! $admin || ! $this->adminSecurity->verifyPassword($admin, $password)) {
            $this->adminSecurity->recordAttempt($username, false);

            return $this->failure();
        }

        $this->adminSecurity->recordAttempt($username, true);
        Auth::guard('admin')->login($admin);

        return ['success' => true, 'message' => ''];
    }

    /**
     * @return array{success: bool, message: string}
     */
    private function failure(): array
    {
        return [
            'success' => false,
            'message' => __('These credentials do not match our records.'),
        ];
    }
}
