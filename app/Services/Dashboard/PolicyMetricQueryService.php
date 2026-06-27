<?php

namespace App\Services\Dashboard;

use App\DTOs\Dashboard\DashboardFilter;
use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyApproval;
use App\Modules\Governance\Models\PolicyReview;

class PolicyMetricQueryService extends BaseQueryService
{
    protected function domain(): string
    {
        return DashboardDomains::POLICY_METRICS;
    }

    protected function compute(DashboardFilter $filter): array
    {
        $query = Policy::query();

        if ($filter->businessUnit) {
            $query->where('business_unit', $filter->businessUnit);
        }

        $total = (clone $query)->count();
        $byStatus = (clone $query)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $overdueReviews = (clone $query)
            ->whereHas('reviews', fn ($q) => $q
                ->whereIn('status', ['pending', 'in_progress'])
                ->where('due_date', '<', now()))
            ->count();

        $pendingApprovals = PolicyApproval::query()
            ->where('status', 'pending')
            ->when($filter->businessUnit, fn ($q) => $q->whereHas('policy', fn ($q2) => $q2->where('business_unit', $filter->businessUnit)))
            ->count();

        return [
            'total_policies' => $total,
            'by_status' => [
                'draft' => (int) $byStatus->get('draft', 0),
                'under_review' => (int) $byStatus->get('under_review', 0),
                'approved' => (int) $byStatus->get('approved', 0),
                'published' => (int) $byStatus->get('published', 0),
                'deprecated' => (int) $byStatus->get('deprecated', 0),
                'archived' => (int) $byStatus->get('archived', 0),
                'expired' => (int) $byStatus->get('expired', 0),
            ],
            'active_policies' => (int) (clone $query)->where('is_active', true)->count(),
            'overdue_reviews' => $overdueReviews,
            'pending_approvals' => $pendingApprovals,
        ];
    }
}
