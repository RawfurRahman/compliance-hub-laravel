<?php

namespace App\Modules\RiskManagement\Services;

use App\Modules\RiskManagement\Models\RiskInherentScore;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Support\Scoring\InherentRiskFormulaConfig;
use App\Modules\RiskManagement\Support\Scoring\InherentRiskInput;
use App\Modules\RiskManagement\Support\Scoring\InherentRiskResult;

/**
 * RiskScoringService
 *
 * Pure domain service responsible for INHERENT (before-controls) risk only.
 *
 * Design goals (Prompt 4):
 *   - Explicit, traceable, deterministic scoring logic kept out of controllers.
 *   - Configurable via a versioned formula config so historical records remain
 *     reproducible even after business rules change.
 *   - Preserves the imported raw inputs separately as a snapshot.
 *   - Emits enough metadata (explanation + input snapshot + formula version)
 *     to reconstruct any calculation later.
 *   - Callable from import jobs, manual edits and snapshot jobs.
 *
 * Canonical workbook formula (v1):
 *   tv             = threat_level + vulnerability_level
 *   inherent_score = vulnerability_level * tv * likelihood
 */
class RiskScoringService
{
    /**
     * Score a canonical inherent-risk input.
     *
     * Pure: given the same input, formula version and appetite threshold, the
     * result is always identical. No persistence happens here.
     *
     * @param InherentRiskInput $input
     * @param string|null $formulaVersion Pin a specific formula version (defaults to active).
     * @param int|null $appetiteThreshold Score at/above which the risk exceeds appetite.
     */
    public function score(
        InherentRiskInput $input,
        ?string $formulaVersion = null,
        ?int $appetiteThreshold = null
    ): InherentRiskResult {
        $config = InherentRiskFormulaConfig::forVersion($formulaVersion);

        $tvScore = $this->tvScore($input->threatLevel, $input->vulnerabilityLevel);
        $inherentScore = $this->inherentScore($input->vulnerabilityLevel, $tvScore, $input->likelihood);

        $band = $config->bandFor($inherentScore);
        $appetiteStatus = $this->appetiteStatus($inherentScore, $appetiteThreshold, $config);
        $heatmap = $this->heatmapCoordinates($input);
        $ranking = $this->riskRanking($inherentScore, $config);

        $explanation = [
            'formula_version'     => $config->version,
            'tv_expression'       => $config->tvExpression,
            'inherent_expression' => $config->inherentExpression,
            'steps'               => [
                'tv'       => sprintf('%d + %d = %d', $input->threatLevel, $input->vulnerabilityLevel, $tvScore),
                'inherent' => sprintf('%d * %d * %d = %d', $input->vulnerabilityLevel, $tvScore, $input->likelihood, $inherentScore),
            ],
            'bands'               => $config->bands,
            'appetite_threshold'  => $appetiteThreshold,
            'max_score'           => $config->maxScore,
        ];

        return new InherentRiskResult(
            tvScore: $tvScore,
            inherentScore: $inherentScore,
            severityBand: $band,
            appetiteStatus: $appetiteStatus,
            heatmapCoordinates: $heatmap,
            riskRanking: $ranking,
            formulaVersion: $config->version,
            explanation: $explanation,
            inputSnapshot: $input->toSnapshot()
        );
    }

    /**
     * Score an input and persist the result to the inherent score history
     * table. Returns the result value object.
     *
     * Safe to call from import jobs, manual edits and snapshot jobs. Persisting
     * the formula version + input snapshot is what guarantees reproducibility.
     */
    public function scoreAndRecord(
        InherentRiskInput $input,
        ?string $formulaVersion = null,
        ?int $appetiteThreshold = null,
        ?int $recordedBy = null,
        string $source = 'manual'
    ): InherentRiskResult {
        $result = $this->score($input, $formulaVersion, $appetiteThreshold);

        RiskInherentScore::create([
            'risk_register_id'    => $input->riskRegisterId,
            'tv_score'            => $result->tvScore,
            'inherent_score'      => $result->inherentScore,
            'severity_band'       => $result->severityBand,
            'appetite_status'     => $result->appetiteStatus,
            'heatmap_likelihood'  => $result->heatmapCoordinates['likelihood'],
            'heatmap_impact'      => $result->heatmapCoordinates['impact'],
            'risk_ranking'        => $result->riskRanking,
            'formula_version'     => $result->formulaVersion,
            'input_snapshot'      => $result->inputSnapshot,
            'explanation'         => $result->explanation,
            'source'              => $source,
            'recorded_by'         => $recordedBy,
        ]);

        return $result;
    }

    /**
     * Convenience wrapper to score directly from a RiskRegister model.
     */
    public function scoreRiskRegister(
        RiskRegister $risk,
        ?string $formulaVersion = null,
        ?int $appetiteThreshold = null
    ): InherentRiskResult {
        return $this->score(
            InherentRiskInput::fromRiskRegister($risk),
            $formulaVersion,
            $appetiteThreshold
        );
    }

    /**
     * Recompute a historical inherent score record from its stored snapshot
     * and formula version, proving reproducibility.
     */
    public function recomputeFromRecord(RiskInherentScore $record): InherentRiskResult
    {
        $input = InherentRiskInput::fromArray(
            ($record->input_snapshot ?? []) + ['risk_register_id' => $record->risk_register_id]
        );

        return $this->score($input, $record->formula_version);
    }

    /* ------------------------------------------------------------------ */
    /* Primitive, deterministic formula operations                         */
    /* ------------------------------------------------------------------ */

    /** TV (Threat + Vulnerability) sub-score. */
    public function tvScore(int $threatLevel, int $vulnerabilityLevel): int
    {
        return $threatLevel + $vulnerabilityLevel;
    }

    /** Inherent risk score: vulnerability_level * tv * likelihood. */
    public function inherentScore(int $vulnerabilityLevel, int $tvScore, int $likelihood): int
    {
        return $vulnerabilityLevel * $tvScore * $likelihood;
    }

    /**
     * Determine appetite status relative to a threshold.
     *
     * When no explicit threshold is provided, the boundary of the highest
     * "acceptable" band (the High band lower bound) is used as the default
     * appetite line, mirroring RiskAppetite::exceedsAppetite().
     */
    private function appetiteStatus(int $score, ?int $threshold, InherentRiskFormulaConfig $config): string
    {
        $threshold ??= $config->bands['High'] ?? null;

        if ($threshold === null) {
            return 'unknown';
        }

        return $score >= $threshold ? 'exceeds_appetite' : 'within_appetite';
    }

    /**
     * Heatmap coordinates: likelihood axis vs. max impact dimension axis.
     *
     * @return array{likelihood:int,impact:int}
     */
    private function heatmapCoordinates(InherentRiskInput $input): array
    {
        return [
            'likelihood' => $input->likelihood,
            'impact'     => $input->maxImpact(),
        ];
    }

    /**
     * Normalised 0-100 ranking weight; higher means riskier. Deterministic and
     * stable for ordering risks on dashboards.
     */
    private function riskRanking(int $inherentScore, InherentRiskFormulaConfig $config): float
    {
        $max = max(1, $config->maxScore);
        $normalised = min(1.0, $inherentScore / $max) * 100.0;

        return round($normalised, $config->precision);
    }
}
