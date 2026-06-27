<?php

namespace App\Services\Dashboard;

use App\DTOs\Dashboard\DashboardFilter;
use App\Models\AssessmentFinding;
use App\Models\Framework;
use App\Models\Project;

class KpiQueryService extends BaseQueryService
{
    protected function domain(): string
    {
        return DashboardDomains::KPI;
    }

    protected function compute(DashboardFilter $filter): array
    {
        $query = AssessmentFinding::query();

        if ($filter->businessUnit) {
            $query->whereHas('frameworkControl', fn ($q) => $q->where('domain', $filter->businessUnit));
        }
        if ($filter->framework) {
            $query->whereHas('projectAssessment.framework', fn ($q) => $q->where('name', $filter->framework));
        }
        if ($filter->projectId) {
            $query->whereHas('projectAssessment', fn ($q) => $q->where('project_id', $filter->projectId));
        }

        $total = (clone $query)->count();
        $compliant = (clone $query)->where('is_compliant', true)->count();
        $open = (clone $query)->where('status', 'Open')->count();
        $overdue = (clone $query)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->where('is_compliant', false)
            ->count();

        return [
            'projects' => Project::count(),
            'frameworks' => Framework::where('is_active', true)->count(),
            'total_controls' => $total,
            'compliant' => $compliant,
            'open_findings' => $open,
            'overdue_findings' => $overdue,
            'compliance_pct' => $total > 0 ? round($compliant / $total * 100, 1) : 0.0,
        ];
    }
}
