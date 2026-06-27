<?php

namespace App\Services\Dashboard;

use App\DTOs\Dashboard\DashboardFilter;
use App\Models\AssessmentFinding;

class HeatmapQueryService extends BaseQueryService
{
    protected function domain(): string
    {
        return DashboardDomains::HEATMAP;
    }

    protected function compute(DashboardFilter $filter): array
    {
        $likelihoodAxis = ['Open', 'In Progress', 'Closed'];
        $impactAxis = ['Low', 'Medium', 'High'];

        $query = AssessmentFinding::query()
            ->selectRaw('status, risk_rating, COUNT(*) as aggregate')
            ->whereIn('status', $likelihoodAxis)
            ->whereIn('risk_rating', $impactAxis);

        if ($filter->businessUnit) {
            $query->whereHas('frameworkControl', fn ($q) => $q->where('domain', $filter->businessUnit));
        }
        if ($filter->framework) {
            $query->whereHas('projectAssessment.framework', fn ($q) => $q->where('name', $filter->framework));
        }
        if ($filter->projectId) {
            $query->whereHas('projectAssessment', fn ($q) => $q->where('project_id', $filter->projectId));
        }

        $rows = $query->groupBy('status', 'risk_rating')
            ->get()
            ->keyBy(fn ($row) => $row->status . '|' . $row->risk_rating);

        $cells = [];
        foreach ($likelihoodAxis as $likelihood) {
            foreach ($impactAxis as $impact) {
                $key = $likelihood . '|' . $impact;
                $cells[] = [
                    'likelihood' => $likelihood,
                    'impact' => $impact,
                    'count' => (int) ($rows->get($key)->aggregate ?? 0),
                ];
            }
        }

        return $cells;
    }
}
