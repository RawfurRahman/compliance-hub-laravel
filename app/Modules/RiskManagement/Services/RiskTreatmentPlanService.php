<?php

namespace App\Modules\RiskManagement\Services;

use App\Modules\RiskManagement\Models\RiskTreatmentPlan;
use Illuminate\Support\Facades\Auth;

class RiskTreatmentPlanService
{
    public function getForRisk(int $riskId)
    {
        return RiskTreatmentPlan::where('risk_register_id', $riskId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(array $data): RiskTreatmentPlan
    {
        $data['created_by'] = Auth::id();
        return RiskTreatmentPlan::create($data);
    }

    public function update(RiskTreatmentPlan $plan, array $data): RiskTreatmentPlan
    {
        $plan->update($data);
        return $plan->fresh();
    }

    public function delete(RiskTreatmentPlan $plan): void
    {
        $plan->delete();
    }

    public function markCompleted(RiskTreatmentPlan $plan, int $effectivenessRating): RiskTreatmentPlan
    {
        $plan->update([
            'status' => 'completed',
            'completion_date' => now(),
            'progress_pct' => 100,
            'effectiveness_rating' => $effectivenessRating,
        ]);
        return $plan->fresh();
    }

    public function updateProgress(RiskTreatmentPlan $plan, int $progressPct): RiskTreatmentPlan
    {
        $plan->update(['progress_pct' => min(100, max(0, $progressPct))]);
        return $plan->fresh();
    }
}
