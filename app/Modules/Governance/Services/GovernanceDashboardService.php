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

    public function policyGovernanceSummary(?int $projectId = null, ?string $framework = null, ?string $businessUnit = null): array
    {
        $query = Policy::query();

        if ($businessUnit) {
            $query->where('business_unit', $businessUnit);
        }

        $total = (clone $query)->count();
        $byStatus = (clone $query)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $domainBreakdown = $this->domainPolicyBreakdown($businessUnit);

        $overdueReviews = (clone $query)
            ->whereHas('reviews', fn ($q) => $q
                ->whereIn('status', ['pending', 'in_progress'])
                ->where('due_date', '<', now()))
            ->count();

        $pendingApprovals = PolicyApproval::query()
            ->where('status', 'pending')
            ->when($businessUnit, fn ($q) => $q->whereHas('policy', fn ($q2) => $q2->where('business_unit', $businessUnit)))
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
            'domain_breakdown' => $domainBreakdown,
        ];
    }

    public function ownershipAccountability(?string $businessUnit = null, ?string $framework = null): array
    {
        $query = \App\Modules\Governance\Models\OwnershipMatrix::query()->with(['policy', 'user']);

        if ($businessUnit) {
            $query->where('business_unit', $businessUnit);
        }

        if ($framework) {
            $query->whereHas('policy.domain', fn ($q) => $q->where('name', $framework));
        }

        $totalAssignments = (clone $query)->count();
        $primaryOwners = (clone $query)->where('is_primary', true)->count();

        $byRole = (clone $query)
            ->selectRaw('role, COUNT(*) as aggregate')
            ->groupBy('role')
            ->pluck('aggregate', 'role');

        $byBusinessUnit = (clone $query)
            ->selectRaw('business_unit, COUNT(*) as aggregate')
            ->groupBy('business_unit')
            ->pluck('aggregate', 'business_unit');

        $coverage = $this->computeCoverage($query);

        return [
            'total_assignments' => $totalAssignments,
            'primary_owners' => $primaryOwners,
            'by_role' => [
                'owner' => (int) $byRole->get('owner', 0),
                'reviewer' => (int) $byRole->get('reviewer', 0),
                'approver' => (int) $byRole->get('approver', 0),
                'stakeholder' => (int) $byRole->get('stakeholder', 0),
            ],
            'by_business_unit' => $byBusinessUnit->toArray(),
            'coverage_pct' => $coverage['coverage_pct'],
            'gaps' => $coverage['gaps'],
        ];
    }

    private function domainPolicyBreakdown(?string $businessUnit): array
    {
        $domains = Domain::withCount(['policies' => function ($q) use ($businessUnit) {
            if ($businessUnit) {
                $q->where('business_unit', $businessUnit);
            }
        }])->get();

        $breakdown = [];
        foreach ($domains as $domain) {
            $breakdown[] = [
                'domain_id' => $domain->id,
                'domain_name' => $domain->name,
                'total_policies' => $domain->policies_count,
            ];
        }

        return $breakdown;
    }

    private function computeCoverage($query): array
    {
        $total = \App\Modules\Governance\Models\Domain::count();
        $covered = (clone $query)
            ->whereHas('policy.domain')
            ->get()
            ->pluck('policy.domain_id')
            ->unique()
            ->count();

        return [
            'coverage_pct' => $total > 0 ? round($covered / $total * 100, 1) : 0.0,
            'gaps' => max(0, $total - $covered),
        ];
    }
}
