<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Models\RiskTreatmentPlan;
use App\Modules\RiskManagement\Services\RiskTreatmentPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskTreatmentPlanTest extends TestCase
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
            'name' => 'Treatment Plan Test Project',
            'module_type' => 'iso_27001',
            'user_id' => $this->user->id,
        ]);

        $this->risk = RiskRegister::create([
            'project_id' => $this->project->id,
            'serial_no' => 'TP-001',
            'asset_process_service' => 'Treatment Test',
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

    public function test_can_create_treatment_plan(): void
    {
        $this->actingAs($this->user);
        $service = new RiskTreatmentPlanService();

        $plan = $service->create([
            'risk_register_id' => $this->risk->id,
            'title' => 'Implement Firewall',
            'treatment_type' => 'reduce',
            'description' => 'Deploy next-gen firewall',
            'responsible_party' => 'IT Team',
            'budget_estimated' => 50000,
            'start_date' => now()->toDateString(),
            'target_date' => now()->addMonths(3)->toDateString(),
        ]);

        $this->assertDatabaseHas('risk_treatment_plans', [
            'id' => $plan->id,
            'title' => 'Implement Firewall',
            'treatment_type' => 'reduce',
        ]);
    }

    public function test_can_update_plan_progress(): void
    {
        $this->actingAs($this->user);
        $service = new RiskTreatmentPlanService();

        $plan = $service->create([
            'risk_register_id' => $this->risk->id,
            'title' => 'Update Firewall',
            'treatment_type' => 'reduce',
        ]);

        $updated = $service->updateProgress($plan, 75);

        $this->assertEquals(75, $updated->progress_pct);
    }

    public function test_plan_is_overdue(): void
    {
        $plan = RiskTreatmentPlan::create([
            'risk_register_id' => $this->risk->id,
            'title' => 'Overdue Plan',
            'treatment_type' => 'reduce',
            'target_date' => now()->subDay(),
            'status' => 'in_progress',
        ]);

        $this->assertTrue($plan->isOverdue);
    }

    public function test_plan_is_not_overdue_when_completed(): void
    {
        $plan = RiskTreatmentPlan::create([
            'risk_register_id' => $this->risk->id,
            'title' => 'Completed Plan',
            'treatment_type' => 'reduce',
            'target_date' => now()->subDay(),
            'status' => 'completed',
        ]);

        $this->assertFalse($plan->isOverdue);
    }

    public function test_plan_is_not_overdue_when_future_date(): void
    {
        $plan = RiskTreatmentPlan::create([
            'risk_register_id' => $this->risk->id,
            'title' => 'Future Plan',
            'treatment_type' => 'reduce',
            'target_date' => now()->addMonth(),
            'status' => 'planned',
        ]);

        $this->assertFalse($plan->isOverdue);
    }

    public function test_can_mark_plan_completed(): void
    {
        $this->actingAs($this->user);
        $service = new RiskTreatmentPlanService();

        $plan = $service->create([
            'risk_register_id' => $this->risk->id,
            'title' => 'Complete Plan',
            'treatment_type' => 'reduce',
        ]);

        $completed = $service->markCompleted($plan, 85);

        $this->assertEquals('completed', $completed->status);
        $this->assertEquals(100, $completed->progress_pct);
        $this->assertEquals(85, $completed->effectiveness_rating);
        $this->assertNotNull($completed->completion_date);
    }

    public function test_treatment_plan_fillable_fields(): void
    {
        $plan = new RiskTreatmentPlan();

        $this->assertTrue($plan->isFillable('title'));
        $this->assertTrue($plan->isFillable('treatment_type'));
        $this->assertTrue($plan->isFillable('description'));
        $this->assertTrue($plan->isFillable('budget_estimated'));
        $this->assertTrue($plan->isFillable('effectiveness_rating'));
    }
}
