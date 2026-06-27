<?php

namespace Tests\Unit;

use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Services\FinancialExposureService;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit tests for the financial exposure math. These build unsaved
 * RiskRegister instances and assert the quantitative model directly (no DB).
 */
class FinancialExposureServiceTest extends TestCase
{
    private FinancialExposureService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Fixed ratios so the assertions are deterministic.
        $this->service = new FinancialExposureService(
            remediationCostRatio: 0.15,
            businessInterruptionFactor: 0.30
        );
    }

    private function risk(float $assetValue, int $inherent, int $residual, int $likelihood): RiskRegister
    {
        $risk = new RiskRegister();
        $risk->id = 1;
        $risk->serial_no = 'FE-001';
        $risk->category = 'Cybersecurity';
        $risk->asset_value_bdt = $assetValue;
        $risk->risk_rating_avtvlh = $inherent;
        $risk->computed_risk_rating = $inherent;
        $risk->residual_rating = $residual;
        $risk->computed_residual_rating = $residual;
        $risk->likelihood_lh = $likelihood;

        return $risk;
    }

    public function test_single_loss_expectancy(): void
    {
        // asset 1,000,000; inherent 125/250 => factor 0.5 => SLE 500,000
        $result = $this->service->forRisk($this->risk(1_000_000, 125, 50, 4));
        $this->assertSame(500000.0, $result['single_loss_expectancy']);
    }

    public function test_annualized_loss_expectancy_uses_likelihood_aro(): void
    {
        // SLE 500,000; likelihood 4 => ARO 1.0 => ALE 500,000
        $r4 = $this->service->forRisk($this->risk(1_000_000, 125, 50, 4));
        $this->assertSame(1.0, $r4['annualized_rate_of_occurrence']);
        $this->assertSame(500000.0, $r4['annualized_loss_expectancy']);

        // likelihood 5 => ARO 2.0 => ALE 1,000,000
        $r5 = $this->service->forRisk($this->risk(1_000_000, 125, 50, 5));
        $this->assertSame(2.0, $r5['annualized_rate_of_occurrence']);
        $this->assertSame(1000000.0, $r5['annualized_loss_expectancy']);
    }

    public function test_expected_remediation_cost_and_business_interruption(): void
    {
        $result = $this->service->forRisk($this->risk(1_000_000, 125, 50, 4));
        // remediation = SLE * 0.15 = 75,000
        $this->assertSame(75000.0, $result['expected_remediation_cost']);
        // business interruption = asset * 0.30 * factor(0.5) = 150,000
        $this->assertSame(150000.0, $result['business_interruption_impact']);
    }

    public function test_residual_annualized_loss_uses_residual_factor(): void
    {
        // residual 25/250 => factor 0.1; asset 1,000,000; ARO 1.0 => 100,000
        $result = $this->service->forRisk($this->risk(1_000_000, 125, 25, 4));
        $this->assertSame(100000.0, $result['residual_annualized_loss']);
    }

    public function test_exposure_factor_is_capped(): void
    {
        // inherent above max still caps at factor 1.0 => SLE == asset value
        $result = $this->service->forRisk($this->risk(200000, 500, 500, 5));
        $this->assertSame(200000.0, $result['single_loss_expectancy']);
    }
}
