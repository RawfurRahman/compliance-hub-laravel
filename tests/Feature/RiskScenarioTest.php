<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Models\RiskScenario;
use App\Modules\RiskManagement\Services\RiskScenarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskScenarioTest extends TestCase
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
            'name' => 'Scenario Test Project',
            'module_type' => 'iso_27001',
            'user_id' => $this->user->id,
        ]);

        $this->risk = RiskRegister::create([
            'project_id' => $this->project->id,
            'serial_no' => 'SC-001',
            'asset_process_service' => 'Scenario Test Risk',
            'risk_owner' => 'Owner',
            'risk_calculation_date' => now(),
            'asset_value_bdt' => 100000,
            'category' => 'Test',
            'department' => 'IT',
            'threats' => ['Threat'],
            'threat_level_t' => 3,
            'vulnerabilities' => ['Vuln'],
            'vulnerability_level_av' => 3,
            'impact_confidentiality' => 3,
            'impact_integrity' => 3,
            'impact_availability' => 3,
            'existing_control' => 'None',
            'tv_t_av' => 6,
            'likelihood_lh' => 3,
            'risk_rating_avtvlh' => 54,
            'measurement' => 'Not Accepted',
            'residual_tv' => 2,
            'residual_lh' => 2,
            'residual_rating' => 4,
        ]);
    }

    public function test_can_create_scenario_linked_to_risk(): void
    {
        $this->actingAs($this->user);
        $service = new RiskScenarioService();

        $scenario = $service->create([
            'risk_register_id' => $this->risk->id,
            'title' => 'Data Breach Scenario',
            'description' => 'A malicious actor gains access to customer data.',
            'threat_source' => 'External',
            'threat_event' => 'Unauthorized access',
            'scenario_date' => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('risk_scenarios', [
            'id' => $scenario->id,
            'risk_register_id' => $this->risk->id,
            'title' => 'Data Breach Scenario',
        ]);
    }

    public function test_can_create_scenario_without_risk(): void
    {
        $this->actingAs($this->user);
        $service = new RiskScenarioService();

        $scenario = $service->create([
            'title' => 'Standalone Scenario',
            'description' => 'A standalone scenario without risk link.',
        ]);

        $this->assertNull($scenario->risk_register_id);
        $this->assertEquals('Standalone Scenario', $scenario->title);
    }

    public function test_scenario_has_correct_fillable_fields(): void
    {
        $scenario = new RiskScenario();

        $this->assertTrue($scenario->isFillable('title'));
        $this->assertTrue($scenario->isFillable('description'));
        $this->assertTrue($scenario->isFillable('threat_source'));
        $this->assertTrue($scenario->isFillable('threat_event'));
        $this->assertTrue($scenario->isFillable('vulnerability_factor'));
        $this->assertTrue($scenario->isFillable('scenario_date'));
    }
}
