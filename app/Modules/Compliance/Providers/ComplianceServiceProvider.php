<?php

namespace App\Modules\Compliance\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Compliance\Console\Commands\RunMonitoringChecks;
use App\Modules\Compliance\Console\Commands\CheckSLABreaches;
use App\Modules\Compliance\Console\Commands\GenerateComplianceSnapshot;
use App\Modules\Compliance\Console\Commands\ImportControlMappings;

class ComplianceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/compliance.php',
            'compliance'
        );

        $this->app->singleton(\App\Modules\Compliance\Services\ControlTestService::class);
        $this->app->singleton(\App\Modules\Compliance\Services\ControlMonitorService::class);
        $this->app->singleton(\App\Modules\Compliance\Services\ComplianceFindingService::class);
        $this->app->singleton(\App\Modules\Compliance\Services\RemediationService::class);
        $this->app->singleton(\App\Modules\Compliance\Services\SLATrackerService::class);
        $this->app->singleton(\App\Modules\Compliance\Services\ComplianceSnapshotService::class);
        $this->app->singleton(\App\Modules\Compliance\Services\MappingImportService::class);
        $this->app->singleton(\App\Modules\Compliance\Services\ComplianceQueryService::class);
        $this->app->singleton(\App\Modules\Compliance\Services\AuditFindingService::class);
        $this->app->singleton(\App\Modules\Compliance\Services\ControlEvidenceService::class);
    }

    public function boot(): void
    {
        if (!config('compliance.enabled', true)) {
            return;
        }

        $this->loadMigrationsFrom(database_path('migrations'));
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                RunMonitoringChecks::class,
                CheckSLABreaches::class,
                GenerateComplianceSnapshot::class,
                ImportControlMappings::class,
            ]);
        }
    }
}
