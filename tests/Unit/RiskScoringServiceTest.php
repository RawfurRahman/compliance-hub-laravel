<?php

namespace Tests\Unit;

use App\Modules\RiskManagement\Services\RiskScoringService;
use App\Modules\RiskManagement\Support\Scoring\InherentRiskInput;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit tests for the inherent risk scoring engine.
 *
 * These do not touch the database; they assert workbook parity, banding,
 * appetite status, heatmap coordinates, ranking, determinism and
 * reproducibility of the pure scoring logic.
 */
class RiskScoringServiceTest extends TestCase
{
    private RiskScoringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RiskScoringService();
    }

    private function input(int $threat, int $vuln, int $likelihood, array $impact = [], float $assetValue = 100000.0): InherentRiskInput
    {
        return new InherentRiskInput(
            threatLevel: $threat,
            vulnerabilityLevel: $vuln,
            impactDimensions: $impact ?: ['confidentiality' => $impact['c'] ?? 3, 'integrity' => 3, 'availability' => 3],
            likelihood: $likelihood,
            assetValue: $assetValue
        );
    }

    /**
     * Representative workbook rows reproduce expected inherent scores.
     */
    public function test_inherent_score_parity_with_workbook_rows(): void
    {
        // Row 1: Customer Data Management -> T=5, AV=4, LH=4 => 4 * 9 * 4 = 144
        $row1 = $this->service->score($this->input(5, 4, 4, ['confidentiality' => 5, 'integrity' => 4, 'availability' => 4]));
        $this->assertSame(9, $row1->tvScore);
        $this->assertSame(144, $row1->inherentScore);
        $this->assertSame('Critical', $row1->severityBand);

        // Row 2: Employee Access Control -> T=4, AV=3, LH=3 => 3 * 7 * 3 = 63
        $row2 = $this->service->score($this->input(4, 3, 3, ['confidentiality' => 3, 'integrity' => 3, 'availability' => 3]));
        $this->assertSame(7, $row2->tvScore);
        $this->assertSame(63, $row2->inherentScore);
        $this->assertSame('Medium', $row2->severityBand);

        // Row 3: Low risk baseline -> T=3, AV=3, LH=3 => 3 * 6 * 3 = 54
        $row3 = $this->service->score($this->input(3, 3, 3));
        $this->assertSame(54, $row3->inherentScore);
        $this->assertSame('Medium', $row3->severityBand);

        // Row 4: Genuinely low -> T=2, AV=2, LH=2 => 2 * 4 * 2 = 16
        $row4 = $this->service->score($this->input(2, 2, 2, ['confidentiality' => 2, 'integrity' => 2, 'availability' => 2]));
        $this->assertSame(16, $row4->inherentScore);
        $this->assertSame('Low', $row4->severityBand);
    }

    /**
     * Banding thresholds match the workbook legend.
     */
    public function test_band_thresholds(): void
    {
        $this->assertSame('Critical', $this->service->score($this->input(5, 4, 4))->severityBand); // 144
        $this->assertSame('High', $this->service->score($this->input(4, 4, 3))->severityBand);      // 4*8*3 = 96
        $this->assertSame('Medium', $this->service->score($this->input(3, 3, 3))->severityBand);    // 54
        $this->assertSame('Low', $this->service->score($this->input(2, 2, 2))->severityBand);       // 16
    }

    /**
     * Appetite status uses the High band boundary by default, or an explicit threshold.
     */
    public function test_appetite_status(): void
    {
        // Default appetite line = High band lower bound (84).
        $this->assertSame('exceeds_appetite', $this->service->score($this->input(5, 4, 4))->appetiteStatus); // 144
        $this->assertSame('within_appetite', $this->service->score($this->input(3, 3, 3))->appetiteStatus);  // 54

        // Explicit threshold overrides the default.
        $this->assertSame('exceeds_appetite', $this->service->score($this->input(3, 3, 3), null, 50)->appetiteStatus);
        $this->assertSame('within_appetite', $this->service->score($this->input(3, 3, 3), null, 200)->appetiteStatus);
    }

    /**
     * Heatmap coordinates pick likelihood and the max impact dimension.
     */
    public function test_heatmap_coordinates(): void
    {
        $result = $this->service->score($this->input(5, 4, 4, ['confidentiality' => 5, 'integrity' => 2, 'availability' => 3]));
        $this->assertSame(4, $result->heatmapCoordinates['likelihood']);
        $this->assertSame(5, $result->heatmapCoordinates['impact']);
    }

    /**
     * Ranking is a normalised 0-100 weight ordered by inherent score.
     */
    public function test_risk_ranking_is_normalised_and_ordered(): void
    {
        $high = $this->service->score($this->input(5, 4, 4)); // 144
        $low = $this->service->score($this->input(2, 2, 2));  // 16

        $this->assertGreaterThan($low->riskRanking, $high->riskRanking);
        $this->assertEqualsWithDelta(57.6, $high->riskRanking, 0.01); // 144/250*100
        $this->assertEqualsWithDelta(6.4, $low->riskRanking, 0.01);   // 16/250*100
    }

    /**
     * Engine is deterministic: identical inputs yield identical output.
     */
    public function test_engine_is_deterministic(): void
    {
        $a = $this->service->score($this->input(5, 4, 4));
        $b = $this->service->score($this->input(5, 4, 4));

        $this->assertEquals($a->toArray()['inherent_score'], $b->toArray()['inherent_score']);
        $this->assertEquals($a->toArray()['explanation'], $b->toArray()['explanation']);
    }

    /**
     * Explanation metadata + input snapshot allow later reconstruction.
     */
    public function test_result_carries_reproducibility_metadata(): void
    {
        $result = $this->service->score($this->input(5, 4, 4));

        $this->assertSame('v1', $result->formulaVersion);
        $this->assertSame('5 + 4 = 9', $result->explanation['steps']['tv']);
        $this->assertSame('4 * 9 * 4 = 144', $result->explanation['steps']['inherent']);
        $this->assertSame(5, $result->inputSnapshot['threat_level']);
        $this->assertSame(4, $result->inputSnapshot['vulnerability_level']);
        $this->assertSame(4, $result->inputSnapshot['likelihood']);
    }

    /**
     * Re-scoring from a stored snapshot reproduces the original score exactly.
     */
    public function test_reproducible_from_input_snapshot(): void
    {
        $original = $this->service->score($this->input(5, 4, 4));

        $rebuiltInput = InherentRiskInput::fromArray($original->inputSnapshot);
        $rebuilt = $this->service->score($rebuiltInput, $original->formulaVersion);

        $this->assertSame($original->inherentScore, $rebuilt->inherentScore);
        $this->assertSame($original->tvScore, $rebuilt->tvScore);
        $this->assertSame($original->severityBand, $rebuilt->severityBand);
        $this->assertSame($original->riskRanking, $rebuilt->riskRanking);
    }
}
