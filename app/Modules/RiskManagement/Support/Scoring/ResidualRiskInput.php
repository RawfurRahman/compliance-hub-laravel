<?php

namespace App\Modules\RiskManagement\Support\Scoring;

/**
 * ResidualRiskInput
 *
 * Canonical, immutable DTO for the residual (after-controls) engine.
 *
 * It consumes the inherent score and the real-world factors that determine
 * whether a risk is genuinely being reduced or merely documented:
 *   - existing control effectiveness (0-100)
 *   - proposed treatment effectiveness (0-100) and progress (0-100)
 *   - open remediation status
 *   - acceptance / waiver status
 *   - monitoring state
 *   - third-party dependence
 *   - evidence confidence (0-100)
 */
final class ResidualRiskInput
{
    /**
     * @param int $inherentScore             Inherent (before-controls) score.
     * @param int $likelihood                Inherent likelihood (for heatmap baseline).
     * @param int $impact                    Inherent max impact (for heatmap baseline).
     * @param float $controlEffectiveness    Cumulative existing-control effectiveness (0-100).
     * @param float $treatmentEffectiveness  Proposed treatment effectiveness (0-100).
     * @param float $treatmentProgress       Treatment completion progress (0-100).
     * @param bool $hasOpenRemediation       Whether remediation is still open/overdue.
     * @param string|null $acceptanceStatus  Approved | Pending | Rejected | null.
     * @param bool $isWaived                 Whether an active waiver/exception applies.
     * @param string|null $monitoringState   e.g. monitoring | escalated | null.
     * @param float $thirdPartyDependence    External dependence factor (0-100).
     * @param float $evidenceConfidence      Evidence confidence (0-100).
     * @param int|null $riskRegisterId       Originating risk register id.
     */
    public function __construct(
        public readonly int $inherentScore,
        public readonly int $likelihood,
        public readonly int $impact,
        public readonly float $controlEffectiveness = 0.0,
        public readonly float $treatmentEffectiveness = 0.0,
        public readonly float $treatmentProgress = 0.0,
        public readonly bool $hasOpenRemediation = false,
        public readonly ?string $acceptanceStatus = null,
        public readonly bool $isWaived = false,
        public readonly ?string $monitoringState = null,
        public readonly float $thirdPartyDependence = 0.0,
        public readonly float $evidenceConfidence = 100.0,
        public readonly ?int $riskRegisterId = null
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            inherentScore: (int) ($data['inherent_score'] ?? 0),
            likelihood: (int) ($data['likelihood'] ?? 0),
            impact: (int) ($data['impact'] ?? 0),
            controlEffectiveness: (float) ($data['control_effectiveness'] ?? 0.0),
            treatmentEffectiveness: (float) ($data['treatment_effectiveness'] ?? 0.0),
            treatmentProgress: (float) ($data['treatment_progress'] ?? 0.0),
            hasOpenRemediation: (bool) ($data['has_open_remediation'] ?? false),
            acceptanceStatus: $data['acceptance_status'] ?? null,
            isWaived: (bool) ($data['is_waived'] ?? false),
            monitoringState: $data['monitoring_state'] ?? null,
            thirdPartyDependence: (float) ($data['third_party_dependence'] ?? 0.0),
            evidenceConfidence: (float) ($data['evidence_confidence'] ?? 100.0),
            riskRegisterId: isset($data['risk_register_id']) ? (int) $data['risk_register_id'] : null
        );
    }

    /**
     * Verbatim snapshot of the residual inputs, stored on each calculation
     * record so the residual score can be reconstructed later.
     *
     * @return array<string,mixed>
     */
    public function toSnapshot(): array
    {
        return [
            'inherent_score'          => $this->inherentScore,
            'likelihood'              => $this->likelihood,
            'impact'                  => $this->impact,
            'control_effectiveness'   => $this->controlEffectiveness,
            'treatment_effectiveness' => $this->treatmentEffectiveness,
            'treatment_progress'      => $this->treatmentProgress,
            'has_open_remediation'    => $this->hasOpenRemediation,
            'acceptance_status'       => $this->acceptanceStatus,
            'is_waived'               => $this->isWaived,
            'monitoring_state'        => $this->monitoringState,
            'third_party_dependence'  => $this->thirdPartyDependence,
            'evidence_confidence'     => $this->evidenceConfidence,
        ];
    }
}
