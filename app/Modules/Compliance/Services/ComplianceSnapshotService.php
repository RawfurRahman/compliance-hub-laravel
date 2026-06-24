<?php

namespace App\Modules\Compliance\Services;

use App\Modules\Compliance\Events\ComplianceSnapshotTaken;
use App\Modules\Compliance\Models\ComplianceSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ComplianceSnapshotService
{
    public function takeSnapshot(int $projectId, string $type = 'ondemand'): ComplianceSnapshot
    {
        $counts = ComplianceFindingService::countByState($projectId);
        $total = array_sum($counts);

        $driver = DB::connection()->getDriverName();
        $diffExpr = $driver === 'sqlite'
            ? "AVG(julianday(completion_date) - julianday(target_date))"
            : "AVG(DATEDIFF(completion_date, target_date))";

        $remediationTimes = \App\Modules\RiskManagement\Models\RiskTreatmentPlan::whereHas('risk', fn ($q) => $q->where('project_id', $projectId))
            ->whereNotNull('completion_date')
            ->selectRaw("{$diffExpr} as avg_days")
            ->value('avg_days');

        $snapshot = ComplianceSnapshot::create([
            'project_id' => $projectId,
            'snapshot_type' => $type,
            'snapshot_data' => $counts,
            'total_controls' => $total,
            'compliant_count' => $counts['compliant'],
            'partial_count' => $counts['partially_compliant'],
            'non_compliant_count' => $counts['non_compliant'],
            'waived_count' => $counts['waived'],
            'overdue_count' => $counts['overdue'],
            'under_review_count' => $counts['under_review'],
            'avg_remediation_time' => $remediationTimes ? round((float) $remediationTimes, 2) : null,
            'snapshot_date' => now(),
        ]);

        event(new ComplianceSnapshotTaken($snapshot));

        return $snapshot;
    }

    public function compare(int $snapshotIdA, int $snapshotIdB): array
    {
        $a = ComplianceSnapshot::findOrFail($snapshotIdA);
        $b = ComplianceSnapshot::findOrFail($snapshotIdB);

        $deltas = [];
        $fields = ['total_controls', 'compliant_count', 'partial_count', 'non_compliant_count',
            'waived_count', 'overdue_count', 'under_review_count', 'avg_remediation_time'];

        foreach ($fields as $field) {
            $deltas[$field] = [
                'from' => (float) ($a->$field ?? 0),
                'to' => (float) ($b->$field ?? 0),
                'delta' => (float) (($b->$field ?? 0) - ($a->$field ?? 0)),
            ];
        }

        return [
            'snapshot_a' => $a,
            'snapshot_b' => $b,
            'deltas' => $deltas,
        ];
    }

    public function getTrend(int $projectId, string $type = 'weekly'): Collection
    {
        return ComplianceSnapshot::forProject($projectId)
            ->byType($type)
            ->orderBy('snapshot_date')
            ->get();
    }
}
