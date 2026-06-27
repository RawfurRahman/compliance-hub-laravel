<?php

namespace App\Services\Dashboard;

use App\DTOs\Dashboard\DashboardFilter;
use App\Models\AssessmentFinding;
use App\Modules\Compliance\Models\AuditFinding;
use Illuminate\Support\Collection;

class RemediationTrendQueryService extends BaseQueryService
{
    protected function domain(): string
    {
        return DashboardDomains::REMEDIATION_TREND;
    }

    protected function compute(DashboardFilter $filter): array
    {
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->format('Y-m'));

        $assessmentTrend = $this->assessmentTrend($filter);
        $auditTrend = $this->auditTrend($filter);

        $monthlyTrend = $months->map(function (string $month) use ($assessmentTrend, $auditTrend) {
            $assessmentMonth = $assessmentTrend->get($month, ['opened' => 0, 'closed' => 0]);
            $auditMonth = $auditTrend->get($month, ['opened' => 0, 'closed' => 0]);

            return [
                'month' => $month,
                'opened' => ($assessmentMonth['opened'] ?? 0) + ($auditMonth['opened'] ?? 0),
                'closed' => ($assessmentMonth['closed'] ?? 0) + ($auditMonth['closed'] ?? 0),
                'net_open' => ($assessmentMonth['opened'] ?? 0) + ($auditMonth['opened'] ?? 0)
                    - ($assessmentMonth['closed'] ?? 0) - ($auditMonth['closed'] ?? 0),
            ];
        })->values();

        $currentOpen = AssessmentFinding::query()->where('is_compliant', false)
            ->when($filter->businessUnit, fn ($q) => $q->whereHas('frameworkControl', fn ($q2) => $q2->where('domain', $filter->businessUnit)))
            ->when($filter->framework, fn ($q) => $q->whereHas('projectAssessment.framework', fn ($q2) => $q2->where('name', $filter->framework)))
            ->count();

        return [
            'monthly_trend' => $monthlyTrend->toArray(),
            'current_open' => $currentOpen,
        ];
    }

    private function assessmentTrend(DashboardFilter $filter): Collection
    {
        $query = AssessmentFinding::query()
            ->selectRaw("strftime('%Y-%m', created_at) as month, status, is_compliant, COUNT(*) as count")
            ->groupBy('month', 'status', 'is_compliant');

        if ($filter->businessUnit) {
            $query->whereHas('frameworkControl', fn ($q) => $q->where('domain', $filter->businessUnit));
        }
        if ($filter->framework) {
            $query->whereHas('projectAssessment.framework', fn ($q) => $q->where('name', $filter->framework));
        }
        if ($filter->projectId) {
            $query->whereHas('projectAssessment', fn ($q) => $q->where('project_id', $filter->projectId));
        }

        return $query->get()
            ->groupBy('month')
            ->map(fn ($rows) => [
                'opened' => $rows->sum('count'),
                'closed' => $rows->where('is_compliant', true)->sum('count'),
            ]);
    }

    private function auditTrend(DashboardFilter $filter): Collection
    {
        $query = AuditFinding::query()
            ->selectRaw("strftime('%Y-%m', created_at) as month, COUNT(*) as count")
            ->groupBy('month');

        if ($filter->framework) {
            $query->whereHas('frameworkControl.framework', fn ($q) => $q->where('name', $filter->framework));
        }
        if ($filter->projectId) {
            $query->where('project_id', $filter->projectId);
        }

        $opened = (clone $query)->get()->keyBy('month');

        $closedQuery = AuditFinding::query()
            ->selectRaw("strftime('%Y-%m', updated_at) as month, COUNT(*) as count")
            ->whereIn('status', ['resolved', 'closed'])
            ->groupBy('month');

        if ($filter->framework) {
            $closedQuery->whereHas('frameworkControl.framework', fn ($q) => $q->where('name', $filter->framework));
        }
        if ($filter->projectId) {
            $closedQuery->where('project_id', $filter->projectId);
        }

        $closed = $closedQuery->get()->keyBy('month');

        $allMonths = $opened->keys()->merge($closed->keys())->unique()->values();

        return $allMonths->mapWithKeys(fn ($month) => [
            $month => [
                'opened' => (int) ($opened->get($month)?->count ?? 0),
                'closed' => (int) ($closed->get($month)?->count ?? 0),
            ],
        ]);
    }
}
