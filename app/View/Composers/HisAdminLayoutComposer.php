<?php

namespace App\View\Composers;

use Illuminate\View\View;

class HisAdminLayoutComposer
{
    public function compose(View $view): void
    {
        (new HisLayoutComposer())->compose($view);
        $view->with('sidebarPartial', 'partials.his-sidebar');
        $view->with('topbarPartial', 'partials.his-topbar');
        $view->with('breadcrumbHomeUrl', route('admin.workspace', ['path' => 'admin/admin.php']));
        $view->with('breadcrumbHomeLabel', 'Dashboard');
    }
}
