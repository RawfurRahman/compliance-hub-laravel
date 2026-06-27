<?php

namespace App\Modules\RiskManagement\Console\Commands;

use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Services\FinancialExposureService;
use App\Modules\RiskManagement\Services\RemediationMetricsService;
use Illuminate\Console\Command;

/**
 * Scheduled snapshot of executive metrics (financial exposure + remediation
 * MTTR/SLA) so the dashboard can render trends without recomputation.
 */
class SnapshotExecutiveMetrics extends Command
{
    protected $signature = 'risks:snapshot-executive-metrics {--project-id=}';
    protected $description = 'Snapshot financial exposure and remediation (MTTR/SLA) metrics for trend reporting';

    public function handle(
        FinancialExposureService $exposure,
        RemediationMetricsService $remediation
    ): int {
        $projectIds = $this->option('project-id')
            ? [(int) $this->option('project-id')]
            : RiskRegister::distinct()->pluck('project_id')->filter()->values()->toArray();

        foreach ($projectIds as $projectId) {
            $exposure->snapshot($projectId);
            $remediation->snapshot($projectId, 'all');
            $remediation->snapshot($projectId, 'risk');
            $remediation->snapshot($projectId, 'control');

            $this->info("Executive metrics snapshot taken for project {$projectId}");
        }

        return Command::SUCCESS;
    }
}
