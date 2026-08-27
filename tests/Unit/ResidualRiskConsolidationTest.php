<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\User;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Models\RiskResidualScore;
use App\Modules\RiskManagement\Services\ResidualRiskService;
use App\Modules\RiskManagement\Services\RiskService;
use App\Modules\RiskManagement\Support\Scoring\ResidualRiskInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ResidualRiskConsolidationTest
 *
 * Verifies the consolidation of residual-risk scoring onto a single
 * authoritative implementation (ResidualRiskService). ScoringEngine's legacy
 * tv/likelihood reduction path has been removed; every computed residual —
 * whether stamped on upsert, recomputed by RiskService::recalculateRisk, or
 * persisted to risk_residual_scores — must flow through ResidualRiskService.
 */
class ResidualRiskConsolidationTest extends TestCase
{
    use RefreshDatabase;

    private ResidualRiskService $residualService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->residualService = new ResidualRiskService;
    }

    /**
     * Known input => known residual score (deterministic, v1 weights).
     */
    public function test_known_input_yields_known_residual_score(): void
    {
        $result = $this->residualService->score(new ResidualRiskInput(
            inherentScore: 144,
            likelihood: 4,
            impact: 5,
            controlEffectiveness: 100.0,
            evidenceConfidence: 100.0
        ));

        // control weight 0.50 => effective reduction 0.50 => 144 * 0.5 = 72
        $this->assertSame(72, $result->residualScore);
        $this->assertSame(50.0, $result->reductionPct);
        $this->assertSame('Medium', $result->severityBand);
    }

    /**
     * Residual can never exceed inherent, and stays >= 1 for a non-zero inherent.
     */
    public function test_residual_never_exceeds_inherent(): void
    {
        $grid = [1, 10, 25, 50, 100, 144, 150];
        $effectiveness = [0, 25, 50, 75, 100];

        foreach ($grid as $inherent) {
            foreach ($effectiveness as $eff) {
                $result = $this->residualService->score(new ResidualRiskInput(
                    inherentScore: $inherent,
                    likelihood: 5,
                    impact: 5,
                    controlEffectiveness: (float) $eff,
                    evidenceConfidence: 100.0
                ));

                $this->assertLessThanOrEqual($inherent, $result->residualScore);
                $this->assertGreaterThanOrEqual(1, $result->residualScore);
            }
        }
    }

    /**
     * A waived risk is documented, not reduced — zero reduction regardless of
     * controls/treatment.
     */
    public function test_waived_risk_receives_zero_reduction(): void
    {
        $result = $this->residualService->score(new ResidualRiskInput(
            inherentScore: 144,
            likelihood: 4,
            impact: 5,
            controlEffectiveness: 100.0,
            treatmentEffectiveness: 100.0,
            treatmentProgress: 100.0,
            isWaived: true,
            evidenceConfidence: 100.0
        ));

        $this->assertSame(144, $result->residualScore);
        $this->assertSame(0.0, $result->reductionPct);
        $this->assertTrue($result->explanation['documented_only'] ?? false);
    }

    /**
     * Calling through RiskService::recalculateRisk and ResidualRiskService
     * directly produces identical residual scores for the same risk state,
     * and the persisted risk_residual_scores row matches.
     */
    public function test_risk_service_and_residual_service_agree(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Consolidation Project',
            'module_type' => 'iso_27001',
            'user_id' => $user->id,
        ]);

        $risk = $this->makeRisk($project, 'CONS-001');

        // Map controls so the residual is actually reduced below inherent.
        $risk->controlMappings()->create(['effectiveness' => 50, 'control_type' => 'Preventive']);
        $risk->controlMappings()->create(['effectiveness' => 30, 'control_type' => 'Detective']);

        (new RiskService)->recalculateRisk($risk);
        $risk->refresh();

        $expected = $this->residualService->score(
            $this->residualService->buildInputFromRisk($risk)
        )->residualScore;

        $this->assertSame($expected, (int) $risk->computed_residual_rating);
        $this->assertLessThan((int) $risk->computed_risk_rating, $expected);

        $latest = RiskResidualScore::where('risk_register_id', $risk->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($latest);
        $this->assertSame($expected, (int) $latest->residual_score);

        // Second case: no controls — residual must equal inherent on both paths.
        $bare = $this->makeRisk($project, 'CONS-002');
        (new RiskService)->recalculateRisk($bare);
        $bare->refresh();

        $expectedBare = $this->residualService->score(
            $this->residualService->buildInputFromRisk($bare)
        )->residualScore;

        $this->assertSame($expectedBare, (int) $bare->computed_residual_rating);
        $this->assertSame((int) $bare->computed_risk_rating, $expectedBare);
    }

    private function makeRisk(Project $project, string $serialNo): RiskRegister
    {
        return RiskRegister::create([
            'project_id' => $project->id,
            'serial_no' => $serialNo,
            'asset_process_service' => 'Consolidation risk',
            'risk_owner' => 'Tester',
            'department' => 'IT',
            'risk_calculation_date' => '2026-06-22',
            'asset_value_bdt' => 100000.00,
            'category' => 'Cybersecurity',
            'threats' => ['General threat'],
            'threat_level_t' => 5,
            'vulnerabilities' => ['Unpatched system'],
            'vulnerability_level_av' => 4,
            'tv_t_av' => 9,
            'likelihood_lh' => 4,
            'risk_rating_avtvlh' => 144,
            'measurement' => 'Not Accepted',
            'residual_tv' => 5,
            'residual_lh' => 2,
            'residual_rating' => 10,
            'existing_control' => 'None',
            'impact_confidentiality' => 4,
            'impact_integrity' => 4,
            'impact_availability' => 4,
        ]);
    }
}
