<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Physician;
use Illuminate\Support\Facades\Auth;

class HealthHubRbacService
{
    public function resolveAuthenticatedClinicalRole(): string
    {
        $admin = Auth::guard('admin')->user();
        if ($admin instanceof Admin) {
            return $this->resolveRole($admin);
        }

        $physician = Auth::guard('physician')->user();
        if ($physician instanceof Physician) {
            return $physician->clinicalPortalRole();
        }

        return 'guest';
    }

    public function resolveRole(?Admin $admin): string
    {
        if ($admin === null) {
            return 'guest';
        }

        if ($admin->isSuperAdmin()) {
            return 'super_admin';
        }

        $role = strtolower(trim((string) ($admin->role ?? 'admin')));

        return in_array($role, config('healthhub.clinical_roles', []), true) ? $role : 'admin';
    }

    public function canAccessDentalRecords(string $role): bool
    {
        return in_array($role, ['super_admin', 'admin', 'physician', 'dentist'], true);
    }

    public function canAccessMedicalRecords(string $role): bool
    {
        return in_array($role, ['super_admin', 'admin', 'physician', 'nurse', 'staff'], true);
    }

    public function canViewMedicalSoda(string $role): bool
    {
        return in_array($role, ['super_admin', 'admin', 'physician'], true);
    }

    public function canEditMedicalSoda(string $role): bool
    {
        return in_array($role, ['super_admin', 'admin', 'physician'], true);
    }

    public function canManageNurseNotes(string $role): bool
    {
        return in_array($role, ['super_admin', 'admin', 'physician', 'nurse'], true);
    }

    public function canViewClinicalDiagnosis(string $role): bool
    {
        return in_array($role, ['super_admin', 'admin', 'physician', 'nurse'], true);
    }

    public function canViewBasicPatientInfo(string $role): bool
    {
        return in_array($role, ['super_admin', 'admin', 'physician', 'dentist', 'nurse', 'staff'], true);
    }

    public function canIssueCertificates(string $role): bool
    {
        return in_array($role, ['super_admin', 'admin', 'physician'], true);
    }

    public function canManageDispensary(string $role): bool
    {
        return in_array($role, ['super_admin', 'admin', 'physician', 'nurse', 'staff'], true);
    }

    /**
     * Strip restricted medical fields for API/view responses.
     *
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    public function filterMedicalRecordForRole(array $record, string $role): array
    {
        if ($this->canViewMedicalSoda($role)) {
            return $record;
        }

        foreach (['subjective', 'objectives', 'assessment'] as $field) {
            if (array_key_exists($field, $record)) {
                $record[$field] = null;
            }
        }

        if (! $this->canViewClinicalDiagnosis($role)) {
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
}
