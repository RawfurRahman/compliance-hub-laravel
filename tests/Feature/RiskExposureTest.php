<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Models\RiskExposure;
use App\Modules\RiskManagement\Services\RiskExposureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskExposureTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected RiskRegister $risk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::create([
            'name' => 'Exposure Test Project',
            'module_type' => 'iso_27001',
            'user_id' => $this->user->id,
        ]);

        $this->risk = RiskRegister::create([
            'project_id' => $this->project->id,
            'serial_no' => 'EX-001',
            'asset_process_service' => 'Exposure Test Risk',
            'risk_owner' => 'Owner',
            'risk_calculation_date' => now(),
            'asset_value_bdt' => 1000000,
            'category' => 'Test',
            'department' => 'IT',
            'threats' => ['Threat'],
            'threat_level_t' => 4,
            'vulnerabilities' => ['Vuln'],
            'vulnerability_level_av' => 4,
            'impact_confidentiality' => 4,
            'impact_integrity' => 4,
            'impact_availability' => 4,
            'existing_control' => 'None',
            'tv_t_av' => 8,
            'likelihood_lh' => 4,
            'risk_rating_avtvlh' => 128,
            'measurement' => 'Not Accepted',
            'residual_tv' => 3,
            'residual_lh' => 2,
            'residual_rating' => 6,
        ]);
    }

    public function test_can_calculate_exposure(): void
    {
        $this->actingAs($this->user);
        $service = new RiskExposureService();

        $exposure = $service->calculateAndStore($this->risk);

        $this->assertDatabaseHas('risk_exposures', [
            'risk_register_id' => $this->risk->id,
            'exposure_type' => 'financial',
        ]);

        $this->assertNotNull($exposure->inherent_exposure);
        $this->assertNotNull($exposure->residual_exposure);
        $this->assertGreaterThan($exposure->residual_exposure, $exposure->inherent_exposure);
    }

    public function test_exposure_level_low(): void
    {
        $exposure = new RiskExposure([
            'residual_exposure' => 50000,
        ]);

        $this->assertEquals('Low', $exposure->exposure_level);
    }

    public function test_exposure_level_medium(): void
    {
        $exposure = new RiskExposure([
            'residual_exposure' => 250000,
        ]);

        $this->assertEquals('Medium', $exposure->exposure_level);
    }

    public function test_exposure_level_high(): void
    {
        $exposure = new RiskExposure([
            'residual_exposure' => 750000,
        ]);

        $this->assertEquals('High', $exposure->exposure_level);
    }

    public function test_exposure_level_critical(): void
    {
        $exposure = new RiskExposure([
            'residual_exposure' => 2000000,
        ]);

        $this->assertEquals('Critical', $exposure->exposure_level);
    }
}
