<?php

namespace App\Modules\RiskManagement\Support\Scoring;

/**
 * ResidualRiskFormulaConfig
 *
 * Versioned, immutable description of how residual (after-controls) risk is
 * derived from an inherent score, plus the banding / appetite thresholds.
 *
 * Residual risk is NOT a naive copy of inherent: it applies a transparent,
 * weighted set of reduction and inflation modifiers (control effectiveness,
 * treatment progress, remediation status, monitoring, third-party dependence,
 * evidence confidence). Each version freezes its weights so historical residual
 * records remain reproducible after the rules change.
 */
final class ResidualRiskFormulaConfig
{
    /**
     * @param string $version            Formula version identifier (e.g. "v1").
     * @param array<string,int> $bands   Map of band name => inclusive lower bound, ordered high to low.
     * @param array<string,float> $weights Modifier weights (0-1) controlling each factor's influence.
     * @param int $maxScore              Maximum theoretically possible score (for normalisation).
     * @param int $precision             Decimal precision for derived values.
     */
    public function __construct(
        public readonly string $version,
        public readonly array $bands,
        public readonly array $weights,
        public readonly int $maxScore,
        public readonly int $precision
    ) {
    }

    public static function forVersion(?string $version = null): self
    {
        $active = (string) config('rmm.residual.active_version', 'v1');
        $version = $version ?: $active;

        $formulas = (array) config('rmm.residual.formulas', []);
        $definition = $formulas[$version] ?? $formulas[$active] ?? self::v1Defaults();
        $defaults = self::v1Defaults();

        return new self(
            version: $version,
            bands: $definition['bands'] ?? $defaults['bands'],
            weights: ($definition['weights'] ?? []) + $defaults['weights'],
            maxScore: (int) ($definition['max_score'] ?? $defaults['max_score']),
            precision: (int) ($definition['precision'] ?? config('rmm.precision', 2))
        );
    }

    /**
     * @return array<string,mixed>
     */
    public static function v1Defaults(): array
    {
        return [
            'bands' => [
                'Critical' => 128,
                'High'     => 84,
                'Medium'   => 54,
                'Low'      => 0,
            ],
            // All weights are 0-1. They scale how much each factor can move the
            // residual score relative to the inherent baseline.
            'weights' => [
                // Reduction factors (lower the score)
                'control_effectiveness' => 0.50, // mapped/existing control effectiveness
                'treatment'             => 0.25, // proposed treatment progress * effectiveness
                // Inflation factors (raise/keep the score when risk is only documented)
                'open_remediation'      => 0.15, // open/overdue remediation dampens reduction
                'third_party'           => 0.10, // external dependence adds residual risk
                'low_evidence'          => 0.15, // weak evidence confidence dampens reduction
            ],
            'max_score' => 250,
            'precision' => 2,
        ];
    }

    public function bandFor(int $score): string
    {
        foreach ($this->bands as $band => $threshold) {
            if ($score >= $threshold) {
                return $band;
            }
        }

        return array_key_last($this->bands) ?: 'Low';
    }

    public function weight(string $key): float
    {
        return (float) ($this->weights[$key] ?? 0.0);
    }
}
