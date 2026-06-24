<?php

namespace Tests\Feature\Compliance;

use App\Models\AssessmentFinding;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\ProjectAssessment;
use App\Models\User;
use App\Modules\Compliance\Services\RemediationService;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Models\RiskTreatmentPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemediationPlanTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected AssessmentFinding $finding;
    protected RiskRegister $risk;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        $project = Project::create(['name' => 'Remediation Test', 'module_type' => 'compliance', 'user_id' => $this->user->id]);

        $framework = Framework::create(['name' => 'PCI DSS', 'slug' => 'pci_dss', 'is_active' => true]);
        $fc = FrameworkControl::create(['framework_id' => $framework->id, 'control_id' => 'PCI-1.1', 'domain' => 'General', 'requirement_description' => 'Test requirement']);

        $this->risk = RiskRegister::create([
            'project_id' => $project->id,
            'serial_no' => 'R-001',
            'asset_process_service' => 'Test Risk',
            'risk_owner' => 'Test Owner',
            'risk_calculation_date' => '2026-06-01',
            'asset_value_bdt' => 100000,
            'threats' => ['Data Breach'],
            'threat_level_t' => 3,
            'vulnerabilities' => ['Weak Access Control'],
            'vulnerability_level_av' => 3,
            'impact_confidentiality' => 3,
            'impact_integrity' => 3,
            'impact_availability' => 3,
            'existing_control' => 'Firewall',
            'tv_t_av' => 6,
            'likelihood_lh' => 3,
            'risk_rating_avtvlh' => 54,
            'measurement' => 'Not Accepted',
            'residual_tv' => 2,
            'residual_lh' => 2,
            'residual_rating' => 4,
            'category' => 'Cybersecurity',
            'department' => 'IT',
            'lifecycle_status' => 'draft',
        ]);

        $assessment = ProjectAssessment::create([
            'project_id' => $project->id,
            'framework_id' => $framework->id,
            'type' => 'Gap',
            'start_date' => now(),
            'overall_status' => 'in_progress',
        ]);

        $this->finding = AssessmentFinding::create([
            'project_assessment_id' => $assessment->id,
            'framework_control_id' => $fc->id,
            'status' => 'Open',
            'observation' => 'Missing firewall rule',
            'is_compliant' => false,
            'is_applicable' => true,
            'compliance_state' => 'non_compliant',
            'risk_register_id' => $this->risk->id,
        ]);
    }

    public function test_create_plan_from_finding(): void
    {
        $service = app(RemediationService::class);
        $plan = $service->createFromFinding($this->finding, $this->user->id);

        $this->assertDatabaseHas('risk_treatment_plans', ['id' => $plan->id]);
        $this->assertEquals('planned', $plan->status);
        $this->assertEquals($this->risk->id, $plan->risk_register_id);
        $this->assertStringContainsString('Missing firewall rule', $plan->title);
    }

    public function test_create_plan_updates_finding_state(): void
    {
        $service = app(RemediationService::class);
        $service->createFromFinding($this->finding, $this->user->id);

        $this->assertEquals('under_review', $this->finding->fresh()->compliance_state);
    }

    public function test_close_plan(): void
    {
        $service = app(RemediationService::class);
        $plan = $service->createFromFinding($this->finding, $this->user->id);

        $closed = $service->closePlan($plan, 'All controls implemented');

        $this->assertEquals('completed', $closed->status);
        $this->assertNotNull($closed->completion_date);
    }

    public function test_get_overdue_plans(): void
    {
        $service = app(RemediationService::class);

        RiskTreatmentPlan::create([
            'risk_register_id' => $this->risk->id,
            'title' => 'Overdue plan',
            'treatment_type' => 'reduce',
            'status' => 'in_progress',
            'target_date' => now()->subDays(1),
        ]);

        $overdue = $service->getOverdueBySLA();
        $this->assertGreaterThanOrEqual(1, $overdue->count());
    }

    public function test_get_plans_by_finding(): void
    {
        $service = app(RemediationService::class);
        $plan = $service->createFromFinding($this->finding, $this->user->id);

        $plans = $service->getByFinding($this->finding);
        $this->assertCount(1, $plans);
        $this->assertEquals($plan->id, $plans->first()->id);
    }
}
