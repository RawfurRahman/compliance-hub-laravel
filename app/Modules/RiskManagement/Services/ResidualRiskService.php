<?php

namespace App\Modules\RiskManagement\Services;

use App\Modules\RiskManagement\Events\ResidualAppetiteCrossed;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Models\RiskResidualScore;
use App\Modules\RiskManagement\Support\Scoring\ResidualRiskFormulaConfig;
use App\Modules\RiskManagement\Support\Scoring\ResidualRiskInput;
use App\Modules\RiskManagement\Support\Scoring\ResidualRiskResult;
use Illuminate\Support\Collection;

/**
 * ResidualRiskService
 *
 * Pure domain service for RESIDUAL (after-controls) risk.
 *
 * Residual risk is the inherent baseline adjusted by a transparent, weighted
 * set of modifiers. The logic is intentionally explicit and step-by-step so it
 * is auditable and testable:
 *
 *   reduction_fraction =
 *       control_effectiveness   * w(control)
 *     + treatment_factor        * w(treatment)
 *   inflation_fraction =
 *       open_remediation        * w(open_remediation)
 *     + third_party_dependence  * w(third_party)
 *     + low_evidence            * w(low_evidence)
 *
 *   effective_reduction = clamp(reduction_fraction - inflation_fraction, 0, 1)
 *   residual_score      = round(inherent_score * (1 - effective_reduction))
 *
 * Special cases:
 *   - A waived risk keeps its inherent score (documented, not reduced).
 *   - A residual score is never allowed below a floor of 1 unless inherent is 0.
 *
 * No persistence happens in score(); scoreAndRecord() persists + fires events.
 */
class ResidualRiskService
{
    /**
     * Pure residual calculation. Deterministic for a given input + version.
     */
    public function score(
        ResidualRiskInput $input,
        ?string $formulaVersion = null,
        ?int $appetiteThreshold = null,
        ?string $previousTrendBaseline = null
    ): ResidualRiskResult {
        $config = ResidualRiskFormulaConfig::forVersion($formulaVersion);

        // Normalise factor inputs to 0-1 fractions.
        $control = $this->fraction($input->controlEffectiveness);
        $treatment = $this->fraction($input->treatmentEffectiveness) * $this->fraction($input->treatmentProgress);
        $thirdParty = $this->fraction($input->thirdPartyDependence);
        $lowEvidence = 1.0 - $this->fraction($input->evidenceConfidence);
        $openRemediation = $input->hasOpenRemediation ? 1.0 : 0.0;

        $reduction =
            $control * $config->weight('control_effectiveness')
            + $treatment * $config->weight('treatment');

        $inflation =
            $openRemediation * $config->weight('open_remediation')
            + $thirdParty * $config->weight('third_party')
            + $lowEvidence * $config->weight('low_evidence');

        $effectiveReduction = max(0.0, min(1.0, $reduction - $inflation));

        // A waived/accepted-without-mitigation risk is documented, not reduced.
        $isDocumentedOnly = $input->isWaived;
        if ($isDocumentedOnly) {
            $effectiveReduction = 0.0;
        }

        $residualScore = (int) round($input->inherentScore * (1.0 - $effectiveReduction));
        if ($input->inherentScore > 0) {
            $residualScore = max(1, $residualScore);
        }
        // Residual can never exceed inherent.
        $residualScore = min($residualScore, $input->inherentScore);

        $band = $config->bandFor($residualScore);
        $appetiteStatus = $this->appetiteStatus($residualScore, $appetiteThreshold, $config);
        $reductionPct = $input->inherentScore > 0
            ? round((1.0 - ($residualScore / $input->inherentScore)) * 100.0, $config->precision)
            : 0.0;

        $heatmap = $this->residualHeatmap($input, $effectiveReduction);
        $trend = $this->trendDirection($residualScore, $previousTrendBaseline);

        $explanation = [
            'formula_version' => $config->version,
            'weights'         => $config->weights,
            'factors'         => [
                'control'          => round($control, 4),
                'treatment'        => round($treatment, 4),
                'open_remediation' => $openRemediation,
                'third_party'      => round($thirdParty, 4),
                'low_evidence'     => round($lowEvidence, 4),
            ],
            'reduction'             => round($reduction, 4),
            'inflation'             => round($inflation, 4),
            'effective_reduction'   => round($effectiveReduction, 4),
            'documented_only'       => $isDocumentedOnly,
            'steps'                 => [
                'residual' => sprintf(
                    '%d * (1 - %.4f) = %d',
                    $input->inherentScore,
                    $effectiveReduction,
                    $residualScore
                ),
            ],
            'bands'                 => $config->bands,
            'appetite_threshold'    => $appetiteThreshold,
        ];

        return new ResidualRiskResult(
            residualScore: $residualScore,
            inherentScore: $input->inherentScore,
            severityBand: $band,
            appetiteStatus: $appetiteStatus,
            reductionPct: $reductionPct,
            heatmapCoordinates: $heatmap,
            trendDirection: $trend,
            manualOverride: false,
            formulaVersion: $config->version,
            explanation: $explanation,
            inputSnapshot: $input->toSnapshot()
        );
    }

