<?php

namespace App\Modules\Compliance\Services;

use App\Models\AssessmentFinding;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * RemediationMetricsService
 *
 * Computes remediation performance metrics from source data so the dashboard
 * is a pure rendering layer.
 *
 * Durations are returned in hours.
 */
class RemediationMetricsService
{
    /** Aging buckets in days (upper-bound inclusive); last bucket is open-ended. */
    private const AGING_BUCKETS = [
        '0-7' => 7,
        '8-30' => 30,
        '31-60' => 60,
        '61-90' => 90,
        '90+' => PHP_INT_MAX,
    ];

    /**
     * Compute the full remediation metric profile for a project (or all).
     *
     * @param  string  $scope  all | risk | control
     * @return array<string,mixed>
     */
    public function forProject(?int $projectId = null, string $scope = 'all'): array
    {
        $findings = $this->findingsQuery($projectId, $scope)->get();

        $total = $findings->count();
        $closed = $findings->where('status', 'Closed');
        $open = $findings->where('status', '!=', 'Closed');
        $closedCount = $closed->count();
        $openCount = $open->count();

        $mttr = $this->meanHours($closed, fn ($f) => $this->hoursBetween($f->created_at, $f->updated_at));
        $mtta = $this->meanHours(
            $findings->filter(fn ($f) => $f->status !== 'Open'),
            fn ($f) => $this->hoursBetween($f->created_at, $f->updated_at)
        );
        $mttAssign = $this->meanHours(
            $findings->filter(fn ($f) => in_array($f->status, ['In Progress', 'Closed'], true)),
            fn ($f) => $this->hoursBetween($f->created_at, $f->updated_at)
        );
        $mttc = $this->meanHours($closed, fn ($f) => $this->hoursBetween($f->created_at, $f->updated_at));

        $overdue = $findings->filter(fn ($f) => $this->isOverdue($f))->count();
        $closureRate = $total > 0 ? round($closedCount / $total * 100, 2) : 0.0;

        return [
            'project_id' => $projectId,
            'scope' => $scope,
            'total_items' => $total,
            'open_items' => $openCount,
            'closed_items' => $closedCount,
            'overdue_count' => $overdue,
            'mttr_hours' => $mttr,
            'mtta_hours' => $mtta,
            'mt_assign_hours' => $mttAssign,
            'mttc_hours' => $mttc,
            'closure_rate' => $closureRate,
            'aging_buckets' => $this->agingBuckets($open),
        ];
    }

    /**
     * Query historical remediation metric snapshots for trend charts.
     *
     * @return Collection<int,array{month:string,opened:int,closed:int,mttr_hours:?float}>
     */
    public function getTrendData(?int $projectId = null, ?string $dateFrom = null, ?string $dateTo = null): Collection
    {
        $query = RemediationMetric::query()
            ->where('scope', 'all')
            ->orderBy('calculated_at');

        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }
        if ($dateFrom) {
            $query->whereDate('calculated_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('calculated_at', '<=', $dateTo);
        }

        return $query->get()->map(fn (RemediationMetric $m) => [
            'id' => $m->id,
            'month' => $m->calculated_at->format('Y-m'),
            'opened' => $m->open_items,
            'closed' => $m->closed_items,
            'overdue' => $m->overdue_count,
            'mttr_hours' => $m->mttr_hours !== null ? (float) $m->mttr_hours : null,
            'closure_rate' => (float) $m->closure_rate,
            'aging_buckets' => $m->aging_buckets,
        ]);
    }

    /**
     * Compute and persist remediation metrics for trend reporting.
     */
    public function snapshot(?int $projectId = null, string $scope = 'all'): RemediationMetric
    {
        $profile = $this->forProject($projectId, $scope);

        return RemediationMetric::create([
            'project_id' => $projectId,
            'scope' => $scope,
            'total_items' => $profile['total_items'],
            'open_items' => $profile['open_items'],
            'closed_items' => $profile['closed_items'],
            'overdue_count' => $profile['overdue_count'],
            'mttr_hours' => $profile['mttr_hours'],
            'mtta_hours' => $profile['mtta_hours'],
            'mt_assign_hours' => $profile['mt_assign_hours'],
            'mttc_hours' => $profile['mttc_hours'],
            'closure_rate' => $profile['closure_rate'],
            'aging_buckets' => $profile['aging_buckets'],
            'breakdown' => null,
            'calculated_at' => now(),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Internal helpers */
    /* ------------------------------------------------------------------ */

    private function findingsQuery(?int $projectId, string $scope)
    {
        $query = AssessmentFinding::query();

        if ($projectId !== null) {
            $query->whereHas('projectAssessment', fn ($q) => $q->where('project_id', $projectId));
        }

        if ($scope === 'risk') {
            $query->whereNotNull('risk_register_id');
        } elseif ($scope === 'control') {
            $query->whereNull('risk_register_id');
        }

        return $query;
    }

    /**
     * @param  Collection<int,AssessmentFinding>  $items
     */
    private function meanHours(Collection $items, callable $resolver): ?float
    {
        $values = $items->map($resolver)->filter(fn ($v) => $v !== null);
        if ($values->isEmpty()) {
            return null;
        }

        return round($values->avg(), 2);
    }

    private function hoursBetween($start, $end): ?float
    {
        if (! $start || ! $end) {
            return null;
        }

        $startAt = Carbon::parse($start);
        $endAt = Carbon::parse($end);
        if ($endAt->lessThan($startAt)) {
            return null;
        }

        return round($startAt->diffInMinutes($endAt) / 60, 2);
    }

    private function isOverdue(AssessmentFinding $finding): bool
    {
        if ($finding->status === 'Closed') {
            return false;
        }

        return $finding->due_date && Carbon::parse($finding->due_date)->isPast();
    }

    /**
     * @param  Collection<int,AssessmentFinding>  $openFindings
     * @return array<string,int>
     */
    private function agingBuckets(Collection $openFindings): array
    {
        $buckets = array_fill_keys(array_keys(self::AGING_BUCKETS), 0);

        foreach ($openFindings as $finding) {
            $ageDays = Carbon::parse($finding->created_at)->diffInDays(now());
            foreach (self::AGING_BUCKETS as $label => $upper) {
                if ($ageDays <= $upper) {
                    $buckets[$label]++;
                    break;
                }
            }
        }

        return $buckets;
    }
}
