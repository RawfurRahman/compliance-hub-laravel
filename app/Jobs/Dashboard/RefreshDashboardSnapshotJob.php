<?php

namespace App\Jobs\Dashboard;

use App\DTOs\Dashboard\DashboardFilter;
use App\Services\Dashboard\BaseQueryService;
use App\Services\Dashboard\ComplianceScorecardQueryService;
use App\Services\Dashboard\DashboardCacheKey;
use App\Services\Dashboard\HeatmapQueryService;
use App\Services\Dashboard\KpiQueryService;
use App\Services\Dashboard\OwnershipAndSlaQueryService;
use App\Services\Dashboard\PolicyMetricQueryService;
use App\Services\Dashboard\RemediationTrendQueryService;
use App\Services\Dashboard\RiskRankingQueryService;
use App\Services\Dashboard\ThirdPartyRiskQueryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class RefreshDashboardSnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public string $domain,
        public ?string $businessUnit = null,
        public ?string $framework = null,
        public ?string $dateScope = 'daily',
        public ?int $projectId = null,
    ) {}

    public function handle(
        KpiQueryService $kpi,
        HeatmapQueryService $heatmap,
        RiskRankingQueryService $riskRanking,
        ComplianceScorecardQueryService $complianceScorecard,
        ThirdPartyRiskQueryService $thirdPartyRisk,
        PolicyMetricQueryService $policyMetrics,
        OwnershipAndSlaQueryService $ownershipSla,
        RemediationTrendQueryService $remediationTrend,
    ): void {
        $service = match ($this->domain) {
            'kpi' => $kpi,
            'heatmap' => $heatmap,
            'risk_ranking' => $riskRanking,
            'compliance_scorecard' => $complianceScorecard,
            'third_party_risk' => $thirdPartyRisk,
            'policy_metrics' => $policyMetrics,
            'ownership_sla' => $ownershipSla,
            'remediation_trend' => $remediationTrend,
            default => throw new \InvalidArgumentException("Unknown domain: {$this->domain}"),
        };

        $filter = new DashboardFilter(
            businessUnit: $this->businessUnit,
            framework: $this->framework,
            owner: null,
            category: null,
            vendor: null,
            dateFrom: null,
            dateTo: null,
            riskStatus: null,
            projectId: $this->projectId,
        );

        $snapshot = $service->snapshot($filter, $this->dateScope ?? 'daily');

        DashboardCacheKey::invalidateDomain($this->domain, $this->businessUnit, $this->framework);

        Log::info('Dashboard snapshot refreshed via job', [
            'domain' => $this->domain,
            'business_unit' => $this->businessUnit,
            'framework' => $this->framework,
            'date_scope' => $this->dateScope,
            'snapshot_id' => $snapshot->id,
            'project_id' => $this->projectId,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Dashboard snapshot refresh job permanently failed', [
            'domain' => $this->domain,
            'business_unit' => $this->businessUnit,
            'framework' => $this->framework,
            'error' => $e->getMessage(),
        ]);
    }
}