    /**
     * Score and persist a residual record. Emits ResidualAppetiteCrossed when
     * the appetite status changes relative to the previous residual record.
     */
    public function scoreAndRecord(
        ResidualRiskInput $input,
        ?RiskRegister $risk = null,
        ?string $formulaVersion = null,
        ?int $appetiteThreshold = null,
        ?int $recordedBy = null,
        string $source = 'manual'
    ): ResidualRiskResult {
        $previous = $input->riskRegisterId
            ? $this->latestRecord($input->riskRegisterId)
            : null;

        $result = $this->score(
            $input,
            $formulaVersion,
            $appetiteThreshold,
            $previous?->residual_score !== null ? (string) $previous->residual_score : null
        );

        $this->persist($result, $source, $recordedBy);

        $this->maybeEmitAppetiteCrossing($risk, $previous?->appetite_status, $result);

        return $result;
    }

    /**
     * Apply a manual override of the residual score, with audit logging.
     */
    public function overrideScore(
        ResidualRiskInput $input,
        int $overrideScore,
        string $reason,
        ?RiskRegister $risk = null,
        ?int $recordedBy = null,
        ?int $appetiteThreshold = null,
        ?string $formulaVersion = null
    ): ResidualRiskResult {
        $config = ResidualRiskFormulaConfig::forVersion($formulaVersion);
        $previous = $input->riskRegisterId ? $this->latestRecord($input->riskRegisterId) : null;

        $overrideScore = max(0, $overrideScore);
        $band = $config->bandFor($overrideScore);
        $appetiteStatus = $this->appetiteStatus($overrideScore, $appetiteThreshold, $config);
        $reductionPct = $input->inherentScore > 0
            ? round((1.0 - ($overrideScore / $input->inherentScore)) * 100.0, $config->precision)
            : 0.0;
        $trend = $this->trendDirection(
            $overrideScore,
            $previous?->residual_score !== null ? (string) $previous->residual_score : null
        );

        $result = new ResidualRiskResult(
            residualScore: $overrideScore,
            inherentScore: $input->inherentScore,
            severityBand: $band,
            appetiteStatus: $appetiteStatus,
            reductionPct: $reductionPct,
            heatmapCoordinates: $this->residualHeatmap($input, $input->inherentScore > 0 ? 1.0 - ($overrideScore / $input->inherentScore) : 0.0),
            trendDirection: $trend,
            manualOverride: true,
            formulaVersion: $config->version,
            explanation: [
                'manual_override' => true,
                'override_reason' => $reason,
                'override_by'     => $recordedBy,
                'bands'           => $config->bands,
            ],
            inputSnapshot: $input->toSnapshot()
        );

        $this->persist($result, 'override', $recordedBy, $reason);
        $this->maybeEmitAppetiteCrossing($risk, $previous?->appetite_status, $result);

        return $result;
    }

    /**
     * Build a residual input directly from a RiskRegister, gathering control
     * effectiveness, treatment progress, remediation, acceptance, monitoring,
     * third-party dependence and evidence confidence from related records.
     */
    public function buildInputFromRisk(RiskRegister $risk): ResidualRiskInput
    {
        $inherent = (int) ($risk->computed_risk_rating ?? $risk->risk_rating_avtvlh);

        $effectivenessScores = $risk->controlMappings()->pluck('effectiveness')->filter()->all();
        $engine = new ScoringEngine();
        $controlEffectiveness = $engine->calculateCumulativeEffectiveness($effectivenessScores);

        $treatments = $risk->treatmentPlans()->get();
        $treatmentEffectiveness = $treatments->isNotEmpty()
            ? (float) $treatments->avg(fn ($t) => ($t->effectiveness_rating ?? 0) * 20) // 1-5 -> 0-100
            : 0.0;
        $treatmentProgress = $treatments->isNotEmpty()
            ? (float) $treatments->avg('progress_pct')
            : 0.0;

        $hasOpenRemediation = in_array($risk->implementation_status, ['Not Started', 'Pending', 'In Progress'], true);

        $latestAcceptance = $risk->latestAcceptance;
        $acceptanceStatus = $latestAcceptance?->status;
        $isWaived = $acceptanceStatus === 'Approved'
            || in_array($risk->lifecycle_status, ['accepted'], true);

        return new ResidualRiskInput(
            inherentScore: $inherent,
            likelihood: (int) $risk->likelihood_lh,
            impact: max(
                (int) $risk->impact_confidentiality,
                (int) $risk->impact_integrity,
                (int) $risk->impact_availability
            ),
            controlEffectiveness: $controlEffectiveness,
            treatmentEffectiveness: $treatmentEffectiveness,
            treatmentProgress: $treatmentProgress,
            hasOpenRemediation: $hasOpenRemediation,
            acceptanceStatus: $acceptanceStatus,
            isWaived: $isWaived,
            monitoringState: $risk->lifecycle_status,
            thirdPartyDependence: 0.0,
            evidenceConfidence: empty($risk->evidence_ids) ? 60.0 : 100.0,
            riskRegisterId: $risk->id
        );
    }

