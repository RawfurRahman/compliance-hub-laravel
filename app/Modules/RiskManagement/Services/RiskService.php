<?php

namespace App\Modules\RiskManagement\Services;

use App\Modules\RiskManagement\Models\RiskRegister;
use Illuminate\Support\Facades\Auth;

class RiskService
{
    private ScoringEngine $engine;

    public function __construct()
    {
        $this->engine = new ScoringEngine();
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

        // Gather all mapped control effectiveness values
        $mappings = $risk->controlMappings()->pluck('effectiveness')->toArray();

        if (empty($mappings)) {
            // Default to manual workbook residual inputs for reconciliation of manual formulas
            $resTv = intval($risk->residual_tv ?: 1);
            $resLh = intval($risk->residual_lh ?: 1);
            $computedResidual = $resTv * $resLh;
            $cumulativeEffectiveness = 0.0;
            $residualTvRecord = $resTv;
            $residualLhRecord = $resLh;
        } else {
            $cumulativeEffectiveness = $this->engine->calculateCumulativeEffectiveness($mappings);
            // Compute Residual Scores using cumulative control reduction
            $residualInputs = $this->engine->calculateResidualInputs($computedTv, $likelihood, $cumulativeEffectiveness);
            $computedResidual = $residualInputs['residual_tv'] * $residualInputs['residual_lh'];
            $residualTvRecord = $residualInputs['residual_tv'];
            $residualLhRecord = $residualInputs['residual_lh'];
        }

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

        // Record history log entry
        $risk->scoresHistory()->create([
            'tv_score' => $computedTv,
            'lh_score' => $likelihood,
            'rating_score' => $computedInherent,
            'threat_level_t' => $threat,
            'vulnerability_level_av' => $vuln,
            'control_effectiveness' => $cumulativeEffectiveness,
            'formula_version' => config('rmm.formula_version', 'v1'),
            'residual_tv' => $residualTvRecord,
            'residual_lh' => $residualLhRecord,
            'residual_rating' => $computedResidual,
            'recorded_by' => Auth::id() ?? $risk->updated_by ?? 1,
        ]);
    }

    /**
     * Update the heatmap aggregates snapshot.
     */
    public function updateHeatmap(int $projectId): void
    {
        $calc = new RiskCalculationService();
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
            if ($treatmentPlans->isNotEmpty() && $treatmentPlans->every(fn($p) => $p->status === 'completed')) {
                $risk->lifecycle_status = 'monitoring';
            }
        }
    }
}
