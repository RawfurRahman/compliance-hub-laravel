<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Gate for Admin role
        Gate::define('is-admin', function (User $user) {
            return $user->hasRole('Admin');
        });

        // Gate for Auditor role
        Gate::define('is-auditor', function (User $user) {
            return $user->hasRole('Auditor');
        });

        // Gate for Customer role
        Gate::define('is-customer', function (User $user) {
            return $user->hasRole('Customer');
        });
    }
}
