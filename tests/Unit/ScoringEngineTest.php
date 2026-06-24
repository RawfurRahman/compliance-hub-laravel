<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Project;
use App\Models\User;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Services\ScoringEngine;
use App\Modules\RiskManagement\Services\RiskService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ScoringEngineTest extends TestCase
{
    use RefreshDatabase;

    private ScoringEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new ScoringEngine();
    }

    /**
     * Test TV Score calculation.
     * TV = Threat (T) + Vulnerability (AV)
     */
    public function test_tv_score_calculation()
    {
        $this->assertEquals(9, $this->engine->calculateTvScore(5, 4));
        $this->assertEquals(7, $this->engine->calculateTvScore(4, 3));
        $this->assertEquals(2, $this->engine->calculateTvScore(1, 1));
    }

    /**
     * Test Inherent Risk Score calculation.
     * Inherent Rating = AV * TV * LH
     */
    public function test_inherent_score_calculation_reproduces_workbook_sample_rows()
    {
        // Row 1: Customer Data Management
        // Threat = 5, Vuln (AV) = 4, TV = 9, Likelihood (LH) = 4
        // Expected Risk Rating = 4 * 9 * 4 = 144
        $tv = $this->engine->calculateTvScore(5, 4);
        $score = $this->engine->calculateInherentScore(4, $tv, 4);
        $this->assertEquals(144, $score);

        // Row 2: Employee Access Control
        // Threat = 4, Vuln (AV) = 3, TV = 7, Likelihood (LH) = 3
        // Expected Risk Rating = 3 * 7 * 3 = 63
        $tv2 = $this->engine->calculateTvScore(4, 3);
        $score2 = $this->engine->calculateInherentScore(3, $tv2, 3);
        $this->assertEquals(63, $score2);
    }

    /**
     * Test Cumulative Control Effectiveness.
     * Formula: 1 - product(1 - effectiveness_i / 100)
     */
    public function test_cumulative_control_effectiveness()
    {
        // No controls mapped
        $this->assertEquals(0.0, $this->engine->calculateCumulativeEffectiveness([]));

        // Single control
        $this->assertEquals(50.0, $this->engine->calculateCumulativeEffectiveness([50]));

        // Two controls: 50% and 30%
        // Remaining: (1 - 0.5) * (1 - 0.3) = 0.5 * 0.7 = 0.35
        // Cumulative: 1 - 0.35 = 65%
        $this->assertEquals(65.0, $this->engine->calculateCumulativeEffectiveness([50, 30]));

        // Three controls: 50%, 30% and 20%
        // Remaining: 0.35 * 0.8 = 0.28
        // Cumulative: 1 - 0.28 = 72%
        $this->assertEquals(72.0, $this->engine->calculateCumulativeEffectiveness([50, 30, 20]));

        // Total security control
        $this->assertEquals(100.0, $this->engine->calculateCumulativeEffectiveness([50, 100, 30]));
    }

    /**
     * Test Residual Inputs reduction.
     */
    public function test_residual_inputs_reduction()
    {
        // TV = 9, Likelihood = 4, Effectiveness = 50%
        // Residual TV: ceil(9 * 0.5) = 5
        // Residual LH: ceil(4 * 0.5) = 2
        $inputs = $this->engine->calculateResidualInputs(9, 4, 50.0);
        $this->assertEquals(5, $inputs['residual_tv']);
        $this->assertEquals(2, $inputs['residual_lh']);

        // TV = 9, Likelihood = 4, Effectiveness = 100%
        // Residual TV: max(1, ceil(9 * 0)) = 1
        // Residual LH: max(1, ceil(4 * 0)) = 1
        $inputs2 = $this->engine->calculateResidualInputs(9, 4, 100.0);
        $this->assertEquals(1, $inputs2['residual_tv']);
        $this->assertEquals(1, $inputs2['residual_lh']);
    }

    /**
     * Test Score to Level mapping matching Legend.
     * Thresholds: Critical >=128, High 84–127, Medium 54–83, Low <=53
     */
    public function test_score_to_level_thresholds()
    {
        $this->assertEquals('Critical', $this->engine->scoreToLevel(144));
        $this->assertEquals('Critical', $this->engine->scoreToLevel(128));

        $this->assertEquals('High', $this->engine->scoreToLevel(127));
        $this->assertEquals('High', $this->engine->scoreToLevel(84));

        $this->assertEquals('Medium', $this->engine->scoreToLevel(83));
        $this->assertEquals('Medium', $this->engine->scoreToLevel(54));

        $this->assertEquals('Low', $this->engine->scoreToLevel(53));
        $this->assertEquals('Low', $this->engine->scoreToLevel(10));
    }

    /**
     * Test RiskService dynamic recalculation and history audit logs integration.
     */
    public function test_risk_service_recalculation_integration()
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Recalc Project',
            'module_type' => 'iso_27001',
            'user_id' => $user->id,
        ]);

        $risk = RiskRegister::create([
            'project_id' => $project->id,
            'serial_no' => 'RC-001',
            'asset_process_service' => 'Audit logs tracking',
            'risk_owner' => 'IT Admin',
            'department' => 'IT',
            'risk_calculation_date' => '2026-06-22',
            'asset_value_bdt' => 100000.00,
            'category' => 'Cybersecurity',
            'threats' => ['General Threat'],
            'threat_level_t' => 5,
            'vulnerabilities' => ['Unpatched OS'],
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

        $service = new RiskService();

        // 1. Initially no mapped controls
        $service->recalculateRisk($risk);

        $risk->refresh();
        $this->assertEquals(9, $risk->computed_tv);
        $this->assertEquals(144, $risk->computed_risk_rating);
        // Without control mappings, it defaults to manual workbook residual inputs:
        // Computed residual tv = 5
        // Computed residual lh = 2
        // Computed residual rating = 5 * 2 = 10
        $this->assertEquals(10, $risk->computed_residual_rating);

        // Verify history contains records
        $this->assertDatabaseHas('risk_scores_history', [
            'risk_register_id' => $risk->id,
            'tv_score' => 9,
            'lh_score' => 4,
            'rating_score' => 144,
            'threat_level_t' => 5,
            'vulnerability_level_av' => 4,
            'control_effectiveness' => 0.00,
            'residual_tv' => 5,
            'residual_lh' => 2,
            'residual_rating' => 10,
        ]);

        // 2. Map two controls (50% and 30% effectiveness)
        $risk->controlMappings()->create([
            'effectiveness' => 50,
            'control_type' => 'Preventive',
        ]);
        $risk->controlMappings()->create([
            'effectiveness' => 30,
            'control_type' => 'Detective',
        ]);

        $service->recalculateRisk($risk);
        $risk->refresh();

        // Cumulative effectiveness = 65%
        // Remaining factor = 1.0 - 0.65 = 0.35
        // Residual TV = ceil(9 * 0.35) = 4
        // Residual LH = ceil(4 * 0.35) = 2
        // Residual Rating = 4 * 2 = 8
        $this->assertEquals(8, $risk->computed_residual_rating);

        $this->assertDatabaseHas('risk_scores_history', [
            'risk_register_id' => $risk->id,
            'control_effectiveness' => 65.00,
            'residual_tv' => 4,
            'residual_lh' => 2,
            'residual_rating' => 8,
        ]);
    }
}
