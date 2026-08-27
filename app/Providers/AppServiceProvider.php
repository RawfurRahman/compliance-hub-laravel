<?php

namespace App\Providers;

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
        // Fallback for views rendered outside an HTTP request (e.g. emails),
        // where the SecurityHeaders middleware does not run to share a nonce.
        View::share('cspNonce', '');
    }
}
