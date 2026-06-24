<?php

namespace Tests\Feature\Compliance;

use App\Models\AssessmentFinding;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\ProjectAssessment;
use App\Models\User;
use App\Modules\Compliance\Services\ComplianceFindingService;
use App\Modules\RiskManagement\Models\RiskRegister;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceFindingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected FrameworkControl $frameworkControl;
    protected AssessmentFinding $finding;
    protected ProjectAssessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        $project = Project::create(['name' => 'Finding Test', 'module_type' => 'compliance', 'user_id' => $this->user->id]);
        $framework = Framework::create(['name' => 'PCI DSS', 'slug' => 'pci_dss', 'is_active' => true]);
        $this->frameworkControl = FrameworkControl::create([
            'framework_id' => $framework->id,
            'control_id' => 'PCI-1.1',
            'domain' => 'General',
            'requirement_description' => 'Test requirement',
        ]);
        $this->assessment = ProjectAssessment::create([
            'project_id' => $project->id,
            'framework_id' => $framework->id,
            'type' => 'Gap',
            'start_date' => now(),
            'overall_status' => 'in_progress',
        ]);

        $this->finding = AssessmentFinding::create([
            'project_assessment_id' => $this->assessment->id,
            'framework_control_id' => $this->frameworkControl->id,
            'status' => 'Open',
            'observation' => 'Test finding',
            'is_compliant' => false,
            'is_applicable' => true,
            'compliance_state' => 'non_compliant',
            'due_date' => now()->addDays(7),
        ]);
    }

    public function test_set_state_changes_compliance_state(): void
    {
        $service = app(ComplianceFindingService::class);
        $updated = $service->setState($this->finding, 'compliant');

        $this->assertEquals('compliant', $updated->compliance_state);
    }

    public function test_get_overdue_findings(): void
    {
        $past = AssessmentFinding::create([
            'project_assessment_id' => $this->assessment->id,
            'framework_control_id' => $this->frameworkControl->id,
            'status' => 'Open',
            'observation' => 'Overdue finding',
            'is_compliant' => false,
            'is_applicable' => true,
            'compliance_state' => 'non_compliant',
            'due_date' => now()->subDays(1),
        ]);

        $service = app(ComplianceFindingService::class);
        $overdue = $service->getOverdue();

        $this->assertGreaterThanOrEqual(1, $overdue->count());
        $this->assertTrue($overdue->contains('id', $past->id));
    }

    public function test_get_by_framework(): void
    {
        $service = app(ComplianceFindingService::class);
        $results = $service->getByFramework($this->frameworkControl->id);

        $this->assertCount(1, $results);
    }

    public function test_count_by_state_returns_correct_counts(): void
    {
        $project = Project::create([
            'name' => 'Finding Count Test',
            'module_type' => 'compliance',
            'user_id' => $this->user->id,
        ]);

        $risk = RiskRegister::create([
            'project_id' => $project->id,
            'serial_no' => 'F-001',
            'asset_process_service' => 'Test Finding Risk',
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

        $this->finding->update(['risk_register_id' => $risk->id]);

        $counts = ComplianceFindingService::countByState($project->id);
        $this->assertArrayHasKey('non_compliant', $counts);
        $this->assertEquals(1, $counts['non_compliant']);
    }

    public function test_set_state_to_waived(): void
    {
        $service = app(ComplianceFindingService::class);
        $updated = $service->setState($this->finding, 'waived');

        $this->assertEquals('waived', $updated->compliance_state);
    }
}
