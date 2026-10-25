<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Services\ClinicalWorkspaceAccessService;
use App\Services\HealthHubRbacService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClinicalRouteAccess
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
        $admin = $request->user('admin');
        if (! $admin instanceof Admin) {
            abort(403);
        }

        $role = $this->rbac->resolveRole($admin);
        $routeName = $request->route()?->getName();

        if (! $this->workspace->canAccessCalendarRoute($role, $routeName)) {
            abort(403, __('You do not have permission to access this area.'));
        }

        return $next($request);
    }
}
