<?php

namespace Tests\Unit;

use App\Modules\RiskManagement\Services\ResidualRiskService;
use App\Modules\RiskManagement\Support\Scoring\ResidualRiskInput;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit tests for the residual risk engine.
 *
 * These do not touch the database; they assert the transparent reduction
 * logic, banding, reduction %, appetite status, trend transitions, waiver
 * behaviour and determinism.
 */
class ResidualRiskServiceTest extends TestCase
{
    private ResidualRiskService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ResidualRiskService();
    }

    /**
     * With no controls, treatment or inflation, residual equals inherent.
     */
    public function test_no_controls_means_residual_equals_inherent(): void
    {
        $input = new ResidualRiskInput(
            inherentScore: 144,
            likelihood: 4,
            impact: 5,
            evidenceConfidence: 100.0
        );

        $result = $this->service->score($input);

        $this->assertSame(144, $result->residualScore);
        $this->assertSame(0.0, $result->reductionPct);
        $this->assertSame('Critical', $result->severityBand);
    }

    /**
     * Full control effectiveness applies the configured control weight (0.50).
     */
    public function test_full_control_effectiveness_reduces_by_weight(): void
    {
        $input = new ResidualRiskInput(
            inherentScore: 144,
            likelihood: 4,
            impact: 5,
            controlEffectiveness: 100.0,
            evidenceConfidence: 100.0
        );

        $result = $this->service->score($input);

        // effective_reduction = 1.0 * 0.50 = 0.50 => 144 * 0.5 = 72
        $this->assertSame(72, $result->residualScore);
        $this->assertSame(50.0, $result->reductionPct);
        $this->assertSame('Medium', $result->severityBand);
    }

    /**
     * Controls + completed effective treatment stack their reductions.
     */
    public function test_controls_and_treatment_stack(): void
    {
        $input = new ResidualRiskInput(
            inherentScore: 144,
            likelihood: 4,
            impact: 5,
            controlEffectiveness: 100.0,   // 1.0 * 0.50 = 0.50
            treatmentEffectiveness: 100.0, // (1.0 * 1.0) * 0.25 = 0.25
            treatmentProgress: 100.0,
            evidenceConfidence: 100.0
        );

        $result = $this->service->score($input);

        // reduction = 0.75 => 144 * 0.25 = 36
        $this->assertSame(36, $result->residualScore);
        $this->assertSame(75.0, $result->reductionPct);
        $this->assertSame('Low', $result->severityBand);
    }

    /**
     * Open remediation and weak evidence inflate residual (less reduction).
     */
    public function test_open_remediation_and_low_evidence_dampen_reduction(): void
    {
        $input = new ResidualRiskInput(
            inherentScore: 144,
            likelihood: 4,
            impact: 5,
            controlEffectiveness: 100.0, // reduction 0.50
            hasOpenRemediation: true,    // inflation 0.15
            evidenceConfidence: 0.0      // low_evidence 1.0 * 0.15 = inflation 0.15
        );

        $result = $this->service->score($input);

        // effective_reduction = 0.50 - (0.15 + 0.15) = 0.20 => 144 * 0.8 = 115
        $this->assertSame(115, $result->residualScore);
        $this->assertSame('High', $result->severityBand);
    }

    /**
     * A waived risk keeps its inherent score (documented, not reduced).
     */
    public function test_waived_risk_is_documented_not_reduced(): void
    {
        $input = new ResidualRiskInput(
            inherentScore: 144,
            likelihood: 4,
            impact: 5,
            controlEffectiveness: 100.0,
            isWaived: true,
            evidenceConfidence: 100.0
        );

        $result = $this->service->score($input);

        $this->assertSame(144, $result->residualScore);
        $this->assertSame(0.0, $result->reductionPct);
    }

    /**
     * Residual never exceeds inherent and never drops below 1 for a non-zero inherent.
     */
    public function test_residual_bounds(): void
    {
        $floored = $this->service->score(new ResidualRiskInput(
            inherentScore: 10,
            likelihood: 1,
            impact: 1,
            controlEffectiveness: 100.0,
            treatmentEffectiveness: 100.0,
            treatmentProgress: 100.0,
            evidenceConfidence: 100.0
        ));
        $this->assertGreaterThanOrEqual(1, $floored->residualScore);
        $this->assertLessThanOrEqual(10, $floored->residualScore);

        $zero = $this->service->score(new ResidualRiskInput(inherentScore: 0, likelihood: 0, impact: 0));
        $this->assertSame(0, $zero->residualScore);
    }

    /**
     * Trend direction transitions based on the previous residual baseline.
     */
    public function test_trend_direction_transitions(): void
    {
        $base = new ResidualRiskInput(
            inherentScore: 144,
            likelihood: 4,
            impact: 5,
            controlEffectiveness: 100.0,
            evidenceConfidence: 100.0
        ); // residual 72

        $this->assertSame('stable', $this->service->score($base)->trendDirection);
        $this->assertSame('improving', $this->service->score($base, null, null, '100')->trendDirection);
        $this->assertSame('worsening', $this->service->score($base, null, null, '50')->trendDirection);
        $this->assertSame('stable', $this->service->score($base, null, null, '72')->trendDirection);
    }

    /**
     * Appetite status flips around the High band boundary (84).
     */
    public function test_appetite_status(): void
    {
        // residual 72 => within appetite
        $within = $this->service->score(new ResidualRiskInput(
            inherentScore: 144, likelihood: 4, impact: 5,
            controlEffectiveness: 100.0, evidenceConfidence: 100.0
        ));
        $this->assertSame('within_appetite', $within->appetiteStatus);

        // residual 144 => exceeds appetite
        $exceeds = $this->service->score(new ResidualRiskInput(
            inherentScore: 144, likelihood: 4, impact: 5, evidenceConfidence: 100.0
        ));
        $this->assertSame('exceeds_appetite', $exceeds->appetiteStatus);
    }

    /**
     * Residual heatmap reduces likelihood while preserving worst-case impact.
     */
    public function test_residual_heatmap_reduces_likelihood_keeps_impact(): void
    {
        $result = $this->service->score(new ResidualRiskInput(
            inherentScore: 144,
            likelihood: 4,
            impact: 5,
            controlEffectiveness: 100.0,
            evidenceConfidence: 100.0
        ));

        // effective_reduction 0.5 => likelihood round(4 * 0.5) = 2; impact preserved 5
        $this->assertSame(2, $result->heatmapCoordinates['likelihood']);
        $this->assertSame(5, $result->heatmapCoordinates['impact']);
    }

    /**
     * Engine is deterministic and reproducible from its snapshot.
     */
    public function test_deterministic_and_reproducible(): void
    {
        $input = new ResidualRiskInput(
            inherentScore: 144, likelihood: 4, impact: 5,
            controlEffectiveness: 60.0, treatmentEffectiveness: 40.0,
            treatmentProgress: 50.0, evidenceConfidence: 80.0
        );

        $a = $this->service->score($input);
        $b = $this->service->score(ResidualRiskInput::fromArray($a->inputSnapshot), $a->formulaVersion);

        $this->assertSame($a->residualScore, $b->residualScore);
        $this->assertSame($a->severityBand, $b->severityBand);
        $this->assertSame($a->reductionPct, $b->reductionPct);
    }
}
