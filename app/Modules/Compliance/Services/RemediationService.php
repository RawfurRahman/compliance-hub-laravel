<?php

namespace App\Modules\Compliance\Services;

use App\Models\AssessmentFinding;
use App\Modules\Compliance\Events\RemediationPlanClosed;
use App\Modules\Compliance\Events\RemediationPlanCreated;
use App\Modules\RiskManagement\Models\RiskTreatmentPlan;
use Illuminate\Support\Collection;

class RemediationService
{
    public function createFromFinding(AssessmentFinding $finding, ?int $createdBy = null): RiskTreatmentPlan
    {
        $plan = RiskTreatmentPlan::create([
            'risk_register_id' => $finding->risk_register_id,
            'assessment_finding_id' => $finding->id,
            'title' => "Remediation: {$finding->observation}",
            'treatment_type' => 'reduce',
            'status' => 'planned',
            'target_date' => $finding->due_date ?? now()->addDays(30),
            'created_by' => $createdBy,
            'notes' => "Auto-created from assessment finding #{$finding->id}",
        ]);

        $finding->update([
            'compliance_state' => 'under_review',
        ]);

        event(new RemediationPlanCreated($plan, $finding));

        return $plan;
    }

    public function closePlan(RiskTreatmentPlan $plan, ?string $notes = null): RiskTreatmentPlan
    {
        $plan->update([
            'status' => 'completed',
            'completion_date' => now(),
            'notes' => $notes ? ($plan->notes . "\n" . $notes) : $plan->notes,
        ]);

        event(new RemediationPlanClosed($plan, $notes));

        return $plan;
    }

    public function getOverdueBySLA(?int $projectId = null): Collection
    {
        $query = RiskTreatmentPlan::overdue()->with('risk');

        if ($projectId) {
            $query->whereHas('risk', fn ($q) => $q->where('project_id', $projectId));
        }

        return $query->latest()->get();
    }

    public function getByFinding(AssessmentFinding $finding): Collection
    {
        return RiskTreatmentPlan::where('assessment_finding_id', $finding->id)
            ->latest()
            ->get();
    }
}
