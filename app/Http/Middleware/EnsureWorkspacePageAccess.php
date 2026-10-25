<?php

namespace App\Http\Middleware;

use App\Services\ClinicalWorkspaceAccessService;
use App\Services\HealthHubRbacService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspacePageAccess
{
    public function __construct(
        private readonly HealthHubRbacService $rbac,
        private readonly ClinicalWorkspaceAccessService $workspace,
    ) {
    }

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user('admin') && ! $request->user('physician')) {
            abort(403);
        }

        $role = $this->rbac->resolveAuthenticatedClinicalRole();
        if ($role === 'guest') {
            abort(403);
        }

        if ($request->user('physician') && ! $request->user('admin') && $role !== 'dentist') {
            abort(403, __('You do not have permission to access this module.'));
        }
        $page = (string) $request->query('page', 'dashboard');
        $normalized = $this->workspace->normalizePage($page);

        if (! $this->workspace->canAccessPage($role, $normalized)) {
            abort(403, __('You do not have permission to access this module.'));
        }

        if ($normalized === 'health_dental_records' && $request->query->has('records_tab')) {
            $tab = strtolower((string) $request->query('records_tab'));
            if ($tab !== '' && ! $this->workspace->canAccessRecordsTab($role, $tab)) {
                abort(403, __('You do not have permission to access this records module.'));
            }
        }

        return $next($request);
    }
}
