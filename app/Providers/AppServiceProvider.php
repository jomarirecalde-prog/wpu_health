<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\DashboardStatsService::class);
        $this->app->singleton(\App\Services\CacheInvalidationService::class);
        $this->app->singleton(\App\Services\AuthRoleService::class);
        $this->app->singleton(\App\Services\UnifiedAuthService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($root = config('app.url')) {
            URL::forceRootUrl(rtrim($root, '/'));
            if (str_starts_with($root, 'https://')) {
                URL::forceScheme('https');
            }
        }

        if (config('app.debug') && config('database.default') === 'mysql') {
            DB::listen(function ($query) {
                if ($query->time > 500) {
                    logger()->warning('Slow query detected', [
                        'sql' => $query->sql,
                        'time_ms' => $query->time,
                    ]);
                }
            });
        }

        View::composer('layouts.his-app', \App\View\Composers\HisLayoutComposer::class);
        View::composer(['layouts.his-admin', 'admin.calendar.*'], \App\View\Composers\HisAdminLayoutComposer::class);
        View::composer(['layouts.portal-app', 'portal.*'], \App\View\Composers\PortalAppLayoutComposer::class);
        View::composer(['layouts.physician-app', 'physician.*'], \App\View\Composers\PhysicianAppLayoutComposer::class);
        View::composer(['layouts.portal-public', 'portal.home', 'portal.auth.*'], \App\View\Composers\PortalLayoutComposer::class);
    }
}
