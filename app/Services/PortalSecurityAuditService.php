<?php

namespace App\Services;

use App\Models\PortalUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PortalSecurityAuditService
{
    public function log(PortalUser $user, string $action, string $details = '', ?string $actorUsername = null): void
    {
        $username = mb_substr($actorUsername ?: $user->email, 0, 50);
        $ip = mb_substr(request()->ip() ?? '', 0, 45);
        $userAgent = mb_substr((string) request()->userAgent(), 0, 255);

        if (Schema::hasTable('security_audit_log')) {
            DB::table('security_audit_log')->insert([
                'username' => $username,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'action' => mb_substr($action, 0, 100),
                'details' => mb_substr($details, 0, 500),
                'created_at' => now(),
            ]);
        }

        if (Schema::hasTable('activity_logs')) {
            DB::table('activity_logs')->insert([
                'username' => $username,
                'action' => mb_substr($action, 0, 255),
                'details' => $details !== '' ? $details : null,
                'created_at' => now(),
            ]);
        }
    }

    public function logAdmin(PortalUser $patient, string $action, string $details = ''): void
    {
        $admin = auth('admin')->user();
        $actor = $admin?->username ?? 'admin';
        $patientRef = 'Patient #'.$patient->id.' ('.$patient->email.')';
        $fullDetails = $details !== '' ? $patientRef.' — '.$details : $patientRef;

        $this->log($patient, $action, $fullDetails, $actor);
    }
}
