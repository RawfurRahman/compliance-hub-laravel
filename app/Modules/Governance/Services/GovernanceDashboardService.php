<?php

namespace App\Modules\Governance\Services;

use App\Modules\Governance\Models\Domain;
use App\Modules\Governance\Models\GovernanceMetricSnapshot;
use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyReview;
use App\Modules\Governance\Models\PolicyApproval;
use App\Modules\Governance\Models\PolicyWaiver;
use App\Modules\Governance\Models\PolicyException;

class GovernanceDashboardService
{
    public function aggregateMetrics(?int $projectId = null): array
    {
        $policyQuery = Policy::query();
        $reviewQuery = PolicyReview::query();
        $approvalQuery = PolicyApproval::query();
        $waiverQuery = PolicyWaiver::query();
        $exceptionQuery = PolicyException::query();

        return [
            'total_policies' => (clone $policyQuery)->count(),
            'published_policies' => (clone $policyQuery)->where('status', 'published')->count(),
            'draft_policies' => (clone $policyQuery)->where('status', 'draft')->count(),
            'under_review_policies' => (clone $policyQuery)->where('status', 'under_review')->count(),
            'approved_policies' => (clone $policyQuery)->where('status', 'approved')->count(),
            'deprecated_policies' => (clone $policyQuery)->where('status', 'deprecated')->count(),
            'archived_policies' => (clone $policyQuery)->where('status', 'archived')->count(),
            'expired_policies' => (clone $policyQuery)->where('status', 'expired')->count(),
            'overdue_reviews' => (clone $reviewQuery)
                ->whereIn('status', ['pending', 'in_progress'])
                ->where('due_date', '<', now())
                ->count(),
            'pending_approvals' => (clone $approvalQuery)->where('status', 'pending')->count(),
            'active_waivers' => (clone $waiverQuery)->where('status', 'approved')->count(),
            'pending_waivers' => (clone $waiverQuery)->where('status', 'pending')->count(),
            'active_exceptions' => (clone $exceptionQuery)->where('status', 'approved')->count(),
            'pending_exceptions' => (clone $exceptionQuery)->where('status', 'pending')->count(),
        ];
    }

    public function snapshot(?int $projectId = null): GovernanceMetricSnapshot
    {
        $metrics = $this->aggregateMetrics($projectId);

        return GovernanceMetricSnapshot::create(array_merge($metrics, [
            'project_id' => $projectId,
            'snapshot_type' => 'overall',
            'snapped_at' => now(),
        ]));
    }

    public function domainBreakdown(): array
    {
        $domains = Domain::withCount('policies')->get();
        $breakdown = [];

        foreach ($domains as $domain) {
            $breakdown[] = [
                'domain_id' => $domain->id,
                'domain_name' => $domain->name,
                'total_policies' => $domain->policies_count,
                'published' => $domain->policies()->where('status', 'published')->count(),
                'draft' => $domain->policies()->where('status', 'draft')->count(),
                'under_review' => $domain->policies()->where('status', 'under_review')->count(),
                'expired' => $domain->policies()->where('status', 'expired')->count(),
            ];
        }

        return $breakdown;
    }

    public function getSnapshotHistory(int $limit = 30): array
    {
        return GovernanceMetricSnapshot::where('snapshot_type', 'overall')
            ->orderByDesc('snapped_at')
            ->take($limit)
            ->get()
            ->toArray();
    }
}
