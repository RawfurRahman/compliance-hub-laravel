<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Dashboard\DashboardFilter;
use App\Http\Controllers\Controller;
use App\Models\DashboardSnapshot;
use App\Modules\Compliance\Models\ComplianceTest;
use App\Modules\Compliance\Services\AuditFindingQueryService;
use App\Modules\Compliance\Services\RemediationMetricsService as ComplianceRemediationMetricsService;
use App\Modules\RiskManagement\Models\FinancialExposureMetric;
use App\Modules\RiskManagement\Resources\Dashboard\AuditFindingSummaryResource;
use App\Modules\RiskManagement\Resources\Dashboard\ComplianceScorecardResource;
use App\Modules\RiskManagement\Resources\Dashboard\ControlEffectivenessResource;
use App\Modules\RiskManagement\Resources\Dashboard\FinancialExposureTrendResource;
use App\Modules\RiskManagement\Resources\Dashboard\HeatmapCellResource;
use App\Modules\RiskManagement\Resources\Dashboard\InherentVsResidualResource;
use App\Modules\RiskManagement\Resources\Dashboard\IssueAgingResource;
use App\Modules\RiskManagement\Resources\Dashboard\IssuesRemediationTrendResource;
use App\Modules\RiskManagement\Resources\Dashboard\KpiResource;
use App\Modules\RiskManagement\Resources\Dashboard\RemediationTrendResource;
use App\Modules\RiskManagement\Resources\Dashboard\ThirdPartyRiskResource;
use App\Modules\RiskManagement\Resources\Dashboard\TopRiskResource;
use App\Modules\RiskManagement\Services\DashboardMetricsService;
use App\Modules\RiskManagement\Services\FinancialExposureService;
use App\Modules\RiskManagement\Services\IssueAgingService;
use App\Modules\RiskManagement\Services\RemediationMetricsService;
use App\Modules\RiskManagement\Services\ThirdPartyRiskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardApiController extends Controller
{
    private const HEAVY_CACHE_TTL = 300;

    public function __construct(
        private DashboardMetricsService $metrics,
        private AuditFindingQueryService $auditFindings,
        private IssueAgingService $issueAging,
        private ThirdPartyRiskService $thirdPartyRisk,
        private FinancialExposureService $financialExposure,
        private RemediationMetricsService $remediationMetrics,
        private ComplianceRemediationMetricsService $complianceRemediationMetrics,
    ) {}

    public function kpis(Request $request)
    {
        $filters = DashboardFilter::fromRequest($request);
        $data = Cache::remember(
            'dashboard:v1:kpis:'.$filters->cacheKey(),
            self::HEAVY_CACHE_TTL,
            fn () => $this->metrics->setFilters($filters->toLegacy())->kpis()
        );

        return new KpiResource($data);
    }

    public function heatmap(Request $request)
    {
        $filters = DashboardFilter::fromRequest($request);
        $data = Cache::remember(
            'dashboard:v1:heatmap:'.$filters->cacheKey(),
            self::HEAVY_CACHE_TTL,
            fn () => $this->metrics->setFilters($filters->toLegacy())->heatmap()
        );

        return HeatmapCellResource::collection($data);
    }

    public function topRisks(Request $request)
    {
        $filters = DashboardFilter::fromRequest($request);
        $limit = (int) $request->input('per_page', 20);
        $data = $this->metrics->setFilters($filters->toLegacy())->topRisks($limit);

        return TopRiskResource::collection($data);
    }

    public function inherentVsResidual(Request $request)
    {
        $filters = DashboardFilter::fromRequest($request);
        $data = $this->metrics->setFilters($filters->toLegacy())->inherentVsResidualByDept();

        return InherentVsResidualResource::collection($data);
    }

    public function controlEffectiveness(Request $request)
    {
        $filters = DashboardFilter::fromRequest($request);
        $data = $this->metrics->setFilters($filters->toLegacy())->controlEffectiveness();

        return new ControlEffectivenessResource($data);
    }

    public function complianceScorecard(Request $request)
    {
        $filters = DashboardFilter::fromRequest($request);
        $data = Cache::remember(
            'dashboard:v1:compliance-scorecard:'.$filters->cacheKey(),
            self::HEAVY_CACHE_TTL,
            fn () => $this->metrics->setFilters($filters->toLegacy())->complianceScorecard()
        );

        return ComplianceScorecardResource::collection($data);
    }

    public function auditFindingsSummary(Request $request)
    {
        $filters = DashboardFilter::fromRequest($request);
        $data = Cache::remember(
            'dashboard:v1:audit-findings:'.$filters->cacheKey(),
            self::HEAVY_CACHE_TTL,
            fn () => $this->auditFindings->summary($filters)
        );

        return new AuditFindingSummaryResource($data);
    }

    public function issuesAndRemediationTrends(Request $request)
    {
        $filters = DashboardFilter::fromRequest($request);
        $data = $this->auditFindings->trends($filters);

        return IssuesRemediationTrendResource::collection($data);
    }

    public function issueAging(Request $request)
    {
        $filters = DashboardFilter::fromRequest($request);
        $data = $this->issueAging->agingBuckets($filters);

        return new IssueAgingResource($data);
    }

    public function thirdPartyRiskSummary(Request $request)
    {
        $filters = DashboardFilter::fromRequest($request);
        $data = Cache::remember(
            'dashboard:v1:third-party-risk:'.$filters->cacheKey(),
            self::HEAVY_CACHE_TTL,
            fn () => $this->thirdPartyRisk->summary($filters)
        );

        return new ThirdPartyRiskResource($data);
    }

    public function getRemediationTrends(Request $request)
    {
        $filters = DashboardFilter::fromRequest($request);
        $data = $this->remediationMetrics->getTrendData(
            projectId: $filters->projectId,
            dateFrom: $filters->dateFrom,
            dateTo: $filters->dateTo,
        );

        return RemediationTrendResource::collection($data);
    }

    public function getFinancialExposureTrends(Request $request)
    {
        $filters = DashboardFilter::fromRequest($request);
        $data = $this->financialExposure->getTrendData(
            projectId: $filters->projectId,
            dateFrom: $filters->dateFrom,
            dateTo: $filters->dateTo,
        );

        return FinancialExposureTrendResource::collection($data);
    }

    public function getExposureMetrics(Request $request)
    {
        $filters = DashboardFilter::fromRequest($request);
        $data = $this->financialExposure->getSnapshot(
            projectId: $filters->projectId,
        );

        return response()->json($data);
    }

    public function getRemediationMetrics(Request $request)
    {
        $filters = DashboardFilter::fromRequest($request);
        $data = $this->complianceRemediationMetrics->getSnapshot(
            projectId: $filters->projectId,
            scope: $request->input('scope', 'all'),
        );

        return response()->json($data);
    }

    public function financialExposureSnapshot(Request $request)
    {
        $filters = DashboardFilter::fromRequest($request);
        $data = $this->financialExposure->forProject($filters->projectId);

        return response()->json([
            'portfolio_exposure' => $data['portfolio_exposure'] ?? 0,
            'total_risks' => $data['total_risks'] ?? 0,
            'avg_ale' => $data['avg_ale'] ?? 0,
            'remediation_cost' => $data['remediation_cost'] ?? 0,
        ]);
    }

    public function testsSummary(Request $request)
    {
        $filters = DashboardFilter::fromRequest($request);
        $query = ComplianceTest::query();

        if ($filters->framework) {
            $query->whereHas('frameworkLinks', fn ($q) => $q->whereHas('framework', fn ($f) => $f->where('name', $filters->framework)));
        }
        if ($filters->businessUnit) {
            $query->where('team', $filters->businessUnit);
        }
        if ($filters->projectId) {
            $query->whereHas('control', fn ($q) => $q->where('project_id', $filters->projectId));
        }

        $total = (clone $query)->count();
        $statusBreakdown = (clone $query)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $dueSoonCount = (clone $query)
            ->where('status', '!=', 'Passing')
            ->whereNotNull('next_due_at')
            ->where('next_due_at', '<=', now()->addDays(7))
            ->count();

        return response()->json([
            'total' => $total,
            'passing' => $statusBreakdown->get('Passing', 0),
            'overdue' => $statusBreakdown->get('Overdue', 0),
            'needs_remediation' => $statusBreakdown->get('Needs Remediation', 0),
            'due_soon' => $dueSoonCount,
            'not_yet_run' => $statusBreakdown->get('Not Yet Run', 0),
        ]);
    }

    public function metricHistory(Request $request)
    {
        $metricKeys = $request->input('metrics', []);
        $dateScope = $request->input('date_scope', 'daily');
        $thresholds = ['daily' => 6, 'weekly' => 4, 'monthly' => 3];
        $minPoints = $thresholds[$dateScope] ?? 6;

        $filters = DashboardFilter::fromRequest($request);

        $result = [];

        foreach ($metricKeys as $key) {
            $result[$key] = match ($key) {
                'residual_risk', 'risks_above_appetite' => $this->buildRiskRankingHistory($filters, $dateScope, $key, $minPoints),
                'financial_exposure' => $this->buildFinancialExposureHistory($filters, $minPoints),
                'control_effectiveness' => $this->buildKpiMetricHistory($filters, $dateScope, 'control_effectiveness', $minPoints),
                'compliance_readiness' => $this->buildComplianceScorecardHistory($filters, $dateScope, $minPoints),
                default => ['points' => [], 'enough_history' => false, 'required_points' => $minPoints],
            };
        }

        return response()->json($result);
    }

    private function buildRiskRankingHistory(DashboardFilter $filters, string $dateScope, string $metricKey, int $minPoints): array
    {
        $query = DashboardSnapshot::where('domain', 'risk_ranking')
            ->where('date_scope', $dateScope)
            ->orderBy('snapshot_date');

        if ($filters->businessUnit) {
            $query->where('business_unit', $filters->businessUnit);
        }

        $rows = $query->get(['snapshot_date', 'snapshot_data']);

        $grouped = $rows->groupBy(fn ($r) => $r->snapshot_date->format('Y-m-d'));
        $points = [];

        foreach ($grouped as $date => $group) {
            $dateValues = [];
            foreach ($group as $row) {
                $rankings = $row->snapshot_data['rankings'] ?? [];
                if ($metricKey === 'residual_risk') {
                    $scores = array_column($rankings, 'risk_score');
                    $dateValues[] = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
                } else {
                    $highCritical = count(array_filter($rankings, fn ($r) => in_array($r['risk'] ?? '', ['High', 'Critical'])));
                    $total = count($rankings);
                    $dateValues[] = $total > 0 ? round(($highCritical / $total) * 100, 1) : 0;
                }
            }
            $points[] = ['date' => $date, 'value' => round(array_sum($dateValues) / count($dateValues), 1)];
        }

        return [
            'points' => $points,
            'enough_history' => count($points) >= $minPoints,
            'required_points' => $minPoints,
        ];
    }

    private function buildFinancialExposureHistory(DashboardFilter $filters, int $minPoints): array
    {
        $query = FinancialExposureMetric::where('scope', 'portfolio')
            ->orderBy('calculated_at');

        if ($filters->projectId) {
            $query->where('project_id', $filters->projectId);
        }

        $rows = $query->get(['calculated_at', 'portfolio_exposure']);

        $grouped = $rows->groupBy(fn ($r) => $r->calculated_at->format('Y-m-d'));
        $points = [];

        foreach ($grouped as $date => $group) {
            $value = $group->avg('portfolio_exposure');
            $points[] = ['date' => $date, 'value' => round($value, 2)];
        }

        return [
            'points' => $points,
            'enough_history' => count($points) >= $minPoints,
            'required_points' => $minPoints,
        ];
    }

    private function buildKpiMetricHistory(DashboardFilter $filters, string $dateScope, string $metricKey, int $minPoints): array
    {
        $query = DashboardSnapshot::where('domain', 'kpi')
            ->where('date_scope', $dateScope)
            ->orderBy('snapshot_date');

        if ($filters->businessUnit) {
            $query->where('business_unit', $filters->businessUnit);
        }

        $rows = $query->get(['snapshot_date', 'snapshot_data']);

        $grouped = $rows->groupBy(fn ($r) => $r->snapshot_date->format('Y-m-d'));
        $points = [];

        foreach ($grouped as $date => $group) {
            $dateValues = [];
            foreach ($group as $row) {
                $data = $row->snapshot_data;
                if ($metricKey === 'control_effectiveness') {
                    $total = $data['total_controls'] ?? 0;
                    $dateValues[] = $total > 0 ? round(($data['compliant'] / $total) * 100, 1) : 0;
                }
            }
            $points[] = ['date' => $date, 'value' => round(array_sum($dateValues) / count($dateValues), 1)];
        }

        return [
            'points' => $points,
            'enough_history' => count($points) >= $minPoints,
            'required_points' => $minPoints,
        ];
    }

    private function buildComplianceScorecardHistory(DashboardFilter $filters, string $dateScope, int $minPoints): array
    {
        $query = DashboardSnapshot::where('domain', 'compliance_scorecard')
            ->where('date_scope', $dateScope)
            ->orderBy('snapshot_date');

        if ($filters->businessUnit) {
            $query->where('business_unit', $filters->businessUnit);
        }

        $rows = $query->get(['snapshot_date', 'snapshot_data']);

        $grouped = $rows->groupBy(fn ($r) => $r->snapshot_date->format('Y-m-d'));
        $points = [];

        foreach ($grouped as $date => $group) {
            $dateValues = [];
            foreach ($group as $row) {
                $scorecard = $row->snapshot_data['scorecard'] ?? [];
                $percentages = array_column($scorecard, 'percentage');
                $dateValues[] = count($percentages) > 0 ? array_sum($percentages) / count($percentages) : 0;
            }
            $points[] = ['date' => $date, 'value' => round(array_sum($dateValues) / count($dateValues), 1)];
        }

        return [
            'points' => $points,
            'enough_history' => count($points) >= $minPoints,
            'required_points' => $minPoints,
        ];
    }

    public function user(Request $request)
    {
        $user = $request->user()->load('roles');
        $userData = [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'roles' => $user->roles->map(function ($role) {
                return ['name' => $role->name];
            }),
        ];

        return response()->json(['user' => $userData]);
    }
}
