<?php

namespace Tests\Feature\Compliance;

use App\Models\AssessmentFinding;
use App\Models\Control;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\ProjectAssessment;
use App\Models\User;
use App\Modules\Compliance\Services\ComplianceQueryService;
use App\Modules\Compliance\Models\ControlTest;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Models\RiskTreatmentPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceQueryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->project = Project::create(['name' => 'Query Test', 'module_type' => 'compliance', 'user_id' => $this->user->id]);
    }

    public function test_controls_failed_this_week(): void
    {
        $control = Control::create(['control_code' => 'C-001', 'name' => 'Fail Control', 'is_active' => true]);

        $framework = Framework::create(['name' => 'PCI DSS', 'slug' => 'pci_dss', 'is_active' => true]);
        $fc = FrameworkControl::create(['framework_id' => $framework->id, 'control_id' => 'PCI-1.1', 'domain' => 'General', 'requirement_description' => 'Test requirement']);

        $risk = RiskRegister::create([
            'project_id' => $this->project->id,
            'serial_no' => 'QR-001',
            'asset_process_service' => 'Query Risk',
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

        $risk->controlMappings()->create([
            'control_id' => $control->id,
            'framework_control_id' => $fc->id,
            'mapping_status' => 'confirmed',
        ]);

        ControlTest::create([
            'control_id' => $control->id,
            'tested_by' => $this->user->id,
            'test_type' => 'manual',
            'test_date' => now(),
            'result' => 'fail',
        ]);

        $service = app(ComplianceQueryService::class);
        $failed = $service->controlsFailedThisWeek($this->project->id);

        $this->assertCount(1, $failed);
        $this->assertEquals('Fail Control', $failed->first()['control_name']);
    }

    public function test_overdue_by_sla(): void
    {
        $risk = RiskRegister::create([
            'project_id' => $this->project->id,
            'serial_no' => 'QR-002',
            'asset_process_service' => 'Overdue SLA Risk',
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

        RiskTreatmentPlan::create([
            'risk_register_id' => $risk->id,
            'title' => 'Overdue plan',
            'treatment_type' => 'reduce',
            'status' => 'in_progress',
            'target_date' => now()->subDays(5),
        ]);

        $service = app(ComplianceQueryService::class);
        $overdue = $service->overdueBySLA($this->project->id);

        $this->assertGreaterThanOrEqual(1, $overdue->count());
    }

    public function test_compliance_by_framework(): void
    {
        $framework = Framework::create(['name' => 'PCI DSS', 'slug' => 'pci_dss', 'is_active' => true]);
        $fc = FrameworkControl::create(['framework_id' => $framework->id, 'control_id' => 'PCI-1.1', 'domain' => 'General', 'requirement_description' => 'Test requirement']);

        $risk = RiskRegister::create([
            'project_id' => $this->project->id,
            'serial_no' => 'QR-003',
            'asset_process_service' => 'Framework Risk',
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
            'project_id' => $this->project->id,
            'framework_id' => $framework->id,
            'type' => 'Gap',
            'start_date' => now(),
            'overall_status' => 'in_progress',
        ]);

        AssessmentFinding::create([
            'project_assessment_id' => $assessment->id,
            'framework_control_id' => $fc->id,
            'status' => 'Open',
            'observation' => 'Non-compliant',
            'is_compliant' => false,
            'is_applicable' => true,
            'compliance_state' => 'non_compliant',
            'risk_register_id' => $risk->id,
        ]);

        $service = app(ComplianceQueryService::class);
        $byFramework = $service->complianceByFramework($this->project->id);

        $this->assertCount(1, $byFramework);
        $this->assertEquals('PCI DSS', $byFramework->first()['framework']);
        $this->assertEquals(0, $byFramework->first()['compliance_pct']);
    }

    public function test_control_test_history(): void
    {
        $control = Control::create(['control_code' => 'C-002', 'name' => 'History Control', 'is_active' => true]);

        ControlTest::create([
            'control_id' => $control->id,
            'tested_by' => $this->user->id,
            'test_type' => 'manual',
            'test_date' => now(),
            'result' => 'pass',
        ]);

        ControlTest::create([
            'control_id' => $control->id,
            'tested_by' => $this->user->id,
            'test_type' => 'automated',
            'test_date' => now()->subDay(),
            'result' => 'fail',
        ]);

        $service = app(ComplianceQueryService::class);
        $history = $service->controlTestHistory($control->id, 5);

        $this->assertCount(2, $history);
    }

    public function test_framework_requirements_impacted(): void
    {
        $framework = Framework::create(['name' => 'PCI DSS', 'slug' => 'pci_dss', 'is_active' => true]);
        $fc = FrameworkControl::create(['framework_id' => $framework->id, 'control_id' => 'PCI-2.1', 'domain' => 'General', 'requirement_description' => 'Test requirement']);

        $assessment = ProjectAssessment::create([
            'project_id' => $this->project->id,
            'framework_id' => $framework->id,
            'type' => 'Gap',
            'start_date' => now(),
            'overall_status' => 'in_progress',
        ]);

        AssessmentFinding::create([
            'project_assessment_id' => $assessment->id,
            'framework_control_id' => $fc->id,
            'status' => 'Open',
            'observation' => 'Impacted finding',
            'is_compliant' => false,
            'is_applicable' => true,
            'compliance_state' => 'non_compliant',
        ]);

        $service = app(ComplianceQueryService::class);
        $impacted = $service->frameworkRequirementsImpacted($fc->id);

        $this->assertCount(1, $impacted);
    }
}
