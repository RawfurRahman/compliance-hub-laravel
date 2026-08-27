<?php

namespace App\Modules\RiskManagement\Services;

use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Support\Scoring\InherentRiskInput;
use Illuminate\Support\Facades\Auth;

class RiskService
{
    private ScoringEngine $engine;

    private RiskScoringService $scoringService;

    private ResidualRiskService $residualService;

    public function __construct()
    {
        $this->engine = new ScoringEngine;
        $this->scoringService = new RiskScoringService;
        $this->residualService = new ResidualRiskService;
    }

    /**
     * Recalculate computed scores for a single risk register entry and log its history.
     */
    public function recalculateRisk(RiskRegister $risk): void
    {
        $threat = intval($risk->threat_level_t);
        $vuln = intval($risk->vulnerability_level_av);
        $likelihood = intval($risk->likelihood_lh);

        // Compute Inherent Scores
        $computedTv = $this->engine->calculateTvScore($threat, $vuln);
        $computedInherent = $this->engine->calculateInherentScore($vuln, $computedTv, $likelihood);

        // Cumulative control effectiveness is aggregated here purely for the
        // legacy history log; residual scoring itself is delegated to the
        // single authoritative implementation (ResidualRiskService).
        $cumulativeEffectiveness = $this->engine->calculateCumulativeEffectiveness(
            $risk->controlMappings()->pluck('effectiveness')->toArray()
        );

        // Make the freshly computed inherent available to the residual engine
        // before the reconciliation columns are persisted.
        $risk->computed_risk_rating = $computedInherent;

        // Residual is always derived from ResidualRiskService (weighted,
        // versioned reduction model). scoreAndRecord() persists the canonical
        // residual history row + fires events, and returns the authoritative
        // score that is mirrored into the reconciliation column and legacy log.
        $residualResult = $this->residualService->scoreAndRecord(
            $this->residualService->buildInputFromRisk($risk),
            risk: $risk,
            recordedBy: Auth::id() ?? $risk->updated_by,
            source: 'trigger'
        );
        $computedResidual = $residualResult->residualScore;

        // Save computed scores to reconciliation columns
        $risk->update([
            'computed_tv' => $computedTv,
            'computed_risk_rating' => $computedInherent,
            'computed_residual_rating' => $computedResidual,
        ]);

        // Compute and store exposure value on the risk register
        $exposureValue = $this->engine->calculateExposureValue(
            (float) $risk->asset_value_bdt,
            $computedInherent
        );
        $risk->exposure_value = $exposureValue;

        // Auto-advance lifecycle if applicable
        $this->autoAdvanceLifecycle($risk);

        $risk->saveQuietly();

        // Record a dedicated inherent (before-controls) score for this edit.
        $this->scoringService->scoreAndRecord(
            InherentRiskInput::fromRiskRegister($risk),
            recordedBy: Auth::id() ?? $risk->updated_by,
            source: 'manual'
        );

        // Record history log entry. residual_tv/residual_lh are the manual
        // workbook inputs; residual_rating is the authoritative
        // ResidualRiskService output (no longer residual_tv × residual_lh).
        $risk->scoresHistory()->create([
            'tv_score' => $computedTv,
            'lh_score' => $likelihood,
            'rating_score' => $computedInherent,
            'threat_level_t' => $threat,
            'vulnerability_level_av' => $vuln,
            'control_effectiveness' => $cumulativeEffectiveness,
            'formula_version' => config('rmm.formula_version', 'v1'),
            'residual_tv' => intval($risk->residual_tv ?: 1),
            'residual_lh' => intval($risk->residual_lh ?: 1),
            'residual_rating' => $computedResidual,
            'recorded_by' => Auth::id() ?? $risk->updated_by ?? 1,
        ]);
    }

    /**
     * Update the heatmap aggregates snapshot.
     */
    public function updateHeatmap(int $projectId): void
    {
        $calc = new RiskCalculationService;
        $registerService = new RiskRegisterService($calc);

        $registerService->snapshotHeatmap($projectId, 'inherent');
        $registerService->snapshotHeatmap($projectId, 'residual');
    }

    /**
     * Auto-advance lifecycle status based on business rules.
     */
    public function autoAdvanceLifecycle(RiskRegister $risk): void
    {
        if ($risk->lifecycle_status === 'draft' && $risk->computed_tv !== null) {
            $risk->lifecycle_status = 'assessed';

            return;
        }

        if ($risk->lifecycle_status === 'assessed' && $risk->measurement === 'Accepted') {
            $latestAcceptance = $risk->latestAcceptance;
            if ($latestAcceptance && $latestAcceptance->status === 'Approved') {
                $risk->lifecycle_status = 'accepted';

                return;
            }
        }

        if ($risk->lifecycle_status === 'treated') {
            $treatmentPlans = $risk->treatmentPlans;
            if ($treatmentPlans->isNotEmpty() && $treatmentPlans->every(fn ($p) => $p->status === 'completed')) {
                $risk->lifecycle_status = 'monitoring';
            }
        }
    }
}
