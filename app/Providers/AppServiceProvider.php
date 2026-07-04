<?php

namespace App\Providers;

use App\Models\PilgrimLocation;
use App\Models\SosReport;
use App\Observers\PilgrimLocationObserver;
use App\Observers\SosReportObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        SosReport::observe(SosReportObserver::class);
        PilgrimLocation::observe(PilgrimLocationObserver::class);
    }
}
