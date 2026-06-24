<?php

namespace App\Modules\RiskManagement\Support\Scoring;

/**
 * ResidualRiskResult
 *
 * Immutable value object holding the full output of a residual-risk
 * calculation.
 */
final class ResidualRiskResult
{
    /**
     * @param int $residualScore             Residual score (after controls).
     * @param int $inherentScore             Inherent baseline it was derived from.
     * @param string $severityBand           Critical | High | Medium | Low.
     * @param string $appetiteStatus         within_appetite | exceeds_appetite | unknown.
     * @param float $reductionPct            % reduction from inherent (0-100).
     * @param array{likelihood:int,impact:int} $heatmapCoordinates Residual heatmap axes.
     * @param string $trendDirection         improving | worsening | stable.
     * @param bool $manualOverride           Whether the score was manually overridden.
     * @param string $formulaVersion         Formula version used.
     * @param array<string,mixed> $explanation Derivation / audit metadata.
     * @param array<string,mixed> $inputSnapshot Verbatim copy of the inputs.
     */
    public function __construct(
        public readonly int $residualScore,
        public readonly int $inherentScore,
        public readonly string $severityBand,
        public readonly string $appetiteStatus,
        public readonly float $reductionPct,
        public readonly array $heatmapCoordinates,
        public readonly string $trendDirection,
        public readonly bool $manualOverride,
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
            'residual_score'      => $this->residualScore,
            'inherent_score'      => $this->inherentScore,
            'severity_band'       => $this->severityBand,
            'appetite_status'     => $this->appetiteStatus,
            'reduction_pct'       => $this->reductionPct,
            'heatmap_coordinates' => $this->heatmapCoordinates,
            'trend_direction'     => $this->trendDirection,
            'manual_override'     => $this->manualOverride,
            'formula_version'     => $this->formulaVersion,
            'explanation'         => $this->explanation,
            'input_snapshot'      => $this->inputSnapshot,
        ];
    }
}
