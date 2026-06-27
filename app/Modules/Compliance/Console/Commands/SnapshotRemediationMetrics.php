<?php

namespace App\Modules\Compliance\Console\Commands;

use App\Modules\Compliance\Services\RemediationMetricsService;
use Illuminate\Console\Command;

/**
 * Snapshot remediation metrics for trend reporting.
 */
class SnapshotRemediationMetrics extends Command
{
    protected $signature = 'risks:snapshot-remediation-metrics {--project-id=}';
    protected $description = 'Snapshot remediation (MTTR/SLA) metrics for trend reporting';

    public function handle(RemediationMetricsService $remediation): int
    {
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