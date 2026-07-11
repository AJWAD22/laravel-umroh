<?php

namespace App\Providers;

use App\Models\PilgrimLocation;
use App\Observers\PilgrimLocationObserver;
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
        PilgrimLocation::observe(PilgrimLocationObserver::class);
    }
}
