<?php

namespace App\Console\Commands;

use App\Modules\Compliance\Services\RemediationMetricsService;
use App\Modules\RiskManagement\Services\FinancialExposureService;
use Illuminate\Console\Command;

/**
 * Snapshot financial exposure metrics for trend reporting.
 */
class SnapshotFinancialMetrics extends Command
{
    protected $signature = 'risks:snapshot-financial-metrics {--project-id=}' ;
    protected $description = 'Snapshot financial exposure (ALE) metrics for trend reporting';

    public function handle(
        FinancialExposureService $exposure,
    ): int {
        $projectIds = $this->option('project-id')
            ? [(int) $this->option('project-id')]
            : [];

        foreach ($projectIds as $projectId) {
            $exposure->snapshot($projectId);
            $this->info("Financial exposure snapshot taken for project {$projectId}");
        }

        return Command::SUCCESS;
    }
}

class SnapshotRemediationMetrics extends Command
{
    protected $signature = 'risks:snapshot-remediation-metrics {--project-id=}' ;
    protected $description = 'Snapshot remediation (MTTR/SLA) metrics for trend reporting';

    public function handle(
        RemediationMetricsService $remediation,
    ): int {
        $projectIds = $this->option('project-id')
            ? [(int) $this->option('project-id')]
            : [];

        foreach ($projectIds as $projectId) {
            $remediation->snapshot($projectId, 'all');
            $remediation->snapshot($projectId, 'risk');
            $remediation->snapshot($projectId, 'control');
            $this->info("Remediation snapshot taken for project {$projectId}");
        }

        return Command::SUCCESS;
    }
}