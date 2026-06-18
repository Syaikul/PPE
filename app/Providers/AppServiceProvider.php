<?php

namespace App\Providers;

use App\Services\GudangContext;
use Illuminate\Support\Facades\View;
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
        View::composer('layouts.kai', function ($view) {
            GudangContext::ensureNamagudangInSession();

            $view->with('documentTitle', GudangContext::titlePrefix().' Inventory');
        });
    }
}
