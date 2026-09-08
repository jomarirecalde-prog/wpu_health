<?php

namespace App\View\Composers;

use Illuminate\View\View;

class PortalAppLayoutComposer
{
    public function compose(View $view): void
    {
        (new HisLayoutComposer())->compose($view);
        $view->with('sidebarPartial', 'partials.his-sidebar-portal');
        $view->with('topbarPartial', 'partials.his-topbar-app');
        $view->with('breadcrumbHomeUrl', route('portal.dashboard'));
        $view->with('breadcrumbHomeLabel', 'Dashboard');
    }
}
