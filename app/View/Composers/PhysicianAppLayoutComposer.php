<?php

namespace App\View\Composers;

use Illuminate\View\View;

class PhysicianAppLayoutComposer
{
    public function compose(View $view): void
    {
        (new HisLayoutComposer())->compose($view);
        $view->with('sidebarPartial', 'partials.his-sidebar-physician');
        $view->with('topbarPartial', 'partials.his-topbar-app');
        $view->with('breadcrumbHomeUrl', route('physician.dashboard'));
        $view->with('breadcrumbHomeLabel', 'Dashboard');
    }
}
