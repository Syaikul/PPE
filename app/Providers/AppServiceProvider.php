<?php

namespace App\Providers;

use App\Services\GudangContext;
use Illuminate\Support\Facades\Blade;
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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Di lokal/ngrok, URL aset (CSS/JS) harus mengikuti host request,
        // bukan APP_URL=http://localhost — kalau tidak, CSS tidak kebawa via ngrok.
        if (! $this->app->runningInConsole()) {
            URL::forceRootUrl(request()->getSchemeAndHttpHost());
        }

        Blade::if('canView', fn (string $module) => auth()->user()?->canView($module));
        Blade::if('canCrud', fn (string $module) => auth()->user()?->canCrud($module));

        View::composer('layouts.kai', function ($view) {
            GudangContext::ensureNamagudangInSession();

            $view->with('documentTitle', GudangContext::titlePrefix().' Inventory');
        });
    }
}
