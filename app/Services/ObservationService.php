<?php

namespace App\Services;

use App\Models\AssessmentFinding;
use App\Models\Observation;
use App\Modules\RiskManagement\Models\RiskRegister;
use Illuminate\Support\Facades\Auth;

class ObservationService
{
    private GapAssessmentReportService $gapReportService;

    private RiskAutoCreationService $riskAutoService;

    public function __construct(GapAssessmentReportService $gapReportService, RiskAutoCreationService $riskAutoService)
    {
        $this->gapReportService = $gapReportService;
        $this->riskAutoService = $riskAutoService;
    }

    public function createFromFinding(AssessmentFinding $finding, array $data): Observation
    {
        $assessment = $finding->projectAssessment;
        if (! $assessment || $assessment->type !== 'Gap') {
            throw new \RuntimeException('Observations can only be raised from a Gap Assessment finding.');
        }

        return Observation::create([
            'project_id' => $assessment->project_id,
            'assessment_finding_id' => $finding->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? $finding->observation,
            'gap' => $data['gap'] ?? $finding->gap_description,
            'risk_impact' => $data['risk_impact'] ?? $finding->impact,
            'recommendation' => $data['recommendation'] ?? $finding->recommendation,
            'owner_user_id' => $data['owner_user_id'] ?? null,
            'target_date' => $data['target_date'] ?? null,
            'raised_by' => Auth::id(),
            'status' => 'Open',
        ]);
    }

    public function update(Observation $observation, array $data): Observation
    {
        if (isset($data['status']) && ! in_array($data['status'], Observation::STATUSES)) {
            throw new \RuntimeException("Invalid observation status: {$data['status']}");
        }

        $observation->update(array_intersect_key($data, array_flip([
            'title', 'description', 'gap', 'risk_impact', 'recommendation',
            'management_response', 'corrective_action', 'owner_user_id',
            'target_date', 'status',
        ])));

        return $observation->fresh();
    }

    public function sendToFinalAssessment(Observation $observation): Observation
    {
        if ($observation->sent_to_final_assessment_at) {
            throw new \RuntimeException('Observation has already been sent to the final assessment.');
        }

        $finalFinding = $this->gapReportService->syncFindingToFinalReport($observation->finding);

        if (! $finalFinding) {
            throw new \RuntimeException('Underlying finding is not part of an active Gap Assessment.');
        }

        $observation->update([
            'final_assessment_finding_id' => $finalFinding->id,
            'sent_to_final_assessment_at' => now(),
        ]);

        return $observation->fresh();
    }

    public function addToRiskRegister(Observation $observation): RiskRegister
    {
        return $this->riskAutoService->createRiskFromObservation($observation);
    }
}
