<?php

namespace App\Providers\Dashboard;

use App\Console\Commands\InvalidateDashboardCache;
use App\Console\Commands\RefreshDashboardSnapshots;
use App\Services\Dashboard\ComplianceScorecardQueryService;
use App\Services\Dashboard\HeatmapQueryService;
use App\Services\Dashboard\KpiQueryService;
use App\Services\Dashboard\OwnershipAndSlaQueryService;
use App\Services\Dashboard\PolicyMetricQueryService;
use App\Services\Dashboard\RemediationTrendQueryService;
use App\Services\Dashboard\RiskRankingQueryService;
use App\Services\Dashboard\ThirdPartyRiskQueryService;
use Illuminate\Support\ServiceProvider;

class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(KpiQueryService::class);
        $this->app->singleton(HeatmapQueryService::class);
        $this->app->singleton(RiskRankingQueryService::class);
        $this->app->singleton(ComplianceScorecardQueryService::class);
        $this->app->singleton(ThirdPartyRiskQueryService::class);
        $this->app->singleton(PolicyMetricQueryService::class);
        $this->app->singleton(OwnershipAndSlaQueryService::class);
        $this->app->singleton(RemediationTrendQueryService::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                RefreshDashboardSnapshots::class,
                InvalidateDashboardCache::class,
            ]);
        }
    }
}
