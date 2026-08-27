<?php

namespace App\Modules\Compliance\Providers;

use App\Modules\Compliance\Console\Commands\GenerateComplianceSnapshot;
use App\Modules\Compliance\Console\Commands\ImportControlMappings;
use App\Modules\Compliance\Console\Commands\RunMonitoringChecks;
use App\Modules\Compliance\Services\AuditFindingQueryService;
use App\Modules\Compliance\Services\AuditFindingService;
use App\Modules\Compliance\Services\ComplianceFindingService;
use App\Modules\Compliance\Services\ComplianceQueryService;
use App\Modules\Compliance\Services\ComplianceSnapshotService;
use App\Modules\Compliance\Services\ControlEvidenceService;
use App\Modules\Compliance\Services\ControlMonitorService;
use App\Modules\Compliance\Services\ControlTestService;
use App\Modules\Compliance\Services\MappingImportService;
use App\Modules\Compliance\Services\RemediationService;
use Illuminate\Support\ServiceProvider;

class ComplianceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../Config/compliance.php',
            'compliance'
        );

        $this->app->singleton(ControlTestService::class);
        $this->app->singleton(ControlMonitorService::class);
        $this->app->singleton(ComplianceFindingService::class);
        $this->app->singleton(RemediationService::class);
        $this->app->singleton(ComplianceSnapshotService::class);
        $this->app->singleton(MappingImportService::class);
        $this->app->singleton(ComplianceQueryService::class);
        $this->app->singleton(AuditFindingService::class);
        $this->app->singleton(ControlEvidenceService::class);
        $this->app->singleton(AuditFindingQueryService::class);
    }

    public function boot(): void
    {
        if (! config('compliance.enabled', true)) {
            return;
        }

        $this->loadMigrationsFrom(database_path('migrations'));
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                RunMonitoringChecks::class,
                GenerateComplianceSnapshot::class,
                ImportControlMappings::class,
            ]);
        }
    }
}
