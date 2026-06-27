<?php

namespace App\Modules\TrustCenter\Providers;

use Illuminate\Support\ServiceProvider;

class TrustCenterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/trust-center.php',
            'trust-center'
        );
    }

    public function boot(): void
    {
        if (!config('trust-center.enabled', true)) {
            return;
        }

        $this->loadMigrationsFrom(database_path('migrations'));
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
    }
}
