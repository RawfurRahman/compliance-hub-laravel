<?php

namespace App\Modules\RiskManagement\Providers;

use App\Models\AssessmentFinding;
use App\Modules\RiskManagement\Console\Commands\ExpireRiskAcceptances;
use App\Modules\RiskManagement\Console\Commands\ExportControlMappings;
use App\Modules\RiskManagement\Console\Commands\ImportControlMappings;
use App\Modules\RiskManagement\Console\Commands\ImportRiskRegisterFindings;
use App\Modules\RiskManagement\Console\Commands\ImportWorkbookRisk;
use App\Modules\RiskManagement\Console\Commands\MigrateLegacyRisks;
use App\Modules\RiskManagement\Console\Commands\ProcessEvidenceAnalysis;
use App\Modules\RiskManagement\Console\Commands\SeedWorkbookRisk;
use App\Modules\RiskManagement\Console\Commands\SnapshotExecutiveMetrics;
use App\Modules\RiskManagement\Console\Commands\SnapshotMaturityScores;
use App\Modules\RiskManagement\Console\Commands\SnapshotRiskExposures;
use App\Modules\RiskManagement\Events\ResidualAppetiteCrossed;
use App\Modules\RiskManagement\Events\VendorAssessmentCompleted;
use App\Modules\RiskManagement\Listeners\ResidualAppetiteCrossedListener;
use App\Modules\RiskManagement\Listeners\RunVendorAiSummaryListener;
use App\Modules\RiskManagement\Observers\AssessmentFindingObserver;
use App\Modules\RiskManagement\Services\FinancialExposureService;
use App\Modules\RiskManagement\Services\IssueAgingService;
use App\Modules\RiskManagement\Services\RemediationMetricsService;
use App\Modules\RiskManagement\Services\RiskExposureService;
use App\Modules\RiskManagement\Services\RiskScenarioService;
use App\Modules\RiskManagement\Services\RiskSnapshotService;
use App\Modules\RiskManagement\Services\RiskTreatmentPlanService;
use App\Modules\RiskManagement\Services\ThirdPartyRiskService;
use App\Modules\RiskManagement\Services\ThirdPartyVendorService;
use App\Modules\RiskManagement\Services\VendorAssessmentService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class RiskManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../Config/risk-management.php',
            'rmm'
        );

        $this->app->singleton(RiskScenarioService::class);
        $this->app->singleton(RiskTreatmentPlanService::class);
        $this->app->singleton(RiskExposureService::class);
        $this->app->singleton(RiskSnapshotService::class);
        $this->app->singleton(ThirdPartyVendorService::class);
        $this->app->singleton(VendorAssessmentService::class);
        $this->app->singleton(FinancialExposureService::class);
        $this->app->singleton(RemediationMetricsService::class);
        $this->app->singleton(IssueAgingService::class);
        $this->app->singleton(ThirdPartyRiskService::class);
    }

    public function boot(): void
    {
        if (! config('rmm.enabled', true)) {
            return;
        }

        $this->loadMigrationsFrom(database_path('migrations'));
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');

        AssessmentFinding::observe(AssessmentFindingObserver::class);

        Event::listen(ResidualAppetiteCrossed::class, ResidualAppetiteCrossedListener::class);
        Event::listen(VendorAssessmentCompleted::class, RunVendorAiSummaryListener::class);

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
                SnapshotExecutiveMetrics::class,
                ExpireRiskAcceptances::class,
            ]);
        }
    }
}
