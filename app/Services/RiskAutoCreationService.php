<?php

namespace App\Services;

use App\Models\AssessmentFinding;
use App\Models\EvidenceFile;
use App\Models\Observation;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Services\RiskService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RiskAutoCreationService
{
    private RiskService $riskService;

    public function __construct(RiskService $riskService)
    {
        $this->riskService = $riskService;
    }

    public function createRiskFromGap(EvidenceFile $evidence): RiskRegister
    {
        $reportData = $evidence->analysis_report_data ?? [];
        $control = $evidence->frameworkControl;

        if (! $evidence->framework_control_id) {
            throw new \RuntimeException('Evidence file has no framework_control_id');
        }

        $finding = AssessmentFinding::where('source_type', 'evidence')
            ->where('source_id', $evidence->id)
            ->where('framework_control_id', $evidence->framework_control_id)
            ->first();

        $riskRating = $reportData['risk_rating'] ?? 'Medium';
        $inherentScore = $this->mapRiskRatingToScore($riskRating);

        $serialNo = RiskRegister::where('project_id', $evidence->project_id)
            ->max('serial_no');
        $nextSerial = $serialNo ? intval($serialNo) + 1 : 1;

        $gapDescription = $reportData['gap_description'] ?? $evidence->ai_observations ?? '';

        $risk = RiskRegister::create([
            'project_id' => $evidence->project_id,
            'framework_control_id' => $evidence->framework_control_id,
            'serial_no' => (string) $nextSerial,
            'asset_process_service' => Str::limit($reportData['requirement_description'] ?? ($control->requirement_description ?? ''), 200),
            'risk_owner' => $reportData['auditor_notes'] ?? null,
            'risk_calculation_date' => now(),
            'threats' => [$gapDescription],
            'vulnerabilities' => [$reportData['gap_description'] ?? ''],
            'existing_control' => $reportData['current_implementation_status'] ?? '',
            'likelihood_lh' => $this->mapRiskRatingToLikelihood($riskRating),
            'vulnerability_level_av' => $this->mapRiskRatingToVulnerability($riskRating),
            'threat_level_t' => $this->mapRiskRatingToThreat($riskRating),
            'risk_rating_avtvlh' => $inherentScore,
            'lifecycle_status' => 'assessed',
            'source' => 'evidence_tracker',
            'assessment_finding_id' => $finding?->id,
            'owner_user_id' => $evidence->user_id,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
            'category' => 'Compliance',
        ]);

        try {
            $this->riskService->recalculateRisk($risk);
        } catch (\Exception $e) {
            Log::warning("RiskAutoCreation: Score recalculation failed for risk_id {$risk->id}: ".$e->getMessage());
        }

        if ($finding) {
            $finding->update(['risk_register_id' => $risk->id]);
        }

        Log::info("RiskAutoCreation: Created RiskRegister id {$risk->id} from evidence_file_id {$evidence->id}");

        return $risk->fresh();
    }

    public function createRiskFromAssessmentFinding(AssessmentFinding $finding): RiskRegister
    {
        $control = $finding->frameworkControl;
        $reportData = $finding->ai_gaps ?? [];

        $gapText = $finding->gap_description ?? $finding->observation ?? '';
        $riskRating = $finding->risk_rating ?? 'Medium';
        $inherentScore = $this->mapRiskRatingToScore($riskRating);

        $serialNo = RiskRegister::where('project_id', $finding->projectAssessment->project_id)
            ->max('serial_no');
        $nextSerial = $serialNo ? intval($serialNo) + 1 : 1;

        $risk = RiskRegister::create([
            'project_id' => $finding->projectAssessment->project_id,
            'framework_control_id' => $finding->framework_control_id,
            'serial_no' => (string) $nextSerial,
            'asset_process_service' => Str::limit($control->requirement_description ?? $gapText, 200),
            'risk_calculation_date' => now(),
            'threats' => [$gapText],
            'vulnerabilities' => [$gapText],
            'existing_control' => '',
            'likelihood_lh' => $this->mapRiskRatingToLikelihood($riskRating),
            'vulnerability_level_av' => $this->mapRiskRatingToVulnerability($riskRating),
            'threat_level_t' => $this->mapRiskRatingToThreat($riskRating),
            'risk_rating_avtvlh' => $inherentScore,
            'lifecycle_status' => 'assessed',
            'source' => 'gap_assessment',
            'assessment_finding_id' => $finding->id,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
            'category' => 'Compliance',
        ]);

        try {
            $this->riskService->recalculateRisk($risk);
        } catch (\Exception $e) {
            Log::warning("RiskAutoCreation: Score recalculation failed for risk_id {$risk->id}: ".$e->getMessage());
        }

        $finding->update(['risk_register_id' => $risk->id]);

        Log::info("RiskAutoCreation: Created RiskRegister id {$risk->id} from assessment_finding_id {$finding->id}");

        return $risk->fresh();
    }

    public function createRiskFromObservation(Observation $observation): RiskRegister
    {
        if ($observation->risk_register_id) {
            throw new \RuntimeException('A risk has already been created for this observation.');
        }

        $finding = $observation->finding;
        $control = $finding->frameworkControl;

        $gapText = $observation->gap ?? $finding->gap_description ?? $finding->observation ?? '';
        $riskRating = $finding->risk_rating ?? 'Medium';
        $inherentScore = $this->mapRiskRatingToScore($riskRating);

        $serialNo = RiskRegister::where('project_id', $observation->project_id)->max('serial_no');
        $nextSerial = $serialNo ? intval($serialNo) + 1 : 1;

        $risk = RiskRegister::create([
            'project_id' => $observation->project_id,
            'framework_control_id' => $finding->framework_control_id,
            'serial_no' => (string) $nextSerial,
            'asset_process_service' => Str::limit($observation->title ?: ($control->requirement_description ?? $gapText), 200),
            'risk_owner' => $observation->owner?->username,
            'risk_calculation_date' => now(),
            'threats' => [$gapText],
            'vulnerabilities' => [$observation->risk_impact ?? $gapText],
            'existing_control' => '',
            'likelihood_lh' => $this->mapRiskRatingToLikelihood($riskRating),
            'vulnerability_level_av' => $this->mapRiskRatingToVulnerability($riskRating),
            'threat_level_t' => $this->mapRiskRatingToThreat($riskRating),
            'risk_rating_avtvlh' => $inherentScore,
            'proposed_control' => $observation->recommendation ?? $finding->recommendation,
            'lifecycle_status' => 'assessed',
            'source' => 'observation',
            'assessment_finding_id' => $finding->id,
            'observation_id' => $observation->id,
            'owner_user_id' => $observation->owner_user_id,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
            'category' => 'Compliance',
        ]);

        try {
            $this->riskService->recalculateRisk($risk);
        } catch (\Exception $e) {
            Log::warning("RiskAutoCreation: Score recalculation failed for risk_id {$risk->id}: ".$e->getMessage());
        }

        $observation->update(['risk_register_id' => $risk->id]);

        Log::info("RiskAutoCreation: Created RiskRegister id {$risk->id} from observation_id {$observation->id}");

        return $risk->fresh();
    }

    private function mapRiskRatingToScore(string $rating): int
    {
        return match (strtolower($rating)) {
            'critical' => 25,
            'high' => 20,
            'medium' => 12,
            'low' => 4,
            default => 9,
        };
    }

    private function mapRiskRatingToLikelihood(string $rating): int
    {
        return match (strtolower($rating)) {
            'critical' => 5,
            'high' => 4,
            'medium' => 3,
            'low' => 2,
            default => 3,
        };
    }

    private function mapRiskRatingToVulnerability(string $rating): int
    {
        return match (strtolower($rating)) {
            'critical' => 5,
            'high' => 4,
            'medium' => 3,
            'low' => 2,
            default => 3,
        };
    }

    private function mapRiskRatingToThreat(string $rating): int
    {
        return match (strtolower($rating)) {
            'critical' => 5,
            'high' => 4,
            'medium' => 3,
            'low' => 2,
            default => 3,
        };
    }
}
