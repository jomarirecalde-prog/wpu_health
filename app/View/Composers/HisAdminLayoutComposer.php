<?php

namespace App\View\Composers;

use App\Models\Admin;
use App\Services\ClinicalWorkspaceAccessService;
use App\Services\HealthHubRbacService;
use Illuminate\View\View;

class HisAdminLayoutComposer
{
    public function __construct(
        private readonly HealthHubRbacService $rbac,
        private readonly ClinicalWorkspaceAccessService $workspace,
    ) {
    }

    public function compose(View $view): void
    {
        (new HisLayoutComposer)->compose($view);

        $admin = auth('admin')->user();
        $clinicalRole = $admin instanceof Admin ? $this->rbac->resolveRole($admin) : 'guest';

        $view->with('clinicalRole', $clinicalRole);
        $view->with('sidebarPartial', $this->workspace->sidebarPartialForRole($clinicalRole));
        $view->with('topbarPartial', 'partials.his-topbar');
        $view->with('workspaceUrl', route('admin.workspace', ['path' => 'admin/admin.php']));
        $view->with('breadcrumbHomeUrl', $this->workspace->workspaceUrlForRole($clinicalRole));
        $view->with('breadcrumbHomeLabel', __('Dashboard'));
    }
}
