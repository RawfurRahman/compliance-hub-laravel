<?php

namespace App\Modules\RiskManagement\Support\Scoring;

use App\Modules\RiskManagement\Models\RiskRegister;

/**
 * InherentRiskInput
 *
 * Canonical, immutable DTO describing the raw inputs needed to calculate an
 * inherent (before-controls) risk score.
 *
 * It deliberately captures ONLY the dimensions the inherent engine cares about
 * plus contextual metadata (category, control references, treatment context)
 * that callers may want echoed back into explanation / snapshot data. Control
 * references and treatment context never influence the inherent score itself,
 * since inherent risk is by definition "before any treatment or control
 * effect is applied".
 */
final class InherentRiskInput
{
    /**
     * @param int $threatLevel              Threat level (T).
     * @param int $vulnerabilityLevel       Vulnerability level (AV).
     * @param array<string,int> $impactDimensions Impact dimensions keyed by name
     *                                      (e.g. confidentiality, integrity, availability).
     * @param int $likelihood               Likelihood (LH).
     * @param float $assetValue             Monetary asset value.
     * @param string|null $category         Risk category (context only).
     * @param array<int|string> $controlReferences References to mapped controls (context only).
     * @param array<string,mixed> $treatmentContext Treatment context (context only).
     * @param int|null $riskRegisterId      Originating risk register id, when available.
     */
    public function __construct(
        public readonly int $threatLevel,
        public readonly int $vulnerabilityLevel,
        public readonly array $impactDimensions,
        public readonly int $likelihood,
        public readonly float $assetValue,
        public readonly ?string $category = null,
        public readonly array $controlReferences = [],
        public readonly array $treatmentContext = [],
        public readonly ?int $riskRegisterId = null
    ) {
    }

    /**
     * Build a canonical input DTO from a loose associative array.
     *
     * Accepts both canonical workbook column names and the model's backward
     * compatible aliases so it can be fed directly from import rows, request
     * payloads, or model attributes.
     *
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            threatLevel: (int) ($data['threat_level_t'] ?? $data['threat_level'] ?? $data['threat_score'] ?? 0),
            vulnerabilityLevel: (int) ($data['vulnerability_level_av'] ?? $data['vulnerability_level'] ?? 0),
            impactDimensions: self::extractImpact($data),
            likelihood: (int) ($data['likelihood_lh'] ?? $data['likelihood'] ?? 0),
            assetValue: (float) ($data['asset_value_bdt'] ?? $data['asset_value'] ?? 0.0),
            category: $data['category'] ?? null,
            controlReferences: (array) ($data['control_references'] ?? $data['control_refs'] ?? []),
            treatmentContext: (array) ($data['treatment_context'] ?? []),
            riskRegisterId: isset($data['risk_register_id']) ? (int) $data['risk_register_id'] : (isset($data['id']) ? (int) $data['id'] : null)
        );
    }

    /**
     * Build a canonical input DTO from a RiskRegister model.
     */
    public static function fromRiskRegister(RiskRegister $risk): self
    {
        return new self(
            threatLevel: (int) $risk->threat_level_t,
            vulnerabilityLevel: (int) $risk->vulnerability_level_av,
            impactDimensions: [
                'confidentiality' => (int) $risk->impact_confidentiality,
                'integrity'       => (int) $risk->impact_integrity,
                'availability'    => (int) $risk->impact_availability,
            ],
            likelihood: (int) $risk->likelihood_lh,
            assetValue: (float) $risk->asset_value_bdt,
            category: $risk->category,
            controlReferences: $risk->controlMappings()->pluck('framework_control_id')->filter()->values()->all(),
            treatmentContext: [
                'measurement'      => $risk->measurement,
                'lifecycle_status' => $risk->lifecycle_status,
            ],
            riskRegisterId: $risk->id
        );
    }

    /**
     * The highest impact dimension value (used as the heatmap impact axis).
     */
    public function maxImpact(): int
    {
        if (empty($this->impactDimensions)) {
            return 0;
        }

        return (int) max($this->impactDimensions);
    }

    /**
     * Immutable snapshot of the raw inputs, stored verbatim on each
     * calculation record so the score can be reconstructed later.
     *
     * @return array<string,mixed>
     */
    public function toSnapshot(): array
    {
        return [
            'threat_level'        => $this->threatLevel,
            'vulnerability_level' => $this->vulnerabilityLevel,
            'impact_dimensions'   => $this->impactDimensions,
            'likelihood'          => $this->likelihood,
            'asset_value'         => $this->assetValue,
            'category'            => $this->category,
            'control_references'  => $this->controlReferences,
            'treatment_context'   => $this->treatmentContext,
        ];
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,int>
     */
    private static function extractImpact(array $data): array
    {
        if (isset($data['impact_dimensions']) && is_array($data['impact_dimensions'])) {
            return array_map('intval', $data['impact_dimensions']);
        }

        return [
            'confidentiality' => (int) ($data['impact_confidentiality'] ?? $data['confidentiality'] ?? 0),
            'integrity'       => (int) ($data['impact_integrity'] ?? $data['integrity'] ?? 0),
            'availability'    => (int) ($data['impact_availability'] ?? $data['availability'] ?? 0),
        ];
    }
}
