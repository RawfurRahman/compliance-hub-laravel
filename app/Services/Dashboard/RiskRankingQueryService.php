<?php

namespace App\Services\Dashboard;

use App\DTOs\Dashboard\DashboardFilter;
use App\Models\AssessmentFinding;

class RiskRankingQueryService extends BaseQueryService
{
    private const RISK_WEIGHTS = [
        'High' => 3,
        'Medium' => 2,
        'Low' => 1,
        'None' => 0,
    ];

    protected function domain(): string
    {
        return DashboardDomains::RISK_RANKING;
    }

    protected function compute(DashboardFilter $filter): array
    {
        $query = AssessmentFinding::query()
            ->with(['frameworkControl', 'projectAssessment.framework', 'projectAssessment.project'])
            ->where('is_compliant', false)
            ->where('risk_rating', '!=', 'None');

        if ($filter->businessUnit) {
            $query->whereHas('frameworkControl', fn ($q) => $q->where('domain', $filter->businessUnit));
        }
        if ($filter->framework) {
            $query->whereHas('projectAssessment.framework', fn ($q) => $q->where('name', $filter->framework));
        }
        if ($filter->riskStatus) {
            $query->where('risk_rating', $filter->riskStatus);
        }
        if ($filter->projectId) {
            $query->whereHas('projectAssessment', fn ($q) => $q->where('project_id', $filter->projectId));
        }

        $findings = $query->get()
            ->sortByDesc(fn ($f) => self::RISK_WEIGHTS[$f->risk_rating] ?? 0)
            ->take(10)
            ->values()
            ->map(fn ($f) => [
                'id' => $f->id,
                'control' => $f->frameworkControl?->control_id ?? '',
                'title' => $f->frameworkControl?->control_name ?: ($f->observation ?? ''),
                'framework' => $f->projectAssessment?->framework?->name ?? '',
                'project' => $f->projectAssessment?->project?->name ?? '',
                'risk' => $f->risk_rating,
                'risk_score' => self::RISK_WEIGHTS[$f->risk_rating] ?? 0,
            ]);

        return ['rankings' => $findings->toArray()];
    }
}
