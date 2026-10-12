<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
        // One pagination bar for every list: "Showing x–y of z" and page
        // numbers at phone width (see resources/views/pagination/gtrack).
        Paginator::defaultView('pagination.gtrack');
    }
}
