<?php

namespace App\View\Composers;

use Illuminate\View\View;

class HisLayoutComposer
{
    public function compose(View $view): void
    {
        $base = url('/unified_portal/assets');
        $view->with('wpuAssets', $base);
        $view->with('wpuCoreCssV', @filemtime(base_path('unified_portal/assets/css/admin-core.css')) ?: time());
        $view->with('wpuHisCssV', @filemtime(base_path('unified_portal/assets/css/admin-his.css')) ?: time());
        $view->with('wpuCoreJsV', @filemtime(base_path('unified_portal/assets/js/admin-core.js')) ?: time());
        $view->with('wpuHisJsV', @filemtime(base_path('unified_portal/assets/js/admin-his-ui.js')) ?: time());
        $view->with('wpuAjaxJsV', @filemtime(base_path('unified_portal/assets/js/wpu-ajax.js')) ?: time());
        $view->with('wpuCalendarCssV', @filemtime(base_path('unified_portal/assets/css/admin-calendar.css')) ?: time());
        $view->with('wpuCalendarJsV', @filemtime(base_path('unified_portal/assets/js/wpu-calendar.js')) ?: time());
        $view->with('wpuFullCalendarJsV', @filemtime(base_path('unified_portal/assets/vendor/fullcalendar/index.global.min.js')) ?: time());
    }
}
