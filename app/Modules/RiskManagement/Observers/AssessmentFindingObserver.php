<?php

namespace App\Modules\RiskManagement\Observers;

use App\Models\AssessmentFinding;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Models\RiskControlMapping;
use App\Services\Dashboard\DashboardCacheKey;

class AssessmentFindingObserver
{
    private const RATING_MAP = [
        'High' => ['threat_level_t' => 4, 'vulnerability_level_av' => 4, 'likelihood_lh' => 4],
        'Medium' => ['threat_level_t' => 3, 'vulnerability_level_av' => 3, 'likelihood_lh' => 3],
        'Low' => ['threat_level_t' => 2, 'vulnerability_level_av' => 2, 'likelihood_lh' => 2],
    ];

    public function saved(AssessmentFinding $finding): void
    {
        if ($finding->is_compliant === false && $finding->risk_rating !== 'None') {
            $this->createOrUpdateRiskFromFinding($finding);
        }

        if ($finding->wasChanged('is_compliant')
            && $finding->is_compliant === true
            && $finding->getOriginal('is_compliant') === false
            && $finding->risk_register_id
        ) {
            $this->closeAssociatedRisk($finding);
        }

        if ($finding->wasChanged(['is_compliant', 'status', 'risk_rating', 'due_date'])) {
            DashboardCacheKey::invalidateDomain('kpi');
            DashboardCacheKey::invalidateDomain('heatmap');
            DashboardCacheKey::invalidateDomain('risk_ranking');
            DashboardCacheKey::invalidateDomain('compliance_scorecard');
            DashboardCacheKey::invalidateDomain('remediation_trend');
        }
    }

    private function createOrUpdateRiskFromFinding(AssessmentFinding $finding): void
    {
        $projectAssessment = $finding->projectAssessment;
        if (!$projectAssessment) {
            return;
        }

        $rating = $finding->risk_rating;
        $scores = self::RATING_MAP[$rating] ?? self::RATING_MAP['Medium'];

        $projectId = $projectAssessment->project_id;

        $risk = RiskRegister::updateOrCreate(
            ['assessment_finding_id' => $finding->id],
            $this->buildRiskData($finding, $projectId, $scores)
        );

        $this->ensureControlMapping($risk, $finding);

        if (!$finding->risk_register_id) {
            $finding->risk_register_id = $risk->id;
            $finding->saveQuietly();
        }
    }

    private function buildRiskData(AssessmentFinding $finding, int $projectId, array $scores): array
    {
        $tv = $scores['threat_level_t'] + $scores['vulnerability_level_av'];
        $inherent = $scores['vulnerability_level_av'] * $tv * $scores['likelihood_lh'];

        return [
            'project_id' => $projectId,
            'framework_control_id' => $finding->framework_control_id,
            'serial_no' => app(\App\Modules\RiskManagement\Services\RiskRegisterService::class)
                ->generateRiskId($projectId),
            'asset_process_service' => $finding->observation ?? ($finding->frameworkControl?->domain ?? 'Assessment Finding'),
            'risk_owner' => 'Assessment Auto-Created',
            'risk_calculation_date' => now()->toDateString(),
            'asset_value_bdt' => 0,
            'threats' => [$finding->impact ?? 'Non-compliance risk'],
            'threat_level_t' => $scores['threat_level_t'],
            'vulnerabilities' => [$finding->gap_description ?? 'Non-compliant control'],
            'impact_confidentiality' => $scores['vulnerability_level_av'],
            'impact_integrity' => $scores['vulnerability_level_av'],
            'impact_availability' => $scores['vulnerability_level_av'],
            'existing_control' => $finding->observation ?? '',
            'vulnerability_level_av' => $scores['vulnerability_level_av'],
            'tv_t_av' => $tv,
            'likelihood_lh' => $scores['likelihood_lh'],
            'risk_rating_avtvlh' => $inherent,
            'measurement' => 'Not Accepted',
            'proposed_control' => $finding->recommendation,
            'implementation_status' => 'Not Started',
            'residual_tv' => 1,
            'residual_lh' => 1,
            'residual_rating' => 1,
            'category' => 'Compliance',
            'department' => 'Compliance Department',
            'source' => 'assessment',
            'assessment_finding_id' => $finding->id,
        ];
    }

    private function ensureControlMapping(RiskRegister $risk, AssessmentFinding $finding): void
    {
        if (!$finding->framework_control_id) {
            return;
        }

        RiskControlMapping::firstOrCreate(
            [
                'risk_register_id' => $risk->id,
                'framework_control_id' => $finding->framework_control_id,
            ],
            [
                'effectiveness' => 100,
                'control_type' => 'Preventive',
                'mapping_status' => 'confirmed',
                'confidence_score' => 100.0,
                'notes' => 'Auto-mapped from non-compliant assessment finding',
            ]
        );
    }

    private function closeAssociatedRisk(AssessmentFinding $finding): void
    {
        $risk = RiskRegister::find($finding->risk_register_id);
        if (!$risk) {
            return;
        }

        $risk->update([
            'implementation_status' => 'Completed',
            'measurement' => 'Accepted',
            'residual_tv' => 1,
            'residual_lh' => 1,
            'residual_rating' => 1,
        ]);
    }
}
