<?php

namespace App\Modules\RiskManagement\Console\Commands;

use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Models\RiskSnapshot;
use Illuminate\Console\Command;

class SnapshotRiskExposures extends Command
{
    protected $signature = 'risks:snapshot-exposure {--project-id=}';
    protected $description = 'Take exposure snapshots for all projects';

    public function handle(): int
    {
        $projectIds = $this->option('project-id')
            ? [(int) $this->option('project-id')]
            : RiskRegister::distinct()->pluck('project_id')->toArray();

        foreach ($projectIds as $projectId) {
            $risks = RiskRegister::where('project_id', $projectId)->get();

            $totalExposure = $risks->sum('exposure_value');
            $criticalCount = $risks->filter(fn($r) => ($r->computed_risk_rating ?? $r->risk_rating_avtvlh) >= 128)->count();
            $highCount = $risks->filter(fn($r) => ($r->computed_risk_rating ?? $r->risk_rating_avtvlh) >= 84
                && ($r->computed_risk_rating ?? $r->risk_rating_avtvlh) < 128)->count();
            $mediumCount = $risks->filter(fn($r) => ($r->computed_risk_rating ?? $r->risk_rating_avtvlh) >= 54
                && ($r->computed_risk_rating ?? $r->risk_rating_avtvlh) < 84)->count();
            $lowCount = $risks->filter(fn($r) => ($r->computed_risk_rating ?? $r->risk_rating_avtvlh) < 54)->count();

            RiskSnapshot::create([
                'project_id'     => $projectId,
                'snapshot_type'  => 'exposure',
                'snapshot_data'  => ['exposure_by_risk' => $risks->pluck('exposure_value', 'id')->toArray()],
                'total_risks'    => $risks->count(),
                'critical_count' => $criticalCount,
                'high_count'     => $highCount,
                'medium_count'   => $mediumCount,
                'low_count'      => $lowCount,
                'total_exposure' => round($totalExposure, 2),
                'snapped_at'     => now(),
            ]);

            $this->info("Exposure snapshot taken for project {$projectId}");
        }

        return Command::SUCCESS;
    }
}
