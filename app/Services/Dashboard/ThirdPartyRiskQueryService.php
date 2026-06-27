<?php

namespace App\Services\Dashboard;

use App\DTOs\Dashboard\DashboardFilter;
use App\Modules\RiskManagement\Models\ThirdPartyVendor;
use App\Modules\RiskManagement\Models\VendorAssessment;

class ThirdPartyRiskQueryService extends BaseQueryService
{
    protected function domain(): string
    {
        return DashboardDomains::THIRD_PARTY_RISK;
    }

    protected function compute(DashboardFilter $filter): array
    {
        $vendorQuery = ThirdPartyVendor::query();
        $assessmentQuery = VendorAssessment::query();

        if ($filter->projectId) {
            $vendorQuery->where('project_id', $filter->projectId);
            $assessmentQuery->whereHas('vendor', fn ($q) => $q->where('project_id', $filter->projectId));
        }
        if ($filter->vendor) {
            $vendorQuery->where('vendor_name', 'LIKE', "%{$filter->vendor}%");
            $assessmentQuery->whereHas('vendor', fn ($q) => $q->where('vendor_name', 'LIKE', "%{$filter->vendor}%"));
        }
        if ($filter->category) {
            $vendorQuery->where('service_category', $filter->category);
            $assessmentQuery->whereHas('vendor', fn ($q) => $q->where('service_category', $filter->category));
        }

        $totalVendors = (clone $vendorQuery)->count();
        $criticalVendors = (clone $vendorQuery)->critical()->count();
        $activeVendors = (clone $vendorQuery)->active()->count();

        $riskTierBreakdown = (clone $vendorQuery)
            ->selectRaw('risk_tier, COUNT(*) as aggregate')
            ->groupBy('risk_tier')
            ->pluck('aggregate', 'risk_tier');

        $totalAssessments = (clone $assessmentQuery)->count();
        $overdueAssessments = (clone $assessmentQuery)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->where('status', '!=', 'completed')
            ->count();

        $recentAssessments = (clone $assessmentQuery)
            ->where('status', 'completed')
            ->whereDate('completed_date', '>=', now()->subDays(90))
            ->count();

        return [
            'total_vendors' => $totalVendors,
            'critical_vendors' => $criticalVendors,
            'active_vendors' => $activeVendors,
            'risk_tier_breakdown' => [
                'critical' => (int) $riskTierBreakdown->get('critical', 0),
                'high' => (int) $riskTierBreakdown->get('high', 0),
                'medium' => (int) $riskTierBreakdown->get('medium', 0),
                'low' => (int) $riskTierBreakdown->get('low', 0),
            ],
            'assessments' => [
                'total' => $totalAssessments,
                'completed_recently' => $recentAssessments,
                'overdue' => $overdueAssessments,
            ],
            'vendor_risk_coverage_pct' => $totalVendors > 0
                ? round($recentAssessments / $totalVendors * 100, 1)
                : 0.0,
        ];
    }
}
