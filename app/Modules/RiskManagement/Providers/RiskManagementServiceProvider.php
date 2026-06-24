<?php

namespace App\Modules\RiskManagement\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use App\Models\AssessmentFinding;
use App\Modules\RiskManagement\Events\ResidualAppetiteCrossed;
use App\Modules\RiskManagement\Listeners\ResidualAppetiteCrossedListener;
use App\Modules\RiskManagement\Observers\AssessmentFindingObserver;
use App\Modules\RiskManagement\Console\Commands\ImportWorkbookRisk;
use App\Modules\RiskManagement\Console\Commands\SeedWorkbookRisk;
use App\Modules\RiskManagement\Console\Commands\MigrateLegacyRisks;
use App\Modules\RiskManagement\Console\Commands\ImportControlMappings;
use App\Modules\RiskManagement\Console\Commands\ExportControlMappings;
use App\Modules\RiskManagement\Console\Commands\ProcessEvidenceAnalysis;
use App\Modules\RiskManagement\Console\Commands\SnapshotMaturityScores;
use App\Modules\RiskManagement\Console\Commands\ImportRiskRegisterFindings;
use App\Modules\RiskManagement\Console\Commands\SnapshotRiskExposures;
use App\Modules\RiskManagement\Console\Commands\ExpireRiskAcceptances;

class RiskManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/risk-management.php',
            'rmm'
        );

        $this->app->singleton(\App\Modules\RiskManagement\Services\RiskScenarioService::class);
        $this->app->singleton(\App\Modules\RiskManagement\Services\RiskTreatmentPlanService::class);
        $this->app->singleton(\App\Modules\RiskManagement\Services\RiskExposureService::class);
        $this->app->singleton(\App\Modules\RiskManagement\Services\RiskSnapshotService::class);
        $this->app->singleton(\App\Modules\RiskManagement\Services\ThirdPartyVendorService::class);
        $this->app->singleton(\App\Modules\RiskManagement\Services\VendorAssessmentService::class);
    }

    public function boot(): void
    {
        if (!config('rmm.enabled', true)) {
            return;
        }

        $this->loadMigrationsFrom(database_path('migrations'));
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        AssessmentFinding::observe(AssessmentFindingObserver::class);

        Event::listen(ResidualAppetiteCrossed::class, ResidualAppetiteCrossedListener::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportWorkbookRisk::class,
                SeedWorkbookRisk::class,
                MigrateLegacyRisks::class,
                ImportControlMappings::class,
                ExportControlMappings::class,
                ProcessEvidenceAnalysis::class,
                SnapshotMaturityScores::class,
                ImportRiskRegisterFindings::class,
                SnapshotRiskExposures::class,
                ExpireRiskAcceptances::class,
            ]);
        }
    }
}
