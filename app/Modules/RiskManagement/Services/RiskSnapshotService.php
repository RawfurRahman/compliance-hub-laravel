<?php

namespace App\Modules\RiskManagement\Services;

use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Models\RiskSnapshot;

class RiskSnapshotService
{
    public function takeSnapshot(int $projectId, string $type = 'full'): RiskSnapshot
    {
        $risks = RiskRegister::where('project_id', $projectId)->get();

        $total = $risks->count();
        $critical = $risks->filter(fn($r) => ($r->computed_risk_rating ?? $r->risk_rating_avtvlh) >= 128)->count();
        $high = $risks->filter(fn($r) => ($r->computed_risk_rating ?? $r->risk_rating_avtvlh) >= 84
            && ($r->computed_risk_rating ?? $r->risk_rating_avtvlh) < 128)->count();
        $medium = $risks->filter(fn($r) => ($r->computed_risk_rating ?? $r->risk_rating_avtvlh) >= 54
            && ($r->computed_risk_rating ?? $r->risk_rating_avtvlh) < 84)->count();
        $low = $risks->filter(fn($r) => ($r->computed_risk_rating ?? $r->risk_rating_avtvlh) < 54)->count();

        $avgInherent = $total > 0 ? $risks->avg(fn($r) => $r->computed_risk_rating ?? $r->risk_rating_avtvlh) : 0;
        $avgResidual = $total > 0 ? $risks->avg(fn($r) => $r->computed_residual_rating ?? $r->residual_rating) : 0;
        $totalExposure = $risks->sum('exposure_value');

        $snapshotData = [
            'risk_count_by_status' => $risks->groupBy('lifecycle_status')->map->count()->toArray(),
            'risk_count_by_category' => $risks->groupBy('category')->map->count()->toArray(),
            'risk_count_by_department' => $risks->groupBy('department')->map->count()->toArray(),
            'total_accepted' => $risks->where('measurement', 'Accepted')->count(),
            'total_not_accepted' => $risks->where('measurement', 'Not Accepted')->count(),
        ];

        return RiskSnapshot::create([
            'project_id'        => $projectId,
            'snapshot_type'     => $type,
            'snapshot_data'     => $snapshotData,
            'total_risks'       => $total,
            'critical_count'    => $critical,
            'high_count'        => $high,
            'medium_count'      => $medium,
            'low_count'         => $low,
            'total_exposure'    => round($totalExposure, 2),
            'avg_inherent_score' => round($avgInherent, 2),
            'avg_residual_score' => round($avgResidual, 2),
            'snapped_at'        => now(),
        ]);
    }

    public function latestByProject(int $projectId, string $type = 'full'): ?RiskSnapshot
    {
        return RiskSnapshot::where('project_id', $projectId)
            ->where('snapshot_type', $type)
            ->latest('snapped_at')
            ->first();
    }
}
