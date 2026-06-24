<?php

namespace App\Modules\RiskManagement\Support\Scoring;

/**
 * InherentRiskFormulaConfig
 *
 * Versioned, immutable description of the inherent-risk formula and its
 * banding / appetite thresholds.
 *
 * The whole point of this object is reproducibility: every scoring record
 * stores the `version` used, and a config for any historical version can be
 * rebuilt from the `rmm.inherent.formulas` config map. That means a score
 * calculated under formula v1 can always be recomputed identically later,
 * even after the business rules change in v2+.
 *
 * This is pure domain configuration. It performs no I/O and has no framework
 * dependencies beyond Laravel's global config() helper used in the factory.
 */
final class InherentRiskFormulaConfig
{
    /**
     * @param string $version              Formula version identifier (e.g. "v1").
     * @param string $tvExpression          Human readable TV formula, for explanation metadata.
     * @param string $inherentExpression    Human readable inherent formula, for explanation metadata.
     * @param array<string,int> $bands      Map of band name => inclusive lower bound, ordered high to low.
     * @param int $maxScore                 Maximum theoretically possible inherent score (for normalisation).
     * @param int $precision                Decimal precision used for derived/normalised values.
     */
    public function __construct(
        public readonly string $version,
        public readonly string $tvExpression,
        public readonly string $inherentExpression,
        public readonly array $bands,
        public readonly int $maxScore,
        public readonly int $precision
    ) {
    }

    /**
     * Build a formula config for a given version from application config.
     *
     * Falls back to the active version when $version is null, and to the
     * hard-coded v1 defaults when the requested version is unknown so that
     * historical records never fail to resolve a config.
     */
    public static function forVersion(?string $version = null): self
    {
        $active = (string) config('rmm.inherent.active_version', config('rmm.formula_version', 'v1'));
        $version = $version ?: $active;

        $formulas = (array) config('rmm.inherent.formulas', []);
        $definition = $formulas[$version] ?? $formulas[$active] ?? self::v1Defaults();

        return new self(
            version: $version,
            tvExpression: $definition['tv_expression'] ?? 'threat_level + vulnerability_level',
            inherentExpression: $definition['inherent_expression'] ?? 'vulnerability_level * tv * likelihood',
            bands: $definition['bands'] ?? self::v1Defaults()['bands'],
            maxScore: (int) ($definition['max_score'] ?? self::v1Defaults()['max_score']),
            precision: (int) ($definition['precision'] ?? config('rmm.precision', 2))
        );
    }

    /**
     * Canonical workbook (v1) defaults, used as the ultimate fallback.
     *
     * @return array<string,mixed>
     */
    public static function v1Defaults(): array
    {
        return [
            'tv_expression'       => 'threat_level + vulnerability_level',
            'inherent_expression' => 'vulnerability_level * tv * likelihood',
            'bands'               => [
                'Critical' => 128,
                'High'     => 84,
                'Medium'   => 54,
                'Low'      => 0,
            ],
            // 5 (vuln) * (5 + 5) (tv) * 5 (likelihood) = 250
            'max_score'           => 250,
            'precision'           => 2,
        ];
    }

    /**
     * Resolve a numeric score into its severity band name.
     */
    public function bandFor(int $score): string
    {
        foreach ($this->bands as $band => $threshold) {
            if ($score >= $threshold) {
                return $band;
            }
        }

        // Bands are ordered high to low with a 0 floor, so this is unreachable
        // for non-negative scores; kept as a defensive default.
        return array_key_last($this->bands) ?: 'Low';
    }
}
