<?php

namespace App\Modules\RiskManagement\Support\Scoring;

/**
 * InherentRiskResult
 *
 * Immutable value object holding the full output of an inherent-risk
 * calculation: the score, severity band, appetite status, heatmap
 * coordinates, ranking weight, and the explanation metadata required to
 * reconstruct and audit the calculation later.
 */
final class InherentRiskResult
{
    /**
     * @param int $tvScore                  Threat + Vulnerability sub-score.
     * @param int $inherentScore            Final inherent score (before controls).
     * @param string $severityBand          Critical | High | Medium | Low.
     * @param string $appetiteStatus        within_appetite | exceeds_appetite | unknown.
     * @param array{likelihood:int,impact:int} $heatmapCoordinates Heatmap axes.
     * @param float $riskRanking            Normalised 0-100 ranking weight (higher = riskier).
     * @param string $formulaVersion        Formula version used.
     * @param array<string,mixed> $explanation Human/audit readable derivation metadata.
     * @param array<string,mixed> $inputSnapshot Verbatim copy of the raw inputs.
     */
    public function __construct(
        public readonly int $tvScore,
        public readonly int $inherentScore,
        public readonly string $severityBand,
        public readonly string $appetiteStatus,
        public readonly array $heatmapCoordinates,
        public readonly float $riskRanking,
        public readonly string $formulaVersion,
        public readonly array $explanation,
        public readonly array $inputSnapshot
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'tv_score'             => $this->tvScore,
            'inherent_score'       => $this->inherentScore,
            'severity_band'        => $this->severityBand,
            'appetite_status'      => $this->appetiteStatus,
            'heatmap_coordinates'  => $this->heatmapCoordinates,
            'risk_ranking'         => $this->riskRanking,
            'formula_version'      => $this->formulaVersion,
            'explanation'          => $this->explanation,
            'input_snapshot'       => $this->inputSnapshot,
        ];
    }
}
