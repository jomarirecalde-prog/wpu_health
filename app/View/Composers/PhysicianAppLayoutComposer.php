<?php

namespace App\View\Composers;

use App\Models\Physician;
use Illuminate\View\View;

class PhysicianAppLayoutComposer
{
    public function compose(View $view): void
    {
        (new HisLayoutComposer)->compose($view);

        $physician = auth('physician')->user();
        $isDentist = $physician instanceof Physician && $physician->isDentist();

        $view->with('sidebarPartial', $isDentist
            ? 'partials.his-sidebar-dentist-provider'
            : 'partials.his-sidebar-physician');
        $view->with('topbarPartial', 'partials.his-topbar-app');
        $view->with('breadcrumbHomeUrl', route('physician.dashboard'));
        $view->with('breadcrumbHomeLabel', 'Dashboard');
        $view->with('providerPortalTitle', $isDentist ? 'Dentist Dashboard' : 'Physician Dashboard');
        $view->with('providerPortalSubtitle', $isDentist
            ? "Today's dental appointments and clinic schedule."
            : "Today's consultations and upcoming appointments.");
    }
}