    /**
     * Chronological residual history for a single risk.
     *
     * @return Collection<int, RiskResidualScore>
     */
    public function historyForRisk(int $riskRegisterId, int $limit = 50): Collection
    {
        return RiskResidualScore::where('risk_register_id', $riskRegisterId)
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    public function latestRecord(int $riskRegisterId): ?RiskResidualScore
    {
        return RiskResidualScore::where('risk_register_id', $riskRegisterId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Inherent-vs-residual time series for a single risk, for dashboard charts.
     *
     * Reusable by dashboard APIs: returns chronological points plus the current
     * snapshot (latest residual record), so a chart can plot both lines and a
     * widget can show the live value without a second query.
     *
     * @return array<string,mixed>
     */
    public function trendSeries(int $riskRegisterId, int $limit = 50): array
    {
        $history = $this->historyForRisk($riskRegisterId, $limit);

        $points = $history->map(fn (RiskResidualScore $r) => [
            'recorded_at'     => optional($r->created_at)->toIso8601String(),
            'inherent_score'  => (int) $r->inherent_score,
            'residual_score'  => (int) $r->residual_score,
            'reduction_pct'   => (float) $r->reduction_pct,
            'severity_band'   => $r->severity_band,
            'appetite_status' => $r->appetite_status,
            'trend_direction' => $r->trend_direction,
            'manual_override' => (bool) $r->manual_override,
        ])->values();

        $latest = $history->last();

        return [
            'risk_register_id' => $riskRegisterId,
            'current'          => $latest ? [
                'inherent_score'  => (int) $latest->inherent_score,
                'residual_score'  => (int) $latest->residual_score,
                'reduction_pct'   => (float) $latest->reduction_pct,
                'severity_band'   => $latest->severity_band,
                'appetite_status' => $latest->appetite_status,
                'trend_direction' => $latest->trend_direction,
            ] : null,
            'points'           => $points,
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Internal helpers                                                    */
    /* ------------------------------------------------------------------ */

    private function persist(
        ResidualRiskResult $result,
        string $source,
        ?int $recordedBy,
        ?string $overrideReason = null
    ): void {
        RiskResidualScore::create([
            'risk_register_id'   => $result->inputSnapshot['risk_register_id'] ?? null,
            'inherent_score'     => $result->inherentScore,
            'residual_score'     => $result->residualScore,
            'severity_band'      => $result->severityBand,
            'appetite_status'    => $result->appetiteStatus,
            'reduction_pct'      => $result->reductionPct,
            'heatmap_likelihood' => $result->heatmapCoordinates['likelihood'],
            'heatmap_impact'     => $result->heatmapCoordinates['impact'],
            'trend_direction'    => $result->trendDirection,
            'manual_override'    => $result->manualOverride,
            'override_reason'    => $overrideReason,
            'formula_version'    => $result->formulaVersion,
            'input_snapshot'     => $result->inputSnapshot,
            'explanation'        => $result->explanation,
            'source'             => $source,
            'recorded_by'        => $recordedBy,
        ]);
    }

    private function maybeEmitAppetiteCrossing(
        ?RiskRegister $risk,
        ?string $previousStatus,
        ResidualRiskResult $result
    ): void {
        if (!$risk) {
            return;
        }

        $previousStatus ??= 'unknown';
        if ($previousStatus !== 'unknown' && $previousStatus !== $result->appetiteStatus) {
            event(new ResidualAppetiteCrossed(
                $risk,
                $previousStatus,
                $result->appetiteStatus,
                $result->residualScore
            ));
        }
    }

    private function appetiteStatus(int $score, ?int $threshold, ResidualRiskFormulaConfig $config): string
    {
        $threshold ??= $config->bands['High'] ?? null;

        if ($threshold === null) {
            return 'unknown';
        }

        return $score >= $threshold ? 'exceeds_appetite' : 'within_appetite';
    }

    /**
     * Residual heatmap position. Likelihood is reduced proportionally to the
     * effective reduction (controls mainly lower likelihood); impact is kept as
     * the inherent impact axis (controls rarely change worst-case impact).
     *
     * @return array{likelihood:int,impact:int}
     */
    private function residualHeatmap(ResidualRiskInput $input, float $effectiveReduction): array
    {
        $residualLikelihood = (int) max(1, round($input->likelihood * (1.0 - $effectiveReduction)));
        if ($input->likelihood <= 0) {
            $residualLikelihood = 0;
        }

        return [
            'likelihood' => $residualLikelihood,
            'impact'     => $input->impact,
        ];
    }

    private function trendDirection(int $residualScore, ?string $previousBaseline): string
    {
        if ($previousBaseline === null) {
            return 'stable';
        }

        $previous = (int) $previousBaseline;
        if ($residualScore < $previous) {
            return 'improving';
        }
        if ($residualScore > $previous) {
            return 'worsening';
        }

        return 'stable';
    }

    private function fraction(float $percent): float
    {
        return max(0.0, min(1.0, $percent / 100.0));
    }
}
