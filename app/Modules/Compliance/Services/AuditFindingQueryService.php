<?php

namespace App\Modules\Compliance\Services;

use App\DTOs\Dashboard\DashboardFilter;
use App\Modules\Compliance\Models\AuditFinding;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AuditFindingQueryService
{
    public function summary(DashboardFilter $filter): array
    {
        $query = AuditFinding::query()->with('slaTrackers');

        $this->applyFilters($query, $filter);

        $total = (clone $query)->count();
        $open = (clone $query)->whereIn('status', ['open', 'in_review'])->count();
        $closed = (clone $query)->whereIn('status', ['resolved', 'closed'])->count();
        $overdue = (clone $query)->get()->filter(fn ($f) => $f->is_overdue)->count();

        $severityBreakdown = (clone $query)
            ->selectRaw('severity, COUNT(*) as aggregate')
            ->groupBy('severity')
            ->pluck('aggregate', 'severity');

        return [
            'total_findings' => $total,
            'open' => $open,
            'closed' => $closed,
            'overdue' => $overdue,
            'severity_breakdown' => [
                'critical' => (int) $severityBreakdown->get('critical', 0),
                'high' => (int) $severityBreakdown->get('high', 0),
                'medium' => (int) $severityBreakdown->get('medium', 0),
                'low' => (int) $severityBreakdown->get('low', 0),
            ],
            'closure_rate' => $total > 0 ? round($closed / $total * 100, 1) : 0.0,
        ];
    }

    public function trends(DashboardFilter $filter): Collection
    {
        $query = AuditFinding::query();

        $this->applyFilters($query, $filter);

        $records = $query->selectRaw("strftime('%Y-%m', created_at) as month, severity, COUNT(*) as count")
            ->groupBy('month', 'severity')
            ->orderBy('month')
            ->get();

        $grouped = $records->groupBy('month');

        return $grouped->map(function ($rows, string $month) {
            $bySeverity = $rows->keyBy('severity');
            return [
                'month' => $month,
                'critical' => (int) ($bySeverity->get('critical')?->count ?? 0),
                'high' => (int) ($bySeverity->get('high')?->count ?? 0),
                'medium' => (int) ($bySeverity->get('medium')?->count ?? 0),
                'low' => (int) ($bySeverity->get('low')?->count ?? 0),
                'total' => (int) $rows->sum('count'),
            ];
        })->values();
    }

    private function applyFilters($query, DashboardFilter $filter): void
    {
        if ($filter->framework) {
            $query->whereHas('frameworkControl.framework', fn ($q) => $q->where('name', $filter->framework));
        }

        if ($filter->owner) {
            $query->whereHas('auditor', fn ($q) => $q->where('name', 'LIKE', "%{$filter->owner}%"));
        }

        if ($filter->dateFrom) {
            $query->whereDate('audit_date', '>=', Carbon::parse($filter->dateFrom));
        }

        if ($filter->dateTo) {
            $query->whereDate('audit_date', '<=', Carbon::parse($filter->dateTo));
        }

        if ($filter->riskStatus) {
            if ($filter->riskStatus === 'open') {
                $query->open();
            } elseif (in_array($filter->riskStatus, ['resolved', 'closed'])) {
                $query->where('status', $filter->riskStatus);
            }
        }

        if ($filter->projectId) {
            $query->where('project_id', $filter->projectId);
        }
    }
}
