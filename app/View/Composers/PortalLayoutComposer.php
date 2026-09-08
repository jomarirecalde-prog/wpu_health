<?php

namespace App\View\Composers;

use Illuminate\View\View;

class PortalLayoutComposer
{
    public function compose(View $view): void
    {
        $base = url('/unified_portal/assets');
        $view->with('wpuAssets', $base);
        $view->with('wpuStyleCssV', @filemtime(base_path('unified_portal/assets/css/style.css')) ?: time());
        $view->with('wpuCoreCssV', @filemtime(base_path('unified_portal/assets/css/admin-core.css')) ?: time());
        $view->with('wpuPortalShellCssV', @filemtime(base_path('unified_portal/assets/css/portal-shell.css')) ?: time());
        $view->with('wpuCoreJsV', @filemtime(base_path('unified_portal/assets/js/admin-core.js')) ?: time());
        $view->with('wpuCalendarJsV', @filemtime(base_path('unified_portal/assets/js/wpu-calendar.js')) ?: time());
        $view->with('wpuFullCalendarJsV', @filemtime(base_path('unified_portal/assets/vendor/fullcalendar/index.global.min.js')) ?: time());
    }
}
