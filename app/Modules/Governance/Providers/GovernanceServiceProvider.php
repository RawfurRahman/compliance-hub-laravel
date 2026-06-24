<?php

namespace App\Modules\Governance\Providers;

use App\Modules\Governance\Console\Commands\SendReviewReminders;
use App\Modules\Governance\Console\Commands\ExpireOverdueWaivers;
use App\Modules\Governance\Policies\PolicyAccessPolicy;
use App\Modules\Governance\Policies\DomainAccessPolicy;
use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\Domain;
use App\Modules\Governance\Services\PolicyService;
use App\Modules\Governance\Services\PolicyVersionService;
use App\Modules\Governance\Services\PolicyReviewService;
use App\Modules\Governance\Services\PolicyApprovalService;
use App\Modules\Governance\Services\PolicyExceptionService;
use App\Modules\Governance\Services\PolicyWaiverService;
use App\Modules\Governance\Services\OwnershipService;
use App\Modules\Governance\Services\SLARuleService;
use App\Modules\Governance\Services\GovernanceDashboardService;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Illuminate\Support\ServiceProvider;

class GovernanceServiceProvider extends ServiceProvider
{
    protected array $policies = [
        Policy::class => PolicyAccessPolicy::class,
        Domain::class => DomainAccessPolicy::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/governance.php',
            'governance'
        );

        $this->app->singleton(PolicyService::class);
        $this->app->singleton(PolicyVersionService::class);
        $this->app->singleton(PolicyReviewService::class);
        $this->app->singleton(PolicyApprovalService::class);
        $this->app->singleton(PolicyExceptionService::class);
        $this->app->singleton(PolicyWaiverService::class);
        $this->app->singleton(OwnershipService::class);
        $this->app->singleton(SLARuleService::class);
        $this->app->singleton(GovernanceDashboardService::class);
    }

    public function boot(): void
    {
        if (!config('governance.enabled', true)) {
            return;
        }

        $this->loadMigrationsFrom(database_path('migrations'));
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        $this->registerPolicies();

        if ($this->app->runningInConsole()) {
            $this->commands([
                SendReviewReminders::class,
                ExpireOverdueWaivers::class,
            ]);
        }
    }

    protected function registerPolicies(): void
    {
        foreach ($this->policies as $model => $policy) {
            \Illuminate\Support\Facades\Gate::policy($model, $policy);
        }

        \Illuminate\Support\Facades\Gate::define('waive', function ($user) {
            return $user->hasRole('Admin');
        });
    }
}
