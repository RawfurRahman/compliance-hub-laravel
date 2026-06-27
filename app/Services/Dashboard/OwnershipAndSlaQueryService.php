<?php

namespace App\Services\Dashboard;

use App\DTOs\Dashboard\DashboardFilter;
use App\Modules\Compliance\Models\SLATracker;
use App\Modules\Compliance\Models\AuditFinding;
use App\Modules\Governance\Models\OwnershipMatrix;
use App\Modules\Governance\Models\Domain;

class OwnershipAndSlaQueryService extends BaseQueryService
{
    protected function domain(): string
    {
        return DashboardDomains::OWNERSHIP_SLA;
    }

    protected function compute(DashboardFilter $filter): array
    {
        $ownershipQuery = OwnershipMatrix::query()->with(['policy', 'user']);

        if ($filter->businessUnit) {
            $ownershipQuery->where('business_unit', $filter->businessUnit);
        }
        if ($filter->framework) {
            $ownershipQuery->whereHas('policy.domain', fn ($q) => $q->where('name', $filter->framework));
        }

        $totalAssignments = (clone $ownershipQuery)->count();
        $primaryOwners = (clone $ownershipQuery)->where('is_primary', true)->count();

        $byRole = (clone $ownershipQuery)
            ->selectRaw('role, COUNT(*) as aggregate')
            ->groupBy('role')
            ->pluck('aggregate', 'role');

        $totalDomains = Domain::count();
        $coveredDomains = (clone $ownershipQuery)
            ->whereHas('policy.domain')
            ->get()
            ->pluck('policy.domain_id')
            ->unique()
            ->count();

        $slaQuery = SLATracker::query();
        if ($filter->projectId) {
            $slaQuery->whereHasMorph('trackable', [AuditFinding::class], fn ($q) => $q->where('project_id', $filter->projectId));
        }

        $totalSla = (clone $slaQuery)->count();
        $breachedSla = (clone $slaQuery)->where('status', 'breached')->count();
        $atRiskSla = (clone $slaQuery)
            ->where('status', 'active')
            ->whereNotNull('deadline_at')
            ->where('deadline_at', '<=', now()->addDays(7))
            ->count();

        return [
            'ownership' => [
                'total_assignments' => $totalAssignments,
                'primary_owners' => $primaryOwners,
                'by_role' => [
                    'owner' => (int) $byRole->get('owner', 0),
                    'reviewer' => (int) $byRole->get('reviewer', 0),
                    'approver' => (int) $byRole->get('approver', 0),
                    'stakeholder' => (int) $byRole->get('stakeholder', 0),
                ],
                'domain_coverage_pct' => $totalDomains > 0
                    ? round($coveredDomains / $totalDomains * 100, 1)
                    : 0.0,
            ],
            'sla' => [
                'total_trackers' => $totalSla,
                'breached' => $breachedSla,
                'at_risk' => $atRiskSla,
                'sla_compliance_pct' => $totalSla > 0
                    ? round(($totalSla - $breachedSla) / $totalSla * 100, 1)
                    : 100.0,
            ],
        ];
    }
}
